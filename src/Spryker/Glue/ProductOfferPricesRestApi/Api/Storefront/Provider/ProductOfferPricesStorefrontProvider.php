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
use Generated\Shared\Transfer\RestCurrencyTransfer;
use Generated\Shared\Transfer\RestProductOfferPriceAttributesTransfer;
use Generated\Shared\Transfer\RestProductOfferPricesAttributesTransfer;
use Spryker\ApiPlatform\Exception\GlueApiException;
use Spryker\ApiPlatform\State\Provider\AbstractStorefrontProvider;
use Spryker\Client\Currency\CurrencyClientInterface;
use Spryker\Client\PriceProduct\PriceProductClientInterface;
use Spryker\Client\PriceProductStorage\PriceProductStorageClientInterface;
use Spryker\Client\ProductOfferStorage\ProductOfferStorageClientInterface;
use Spryker\Client\ProductStorage\ProductStorageClientInterface;
use Spryker\Glue\ProductOfferPricesRestApi\ProductOfferPricesRestApiConfig;
use Spryker\Service\Container\Attributes\Plugins;
use Spryker\Service\Serializer\SerializerServiceInterface;
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

    /**
     * @param array<\Spryker\Glue\ProductOfferPricesRestApiExtension\Dependency\Plugin\RestProductOfferPricesAttributesMapperPluginInterface> $restProductOfferPricesAttributesMapperPlugins
     */
    public function __construct(
        protected ProductOfferStorageClientInterface $productOfferStorageClient,
        protected ProductStorageClientInterface $productStorageClient,
        protected PriceProductStorageClientInterface $priceProductStorageClient,
        protected PriceProductClientInterface $priceProductClient,
        protected CurrencyClientInterface $currencyClient,
        protected SerializerServiceInterface $serializer,
        #[Plugins(dependencyProviderMethod: 'getRestProductOfferPricesAttributesMapperPlugins')]
        protected array $restProductOfferPricesAttributesMapperPlugins = [],
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

        $restProductOfferPricesAttributesTransfer = $this->mapToRestAttributes($currentProductPriceTransfer);

        $resource = $this->serializer->denormalize(
            $restProductOfferPricesAttributesTransfer->toArray(true, true),
            ProductOfferPricesStorefrontResource::class,
        );
        $resource->productOfferReference = $productOfferReference;

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

    protected function mapToRestAttributes(
        CurrentProductPriceTransfer $currentProductPriceTransfer,
    ): RestProductOfferPricesAttributesTransfer {
        $restProductOfferPricesAttributesTransfer = (new RestProductOfferPricesAttributesTransfer())
            ->setPrice($currentProductPriceTransfer->getPrice());

        $restCurrencyTransfer = (new RestCurrencyTransfer())
            ->fromArray($currentProductPriceTransfer->getCurrencyOrFail()->toArray(), true);

        foreach ($currentProductPriceTransfer->getPrices() as $priceType => $amount) {
            $restProductOfferPriceAttributesTransfer = (new RestProductOfferPriceAttributesTransfer())
                ->setPriceTypeName((string)$priceType)
                ->setCurrency($restCurrencyTransfer);

            if ($currentProductPriceTransfer->getPriceMode() !== null) {
                $restProductOfferPriceAttributesTransfer = $this->applyPriceModeAmount(
                    $restProductOfferPriceAttributesTransfer,
                    $currentProductPriceTransfer->getPriceMode(),
                    $amount,
                );
            }

            $restProductOfferPricesAttributesTransfer->addPrice($restProductOfferPriceAttributesTransfer);

            $restProductOfferPricesAttributesTransfer = $this->executeMapperPlugins(
                $currentProductPriceTransfer,
                $restProductOfferPricesAttributesTransfer,
            );
        }

        return $restProductOfferPricesAttributesTransfer;
    }

    protected function applyPriceModeAmount(
        RestProductOfferPriceAttributesTransfer $restProductOfferPriceAttributesTransfer,
        string $priceMode,
        int $amount,
    ): RestProductOfferPriceAttributesTransfer {
        if ($priceMode === static::PRICE_MODE_GROSS) {
            return $restProductOfferPriceAttributesTransfer->setGrossAmount($amount);
        }

        if ($priceMode === static::PRICE_MODE_NET) {
            return $restProductOfferPriceAttributesTransfer->setNetAmount($amount);
        }

        return $restProductOfferPriceAttributesTransfer;
    }

    protected function executeMapperPlugins(
        CurrentProductPriceTransfer $currentProductPriceTransfer,
        RestProductOfferPricesAttributesTransfer $restProductOfferPricesAttributesTransfer,
    ): RestProductOfferPricesAttributesTransfer {
        foreach ($this->restProductOfferPricesAttributesMapperPlugins as $plugin) {
            $restProductOfferPricesAttributesTransfer = $plugin->map(
                $currentProductPriceTransfer,
                $restProductOfferPricesAttributesTransfer,
            );
        }

        return $restProductOfferPricesAttributesTransfer;
    }
}
