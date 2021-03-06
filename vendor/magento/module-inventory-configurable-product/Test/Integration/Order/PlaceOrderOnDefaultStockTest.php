<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProduct\Test\Integration\Order;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\DataObject;
use Magento\Framework\DataObject\Factory as DataObjectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\InventoryReservationsApi\Model\CleanupReservationsInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\Sales\Api\Data\ShipmentItemCreationInterface;
use Magento\Sales\Api\Data\ShipmentItemCreationInterfaceFactory;
use Magento\Sales\Api\ShipOrderInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PlaceOrderOnDefaultStockTest extends TestCase
{
    /**
     * @var CleanupReservationsInterface
     */
    private $cleanupReservations;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var CartManagementInterface
     */
    private $cartManagement;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var OrderManagementInterface
     */
    private $orderManagement;

    /**
     * @var ShipOrderInterface
     */
    private $shipOrder;

    /**
     * @var ShipmentItemCreationInterfaceFactory
     */
    private $shipmentItemCreationFactory;

    /**
     * @var StockRegistryInterface
     */
    private $stockRegistry;

    /**
     * @var DefaultStockProviderInterface
     */
    private $defaultStockProvider;

    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;

    protected function setUp(): void
    {
        $this->registry = Bootstrap::getObjectManager()->get(Registry::class);
        $this->cartManagement = Bootstrap::getObjectManager()->get(CartManagementInterface::class);
        $this->productRepository = Bootstrap::getObjectManager()->get(ProductRepositoryInterface::class);
        $this->cartRepository = Bootstrap::getObjectManager()->get(CartRepositoryInterface::class);
        $this->searchCriteriaBuilder = Bootstrap::getObjectManager()->get(SearchCriteriaBuilder::class);
        $this->cleanupReservations = Bootstrap::getObjectManager()->get(CleanupReservationsInterface::class);
        $this->orderRepository = Bootstrap::getObjectManager()->get(OrderRepositoryInterface::class);
        $this->orderManagement = Bootstrap::getObjectManager()->get(OrderManagementInterface::class);
        $this->dataObjectFactory = Bootstrap::getObjectManager()->get(DataObjectFactory::class);
        $this->shipOrder = Bootstrap::getObjectManager()->get(ShipOrderInterface::class);
        $this->shipmentItemCreationFactory = Bootstrap::getObjectManager()
            ->get(ShipmentItemCreationInterfaceFactory::class);
        $this->stockRegistry = Bootstrap::getObjectManager()->get(StockRegistryInterface::class);
        $this->defaultStockProvider = Bootstrap::getObjectManager()->get(DefaultStockProviderInterface::class);
        $this->cleanupReservations->execute();
    }

    /**
     * @magentoDataFixture Magento_InventoryConfigurableProduct::Test/_files/default_stock_configurable_products.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/quote.php
     */
    public function testPlaceOrderWithInStockProduct()
    {
        $sku = 'configurable_in_stock';
        $qty = 4;

        $product = $this->productRepository->get($sku);
        $quote = $this->getQuote();

        $quote->addProduct($product, $this->getByRequest($product, $qty));
        $this->cartRepository->save($quote);
        $orderId = $this->cartManagement->placeOrder($quote->getId());

        self::assertNotNull($orderId);

        //cleanup
        $this->deleteOrderById((int)$orderId);
    }

    /**
     * @magentoDataFixture Magento_InventoryConfigurableProduct::Test/_files/default_stock_configurable_products.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/quote.php
     * @magentoConfigFixture current_store cataloginventory/item_options/backorders 1
     */
    public function testPlaceOrderWithOutOffStockProductAndBackOrdersTurnedOn()
    {
        $sku = 'configurable_out_of_stock';
        $qty = 8;

        $product = $this->productRepository->get($sku);
        $quote = $this->getQuote();
        self::expectExceptionMessage('Product that you are trying to add is not available.');
        $quote->addProduct($product, $this->getByRequest($product, $qty));
        $this->cartRepository->save($quote);
        $orderId = $this->cartManagement->placeOrder($quote->getId());

        self::assertNotNull($orderId);

        //cleanup
        $this->deleteOrderById((int)$orderId);
    }

    /**
     * @magentoDataFixture Magento_InventoryConfigurableProduct::Test/_files/default_stock_configurable_products.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/quote.php
     */
    public function testPlaceOrderWithOutOffStockProduct()
    {
        $sku = 'configurable_out_of_stock';
        $qty = 8;

        $quote = $this->getQuote();
        $product = $this->productRepository->get($sku);

        self::expectException(LocalizedException::class);
        $quote->addProduct($product, $this->getByRequest($product, $qty));
        $this->cartRepository->save($quote);
        $orderId = $this->cartManagement->placeOrder($quote->getId());

        self::assertNull($orderId);
    }

    /**
     * @magentoDataFixture Magento_InventoryConfigurableProduct::Test/_files/disable_config_use_manage_stock.php
     * @magentoDataFixture Magento_InventoryConfigurableProduct::Test/_files/default_stock_configurable_products.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/quote.php
     */
    public function testPlaceOrderWithOutOffStockProductAndManageStockTurnedOff()
    {
        $sku = 'configurable_out_of_stock';
        $qty = 8;

        $product = $this->productRepository->get($sku);
        $quote = $this->getQuote();
        $quote->addProduct($product, $this->getByRequest($product, $qty));
        $this->cartRepository->save($quote);
        $orderId = $this->cartManagement->placeOrder($quote->getId());

        self::assertNotNull($orderId);

        //cleanup
        $this->deleteOrderById((int)$orderId);
    }

    /**
     * @magentoDataFixture Magento_InventoryConfigurableProduct::Test/_files/default_stock_configurable_products.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/quote.php
     *
     * @return void
     */
    public function testPlaceOrderToMakeConfigurableProductOutOfStock(): void
    {
        $skuConfigurable = ['configurable_in_stock'];
        $skuConfigurableChildren = ['simple_10', 'simple_20'];
        $qty = 100;
        $items = [];
        $quote = $this->getQuote();
        foreach ($skuConfigurableChildren as $sku) {
            $product = $this->productRepository->get($sku);
            $byRequest = $this->dataObjectFactory->create(
                [
                    'product' => $product->getId(),
                    'qty' => $qty
                ]
            );
            $quote->addProduct($product, $byRequest);
        }
        $this->cartRepository->save($quote);
        $orderId = $this->cartManagement->placeOrder($quote->getId());
        $order = $this->orderRepository->get($orderId);
        foreach ($order->getItems() as $orderItem) {
            /** @var ShipmentItemCreationInterface $invoiceItemCreation */
            $shipmentItemCreation = $this->shipmentItemCreationFactory->create();
            $shipmentItemCreation->setOrderItemId($orderItem->getItemId());
            $shipmentItemCreation->setQty($orderItem->getQtyOrdered());
            $items[] = $shipmentItemCreation;
        }
        $this->shipOrder->execute($order->getEntityId(), $items);
        foreach (array_merge($skuConfigurable, $skuConfigurableChildren) as $sku) {
            $productsStockStatus = $this->stockRegistry->getProductStockStatusBySku(
                $sku,
                $this->defaultStockProvider->getId()
            );
            self::assertEquals(SourceItemInterface::STATUS_OUT_OF_STOCK, $productsStockStatus);
        }
        //cleanup
        $this->deleteOrderById((int)$orderId);
    }

    /**
     * @return CartInterface
     */
    private function getQuote(): CartInterface
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('reserved_order_id', 'test_order_1')
            ->create();
        /** @var CartInterface $cart */
        $cart = current($this->cartRepository->getList($searchCriteria)->getItems());
        $cart->setStoreId(1);

        return $cart;
    }

    /**
     * @param ProductInterface $product
     * @param float $productQty
     *
     * @return DataObject
     */
    private function getByRequest(ProductInterface $product, float $productQty): DataObject
    {
        $configurableOptions = $product->getTypeInstance()->getConfigurableOptions($product);
        $option = current(current($configurableOptions));

        return $this->dataObjectFactory->create(
            [
                'product' => $option['product_id'],
                'super_attribute' => [key($configurableOptions) => $option['value_index']],
                'qty' => $productQty
            ]
        );
    }

    /**
     * @param int $orderId
     */
    private function deleteOrderById(int $orderId)
    {
        $this->registry->unregister('isSecureArea');
        $this->registry->register('isSecureArea', true);
        $this->orderManagement->cancel($orderId);
        $this->orderRepository->delete($this->orderRepository->get($orderId));
        $this->registry->unregister('isSecureArea');
        $this->registry->register('isSecureArea', false);
    }

    protected function tearDown(): void
    {
        $this->cleanupReservations->execute();
    }
}
