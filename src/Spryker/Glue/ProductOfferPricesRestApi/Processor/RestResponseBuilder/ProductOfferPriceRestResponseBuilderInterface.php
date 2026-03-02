<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\ProductOfferPricesRestApi\Processor\RestResponseBuilder;

use Generated\Shared\Transfer\CurrentProductPriceTransfer;
use Spryker\Glue\GlueApplication\Rest\JsonApi\RestResourceInterface;
use Spryker\Glue\GlueApplication\Rest\JsonApi\RestResponseInterface;

interface ProductOfferPriceRestResponseBuilderInterface
{
    public function createProductOfferIdNotSpecifierErrorResponse(): RestResponseInterface;

    public function createProductOfferAvailabilityEmptyRestResponse(): RestResponseInterface;

    public function createProductOfferAvailabilityRestResponse(RestResourceInterface $productOfferPriceRestResource): RestResponseInterface;

    public function createProductOfferPriceRestResource(
        CurrentProductPriceTransfer $currentProductPriceTransfer,
        string $productOfferReference
    ): RestResourceInterface;

    public function createProductOfferNotFoundErrorResponse(): RestResponseInterface;
}
