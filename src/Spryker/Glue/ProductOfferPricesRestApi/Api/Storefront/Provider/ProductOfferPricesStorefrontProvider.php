<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace Spryker\Glue\ProductOfferPricesRestApi\Api\Storefront\Provider;

use Generated\Api\Storefront\ProductOfferPricesStorefrontResource;
use Generated\Shared\Transfer\CurrentProductPriceTransfer;
use Generated\Shared\Transfer\PriceProductFilterTransfer;
use Generated\Shared\Transfer\PriceProductResolveConditionsTransfer;
use Spryker\ApiPlatform\Exception\GlueApiException;
use Spryker\ApiPlatform\State\Provider\AbstractStorefrontProvider;
use Spryker\Client\Currency\CurrencyClientInterface;
use Spryker\Client\PriceProduct\PriceProductClientInterface;
use Spryker\Client\PriceProductStorage\PriceProductStorageClientInterface;
use Spryker\Client\ProductOfferStorage\ProductOfferStorageClientInterface;
use Spryker\Client\ProductStorage\ProductStorageClientInterface;
use Spryker\Glue\ProductOfferPricesRestApi\ProductOfferPricesRestApiConfig;
use Symfony\Component\HttpFoundation\Response;

class ProductOfferPricesStorefrontProvider extends AbstractStorefrontProvider
{
    protected const string MAPPING_TYPE_SKU = 'sku';

    protected const string KEY_ID_PRODUCT_CONCRETE = 'id_product_concrete';

    protected const string KEY_ID_PRODUCT_ABSTRACT = 'id_product_abstract';

    protected const string URI_VAR_OFFER_REFERENCE = 'productOfferReference';

    protected const string QUERY_PARAM_PRICE_MODE = 'priceMode';

    protected const string QUERY_PARAM_CURRENCY = 'currency';

    protected const string PRICE_MODE_GROSS = 'GROSS_MODE';

    protected const string PRICE_MODE_NET = 'NET_MODE';

    protected const string KEY_VOLUME_PRICES = 'volume_prices';

    protected const string KEY_VOLUME_PRICE_QUANTITY = 'quantity';

    protected const string KEY_VOLUME_PRICE_NET_PRICE = 'net_price';

    protected const string KEY_VOLUME_PRICE_GROSS_PRICE = 'gross_price';

    public function __construct(
        protected ProductOfferStorageClientInterface $productOfferStorageClient,
        protected ProductStorageClientInterface $productStorageClient,
        protected PriceProductStorageClientInterface $priceProductStorageClient,
        protected PriceProductClientInterface $priceProductClient,
        protected CurrencyClientInterface $currencyClient,
    ) {
    }

    /**
     * @return array<\Generated\Api\Storefront\ProductOfferPricesStorefrontResource>
     */
    protected function provideCollection(): array
    {
        if (!$this->hasUriVariable(static::URI_VAR_OFFER_REFERENCE)) {
            $this->throwMissingOfferReference();
        }

        $productOfferReference = (string)$this->getUriVariable(static::URI_VAR_OFFER_REFERENCE);

        if ($productOfferReference === '') {
            $this->throwMissingOfferReference();
        }

        $productOfferStorageTransfers = $this->productOfferStorageClient->getProductOfferStoragesByReferences(
            [$productOfferReference],
        );

        if ($productOfferStorageTransfers === []) {
            $this->throwOfferNotFound();
        }

        $productOfferStorageTransfer = current($productOfferStorageTransfers);
        $productConcreteSku = $productOfferStorageTransfer->getProductConcreteSku();

        if ($productConcreteSku === null || $productConcreteSku === '') {
            $this->throwOfferNotFound();
        }

        $localeName = $this->getLocale()->getLocaleNameOrFail();
        $productConcreteData = $this->productStorageClient->findProductConcreteStorageDataByMapping(
            static::MAPPING_TYPE_SKU,
            $productConcreteSku,
            $localeName,
        );

        if ($productConcreteData === null) {
            $this->throwOfferNotFound();
        }

        $priceProductTransfers = $this->priceProductStorageClient->getResolvedPriceProductConcreteTransfers(
            (int)($productConcreteData[static::KEY_ID_PRODUCT_CONCRETE] ?? 0),
            (int)($productConcreteData[static::KEY_ID_PRODUCT_ABSTRACT] ?? 0),
        );

        $request = $this->getRequest();
        $priceMode = $request->query->getString(static::QUERY_PARAM_PRICE_MODE);
        $currencyIsoCode = $request->query->getString(static::QUERY_PARAM_CURRENCY);

        $currency = $currencyIsoCode !== '' && in_array($currencyIsoCode, $this->currencyClient->getCurrencyIsoCodes(), true)
            ? $this->currencyClient->fromIsoCode($currencyIsoCode)
            : $this->currencyClient->getCurrent();

        $filterTransfer = (new PriceProductFilterTransfer())
            ->setCurrency($currency)
            ->setCurrencyIsoCode($currency->getCode())
            ->setProductOfferReference($productOfferReference)
            ->setPriceProductResolveConditions(
                (new PriceProductResolveConditionsTransfer())
                    ->fromArray($productConcreteData, true)
                    ->setProductOfferReference($productOfferReference),
            );

        if ($priceMode !== '') {
            $filterTransfer->setPriceMode($priceMode);
        }

        $currentProductPriceTransfer = $this->priceProductClient
            ->resolveProductPriceTransferByPriceProductFilter($priceProductTransfers, $filterTransfer);

        $resource = new ProductOfferPricesStorefrontResource();
        $resource->productOfferReference = $productOfferReference;
        $resource->price = $currentProductPriceTransfer->getPrice();
        $resource->prices = $this->buildPricesArray($currentProductPriceTransfer);

        return [$resource];
    }

