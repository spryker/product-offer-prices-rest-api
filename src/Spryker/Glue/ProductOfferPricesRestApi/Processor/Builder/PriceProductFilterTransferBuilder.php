<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\ProductOfferPricesRestApi\Processor\Builder;

use Generated\Shared\Transfer\PriceProductFilterTransfer;
use Spryker\Glue\GlueApplication\Rest\Request\Data\RestRequestInterface;
use Spryker\Glue\ProductOfferPricesRestApi\Dependency\Client\ProductOfferPricesRestApiToCurrencyClientInterface;

class PriceProductFilterTransferBuilder implements PriceProductFilterTransferBuilderInterface
{
    /**
     * @var string
     */
    protected const REQUEST_PARAMETER_CURRENCY = 'currency';

    /**
     * @var \Spryker\Glue\ProductOfferPricesRestApi\Dependency\Client\ProductOfferPricesRestApiToCurrencyClientInterface
     */
    protected ProductOfferPricesRestApiToCurrencyClientInterface $currencyClient;

    public function __construct(ProductOfferPricesRestApiToCurrencyClientInterface $currencyClient)
    {
        $this->currencyClient = $currencyClient;
    }

    public function build(RestRequestInterface $restRequest): PriceProductFilterTransfer
    {
        $priceProductFilterTransfer = new PriceProductFilterTransfer();

        $currencyIsoCode = $this->getRequestParameter($restRequest, static::REQUEST_PARAMETER_CURRENCY);

        if ($currencyIsoCode === null || !$this->isValidCurrencyIsoCode($currencyIsoCode)) {
            return $priceProductFilterTransfer;
        }

        $priceProductFilterTransfer
            ->setCurrency($this->currencyClient->fromIsoCode($currencyIsoCode))
            ->setCurrencyIsoCode($currencyIsoCode);

        return $priceProductFilterTransfer;
    }

    protected function getRequestParameter(RestRequestInterface $restRequest, string $parameterName): ?string
    {
        /**
         * @var string|null $value
         */
        $value = $restRequest->getHttpRequest()->query->get($parameterName, null);

        return $value;
    }

    protected function isValidCurrencyIsoCode(string $currencyIsoCode): bool
    {
        return in_array($currencyIsoCode, $this->currencyClient->getCurrencyIsoCodes());
    }
}
