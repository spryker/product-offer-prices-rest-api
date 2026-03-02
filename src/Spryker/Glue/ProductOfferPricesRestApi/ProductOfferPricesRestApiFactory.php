<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\ProductOfferPricesRestApi;

use Spryker\Glue\Kernel\AbstractFactory;
use Spryker\Glue\ProductOfferPricesRestApi\Dependency\Client\ProductOfferPricesRestApiToCurrencyClientInterface;
use Spryker\Glue\ProductOfferPricesRestApi\Dependency\Client\ProductOfferPricesRestApiToPriceProductClientInterface;
use Spryker\Glue\ProductOfferPricesRestApi\Dependency\Client\ProductOfferPricesRestApiToPriceProductStorageClientInterface;
use Spryker\Glue\ProductOfferPricesRestApi\Dependency\Client\ProductOfferPricesRestApiToProductOfferStorageClientInterface;
use Spryker\Glue\ProductOfferPricesRestApi\Dependency\Client\ProductOfferPricesRestApiToProductStorageClientInterface;
use Spryker\Glue\ProductOfferPricesRestApi\Processor\Builder\PriceProductFilterTransferBuilder;
use Spryker\Glue\ProductOfferPricesRestApi\Processor\Builder\PriceProductFilterTransferBuilderInterface;
use Spryker\Glue\ProductOfferPricesRestApi\Processor\Expander\ProductOfferPriceExpander;
use Spryker\Glue\ProductOfferPricesRestApi\Processor\Expander\ProductOfferPriceExpanderInterface;
use Spryker\Glue\ProductOfferPricesRestApi\Processor\Mapper\ProductOfferPriceMapper;
use Spryker\Glue\ProductOfferPricesRestApi\Processor\Mapper\ProductOfferPriceMapperInterface;
use Spryker\Glue\ProductOfferPricesRestApi\Processor\Reader\ProductOfferPriceReader;
use Spryker\Glue\ProductOfferPricesRestApi\Processor\Reader\ProductOfferPriceReaderInterface;
use Spryker\Glue\ProductOfferPricesRestApi\Processor\RestResponseBuilder\ProductOfferPriceRestResponseBuilder;
use Spryker\Glue\ProductOfferPricesRestApi\Processor\RestResponseBuilder\ProductOfferPriceRestResponseBuilderInterface;

/**
 * @method \Spryker\Glue\ProductOfferPricesRestApi\ProductOfferPricesRestApiConfig getConfig()
 */
class ProductOfferPricesRestApiFactory extends AbstractFactory
{
    public function createProductOfferPriceReader(): ProductOfferPriceReaderInterface
    {
        return new ProductOfferPriceReader(
            $this->getProductOfferStorageClient(),
            $this->getProductStorageClient(),
            $this->getPriceProductStorageClient(),
            $this->getPriceProductClient(),
            $this->createProductOfferPriceRestResponseBuilder(),
            $this->createPriceProductFilterTransferBuilder(),
        );
    }

    public function createPriceProductFilterTransferBuilder(): PriceProductFilterTransferBuilderInterface
    {
        return new PriceProductFilterTransferBuilder(
            $this->getCurrencyClient(),
        );
    }

    public function createProductOfferPriceRestResponseBuilder(): ProductOfferPriceRestResponseBuilderInterface
    {
        return new ProductOfferPriceRestResponseBuilder(
            $this->getResourceBuilder(),
            $this->createProductOfferPriceMapper(),
        );
    }

    public function createProductOfferPriceMapper(): ProductOfferPriceMapperInterface
    {
        return new ProductOfferPriceMapper(
            $this->getRestProductOfferPricesAttributesMapperPlugins(),
        );
    }

    public function createProductOfferPriceExpander(): ProductOfferPriceExpanderInterface
    {
        return new ProductOfferPriceExpander($this->createProductOfferPriceReader());
    }

    public function getPriceProductStorageClient(): ProductOfferPricesRestApiToPriceProductStorageClientInterface
    {
        return $this->getProvidedDependency(ProductOfferPricesRestApiDependencyProvider::CLIENT_PRICE_PRODUCT_STORAGE);
    }

    public function getProductOfferStorageClient(): ProductOfferPricesRestApiToProductOfferStorageClientInterface
    {
        return $this->getProvidedDependency(ProductOfferPricesRestApiDependencyProvider::CLIENT_PRODUCT_OFFER_STORAGE);
    }

    public function getProductStorageClient(): ProductOfferPricesRestApiToProductStorageClientInterface
    {
        return $this->getProvidedDependency(ProductOfferPricesRestApiDependencyProvider::CLIENT_PRODUCT_STORAGE);
    }

    public function getPriceProductClient(): ProductOfferPricesRestApiToPriceProductClientInterface
    {
        return $this->getProvidedDependency(ProductOfferPricesRestApiDependencyProvider::CLIENT_PRICE_PRODUCT);
    }

    public function getCurrencyClient(): ProductOfferPricesRestApiToCurrencyClientInterface
    {
        return $this->getProvidedDependency(ProductOfferPricesRestApiDependencyProvider::CLIENT_CURRENCY);
    }

    /**
     * @return array<\Spryker\Glue\ProductOfferPricesRestApiExtension\Dependency\Plugin\RestProductOfferPricesAttributesMapperPluginInterface>
     */
    public function getRestProductOfferPricesAttributesMapperPlugins(): array
    {
        return $this->getProvidedDependency(ProductOfferPricesRestApiDependencyProvider::PLUGINS_REST_PRODUCT_OFFER_PRICES_ATTRIBUTES_MAPPER);
    }
}