    /**
     * @throws \Spryker\ApiPlatform\Exception\GlueApiException
     *
     * @return never
     */
    protected function throwMissingOfferReference(): void
    {
        throw new GlueApiException(
            Response::HTTP_BAD_REQUEST,
            ProductOfferPricesRestApiConfig::RESPONSE_CODE_PRODUCT_OFFER_ID_IS_NOT_SPECIFIED,
            ProductOfferPricesRestApiConfig::RESPONSE_DETAIL_PRODUCT_OFFER_ID_SKU_IS_NOT_SPECIFIED,
        );
    }

    /**
     * @throws \Spryker\ApiPlatform\Exception\GlueApiException
     *
     * @return never
     */
    protected function throwOfferNotFound(): void
    {
        throw new GlueApiException(
            Response::HTTP_NOT_FOUND,
            ProductOfferPricesRestApiConfig::RESPONSE_CODE_PRODUCT_OFFER_NOT_FOUND,
            ProductOfferPricesRestApiConfig::RESPONSE_DETAIL_PRODUCT_OFFER_NOT_FOUND,
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function buildPricesArray(CurrentProductPriceTransfer $currentProductPriceTransfer): array
    {
        $currency = $currentProductPriceTransfer->getCurrency();
        $currencyData = $currency !== null ? [
            'code' => $currency->getCode(),
            'name' => $currency->getName(),
            'symbol' => $currency->getSymbol(),
        ] : null;

        $isGross = $currentProductPriceTransfer->getPriceMode() === static::PRICE_MODE_GROSS;
        $prices = [];

        foreach ($currentProductPriceTransfer->getPrices() as $priceType => $amount) {
            $prices[] = [
                'priceTypeName' => $priceType,
                'netAmount' => $isGross ? null : $amount,
                'grossAmount' => $isGross ? $amount : null,
                'currency' => $currencyData,
                'volumePrices' => $this->extractVolumePrices($currentProductPriceTransfer, (string)$priceType),
            ];
        }

        return $prices;
    }

    /**
     * @return array<int, array<string, int|null>>
     */
    protected function extractVolumePrices(CurrentProductPriceTransfer $currentProductPriceTransfer, string $priceTypeName): array
    {
        $priceDataByType = $currentProductPriceTransfer->getPriceDataByPriceType();
        $priceDataJson = $priceDataByType[$priceTypeName] ?? $currentProductPriceTransfer->getPriceData();

        if ($priceDataJson === null || $priceDataJson === '') {
            return [];
        }

        $priceData = json_decode((string)$priceDataJson, true);

        if (!is_array($priceData) || !isset($priceData[static::KEY_VOLUME_PRICES]) || !is_array($priceData[static::KEY_VOLUME_PRICES])) {
            return [];
        }

        $volumePrices = [];
        foreach ($priceData[static::KEY_VOLUME_PRICES] as $volumePriceData) {
            if (!is_array($volumePriceData)) {
                continue;
            }

            $volumePrices[] = [
                'quantity' => isset($volumePriceData[static::KEY_VOLUME_PRICE_QUANTITY]) ? (int)$volumePriceData[static::KEY_VOLUME_PRICE_QUANTITY] : null,
                'netAmount' => isset($volumePriceData[static::KEY_VOLUME_PRICE_NET_PRICE]) ? (int)$volumePriceData[static::KEY_VOLUME_PRICE_NET_PRICE] : null,
                'grossAmount' => isset($volumePriceData[static::KEY_VOLUME_PRICE_GROSS_PRICE]) ? (int)$volumePriceData[static::KEY_VOLUME_PRICE_GROSS_PRICE] : null,
            ];
        }

        return $volumePrices;
    }
}
