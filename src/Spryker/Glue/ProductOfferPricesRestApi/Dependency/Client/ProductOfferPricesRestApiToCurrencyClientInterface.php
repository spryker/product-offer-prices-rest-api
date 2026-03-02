<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\ProductOfferPricesRestApi\Dependency\Client;

use Generated\Shared\Transfer\CurrencyTransfer;

interface ProductOfferPricesRestApiToCurrencyClientInterface
{
    public function fromIsoCode(string $isoCode): CurrencyTransfer;

    /**
     * @return array<string>
     */
    public function getCurrencyIsoCodes(): array;
}
