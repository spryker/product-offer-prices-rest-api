<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\ProductOfferPricesRestApi\Dependency\Client;

use Generated\Shared\Transfer\CurrencyTransfer;

class ProductOfferPricesRestApiToCurrencyClientBridge implements ProductOfferPricesRestApiToCurrencyClientInterface
{
    /**
     * @var \Spryker\Client\Currency\CurrencyClientInterface
     */
    protected $currencyClient;

    /**
     * @param \Spryker\Client\Currency\CurrencyClientInterface $currencyClient
     */
    public function __construct($currencyClient)
    {
        $this->currencyClient = $currencyClient;
    }

    /**
     * @param string $isoCode
     *
     * @return \Generated\Shared\Transfer\CurrencyTransfer
     */
    public function fromIsoCode(string $isoCode): CurrencyTransfer
    {
        return $this->currencyClient->fromIsoCode($isoCode);
    }

    /**
     * @return array<string>
     */
    public function getCurrencyIsoCodes(): array
    {
        return $this->currencyClient->getCurrencyIsoCodes();
    }
}
