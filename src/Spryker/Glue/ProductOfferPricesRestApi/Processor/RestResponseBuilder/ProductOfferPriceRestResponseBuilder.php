<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\ProductOfferPricesRestApi\Processor\RestResponseBuilder;

use Generated\Shared\Transfer\CurrentProductPriceTransfer;
use Generated\Shared\Transfer\RestErrorMessageTransfer;
use Generated\Shared\Transfer\RestProductOfferPricesAttributesTransfer;
use Spryker\Glue\GlueApplication\Rest\JsonApi\RestLinkInterface;
use Spryker\Glue\GlueApplication\Rest\JsonApi\RestResourceBuilderInterface;
use Spryker\Glue\GlueApplication\Rest\JsonApi\RestResourceInterface;
use Spryker\Glue\GlueApplication\Rest\JsonApi\RestResponseInterface;
use Spryker\Glue\ProductOfferPricesRestApi\Processor\Mapper\ProductOfferPriceMapperInterface;
use Spryker\Glue\ProductOfferPricesRestApi\ProductOfferPricesRestApiConfig;
use Symfony\Component\HttpFoundation\Response;

class ProductOfferPriceRestResponseBuilder implements ProductOfferPriceRestResponseBuilderInterface
{
    /**
     * @var \Spryker\Glue\GlueApplication\Rest\JsonApi\RestResourceBuilderInterface
     */
    protected $restResourceBuilder;

    /**
     * @var \Spryker\Glue\ProductOfferPricesRestApi\Processor\Mapper\ProductOfferPriceMapperInterface
     */
    protected $productOfferPriceMapper;

    public function __construct(
        RestResourceBuilderInterface $restResourceBuilder,
        ProductOfferPriceMapperInterface $productOfferPriceMapper
    ) {
        $this->restResourceBuilder = $restResourceBuilder;
        $this->productOfferPriceMapper = $productOfferPriceMapper;
    }

    public function createProductOfferAvailabilityEmptyRestResponse(): RestResponseInterface
    {
        return $this->restResourceBuilder->createRestResponse();
    }

    public function createProductOfferAvailabilityRestResponse(RestResourceInterface $productOfferPriceRestResource): RestResponseInterface
    {
        return $this->restResourceBuilder->createRestResponse()->addResource($productOfferPriceRestResource);
    }

    public function createProductOfferPriceRestResource(
        CurrentProductPriceTransfer $currentProductPriceTransfer,
        string $productOfferReference
    ): RestResourceInterface {
        $restProductOfferPricesAttributesTransfer = $this->productOfferPriceMapper
            ->mapCurrentProductPriceTransferToRestProductOfferPricesAttributesTransfer(
                $currentProductPriceTransfer,
                new RestProductOfferPricesAttributesTransfer(),
            );

        $productOfferPriceRestResource = $this->restResourceBuilder->createRestResource(
            ProductOfferPricesRestApiConfig::RESOURCE_PRODUCT_OFFER_PRICES,
            $productOfferReference,
            $restProductOfferPricesAttributesTransfer,
        );

        $productOfferPriceRestResource->addLink(
            RestLinkInterface::LINK_SELF,
            sprintf(
                '%s/%s/%s',
                ProductOfferPricesRestApiConfig::RESOURCE_PRODUCT_OFFERS,
                $productOfferReference,
                ProductOfferPricesRestApiConfig::RESOURCE_PRODUCT_OFFER_PRICES,
            ),
        );

        return $productOfferPriceRestResource;
    }

    public function createProductOfferIdNotSpecifierErrorResponse(): RestResponseInterface
    {
        $restErrorMessageTransfer = (new RestErrorMessageTransfer())
            ->setCode(ProductOfferPricesRestApiConfig::RESPONSE_CODE_PRODUCT_OFFER_ID_IS_NOT_SPECIFIED)
            ->setDetail(ProductOfferPricesRestApiConfig::RESPONSE_DETAIL_PRODUCT_OFFER_ID_SKU_IS_NOT_SPECIFIED)
            ->setStatus(Response::HTTP_BAD_REQUEST);

        return $this->restResourceBuilder->createRestResponse()->addError($restErrorMessageTransfer);
    }

    public function createProductOfferNotFoundErrorResponse(): RestResponseInterface
    {
        $restErrorMessageTransfer = (new RestErrorMessageTransfer())
            ->setCode(ProductOfferPricesRestApiConfig::RESPONSE_CODE_PRODUCT_OFFER_NOT_FOUND)
            ->setDetail(ProductOfferPricesRestApiConfig::RESPONSE_DETAIL_PRODUCT_OFFER_NOT_FOUND)
            ->setStatus(Response::HTTP_NOT_FOUND);

        return $this->restResourceBuilder->createRestResponse()->addError($restErrorMessageTransfer);
    }
}
