<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Spryker Marketplace License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\ProductOfferPricesRestApi\Processor\Mapper;

use Generated\Shared\Transfer\CurrentProductPriceTransfer;
use Generated\Shared\Transfer\RestProductOfferPricesAttributesTransfer;

interface ProductOfferPriceMapperInterface
{
    /**
     * @param \Generated\Shared\Transfer\CurrentProductPriceTransfer $currentProductPriceTransfer
     * @param \Generated\Shared\Transfer\RestProductOfferPricesAttributesTransfer $restProductOfferPricesAttributesTransfer
     *
     * @return \Generated\Shared\Transfer\RestProductOfferPricesAttributesTransfer
     */
    public function mapCurrentProductPriceTransferToRestProductOfferPricesAttributesTransfer(
        CurrentProductPriceTransfer $currentProductPriceTransfer,
        RestProductOfferPricesAttributesTransfer $restProductOfferPricesAttributesTransfer
    ): RestProductOfferPricesAttributesTransfer;
}
