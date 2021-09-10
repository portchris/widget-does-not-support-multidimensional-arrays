<?php

namespace Convert\WidgetDoesNotSupportMultidimensionalArrays\Helper;

use Magento\Bundle\Model\Product\Type as ProductBundleType;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ProductConfigurableType;
use Magento\Downloadable\Model\Product\Type as DownloadableProduct;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ResourceConnectionFactory;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\UrlInterface;
use Magento\GroupedProduct\Model\Product\Type\Grouped as ProductGroupedType;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Model\Quote\Item;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Zend_Db_Expr;

class Data extends AbstractHelper
{
    /** @var string */
    const CONFIG_PATH_GAEE_GTM_CONTAINER_ID = 'google/gaee/container_id';

    /** @var string */
    const CONFIG_PATH_GAEE_EVENTS_ENABLED = 'google/gaee/events_enabled';

    /** @var string */
    const CONFIG_PATH_TEST_BATCH_DATA_LIMIT = 'google/gaee/data_batch_limit';

    /** @var string */
    const CONFIG_PATH_GAEE_CATEGORY_DELIMITER_ENABLED = 'google/gaee/category_delimiter_enabled';

    /** @var string */
    const CONFIG_PATH_GAEE_CATEGORY_PATH_DELIMITER = 'google/gaee/category_path_delimiter';

    /** @var string */
    const CONFIG_PATH_GAEE_CATEGORY_SEPARATOR_DELIMITER = 'google/gaee/category_separator_delimiter';

    /** @var string */
    const TEST_VIEW = 'page_view';

    /** @var string */
    const TEST_VIEW_LIST = 'view_item_list';

    /** @var string */
    const TEST_VIEW_ITEM = 'view_item';

    /** @var string */
    const TEST_SELECT_CONTENT = 'select_content';

    /** @var string */
    const TEST_CART_ADD = 'add_to_cart';

    /** @var string */
    const TEST_CART_REMOVE = 'remove_from_cart';

    /** @var string */
    const TEST_CART_VIEW = 'view_cart';

    /** @var string */
    const TEST_CHECKOUT_START = 'begin_checkout';

    /** @var string */
    const TEST_CHECKOUT_SHIPPING = 'add_shipping_info';

    /** @var string */
    const TEST_CHECKOUT_PAYMENT = 'add_payment_info';

    /** @var string */
    const TEST_PROMO_VIEW = 'view_promotion';

    /** @var string */
    const TEST_PROMO_CLICK = 'select_promotion';

    /** @var string */
    const TEST_ORDER_REFUND = 'refund';

    /** @var string */
    const TEST_ORDER_REFUND_VIEW = 'view_refund';

    /** @var string */
    const TEST_ORDER_PURCHASE = 'purchase';

    /** @var string */
    const TEST_LOGIN = 'login';

    /** @var string */
    const TEST_LOGOUT = 'logout';

    /** @var string */
    const TEST_SIGN_UP = 'sign_up';

    /** @var string */
    const TEST_NEWSLETTER_SUBSCRIBER_ADD = 'join_group';

    /** @var string */
    const TEST_NEWSLETTER_SUBSCRIBER_REMOVE = 'remove_from_group';

    /** @var string */
    const TEST_CURRENCY = 'currency';

    /** @var string */
    const GAEE_OBSERVER_CLICK = 'click';

    /** @var string */
    const GAEE_OBSERVER_SCROLL = 'scroll';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var SerializerInterface
     */
    protected $serialiser;

    /**
     * @var ResourceConnectionFactory
     */
    private $_resourceConnectionFactory;

    /**
     * Constructor.
     *
     * @param Context $context
     * @param SerializerInterface $serialiser
     * @param StoreManagerInterface $storeManager
     * @param ProductRepositoryInterface $productRepository
     * @param ResourceConnectionFactory $resourceConnectionFactory
     */
    public function __construct(
        Context $context,
        SerializerInterface $serialiser,
        StoreManagerInterface $storeManager,
        ProductRepositoryInterface $productRepository,
        ResourceConnectionFactory $resourceConnectionFactory
    ) {
        parent::__construct(
            $context
        );
        $this->serialiser = $serialiser;
        $this->storeManager = $storeManager;
        $this->urlBuilder = $context->getUrlBuilder();
        $this->scopeConfig = $context->getScopeConfig();
        $this->productRepository = $productRepository;
        $this->_resourceConnectionFactory = $resourceConnectionFactory;
    }

    /**
     * @return mixed
     */
    public function configValue(string $configField, string $scope = null)
    {
        $scope = (is_null($scope)) ? ScopeInterface::SCOPE_STORE : $scope;
        return $this->scopeConfig->getValue(
            $configField,
            $scope
        );
    }

    /**
     * @return ProductRepositoryInterface
     */
    public function returnProductRepository(): ProductRepositoryInterface
    {
        return $this->productRepository;
    }

    /**
     * Create a new GTM event action
     * @param string $event
     * @param array $data
     * @return array
     */
    public function newAction(string $event, array $data): array
    {
        return [
            'event' => $event,
            'ecommerce' => $data
        ];
    }

    /**
     * Create a new GTM event customer action
     * @param string $email
     * @param array $name
     * @param array $method
     * @return array
     */
    public function newActionCustomer(string $email, string $name, string $method): array
    {
        return [
            'name' => $name,
            'email' => $email,
            'method' => $method
        ];
    }

    /**
     * Create a new GTM event newsletter subscriber action
     * @param string $email
     * @param array $name
     * @param array $groupId
     * @return array
     */
    public function newActionSubscriber(string $email, string $name, string $groupId): array
    {
        return [
            'name' => $name,
            'email' => $email,
            'group_id' => $groupId
        ];
    }

    /**
     * Create a new GTM event item action
     * @param Product $product
     * @param string $itemListName
     * @param int $categoryId
     * @param int $categoryPath
     * @param int $position
     * @param float $qty
     * @return array
     */
    public function newActionItem(
        ProductInterface $product,
        string $itemListName,
        ?int $categoryId = null,
        ?string $categoryPath = null,
        ?int $position = null,
        ?float $qty = null
    ): array {
        $data = [
            'item_name' => $product->getName(),
            'item_list_name' => $itemListName,
            'item_id' => $product->getSku(),
            'item_list_id' => $product->getSku(),
            'price' => $product->getFinalPrice(),
            'quantity' => ($qty) ? $qty : 1
        ];

        // Position in category (if applicable)
        if ($position) {
            $data['index'] = $position;
        }

        // Brand (if applicable)
        if ($product->getManufacturer()) {
            $data['item_brand'] = $product->getManufacturer();
        }

        // List Item ID
        if ($categoryId) {
            $data['item_list_id'] = $categoryId;
        }

        // Product Categories
        if (!$categoryPath) {
            $categories = $this->allCategoryPaths($product);
            $c = count($categories);
            if ($c) {
                for ($i = 0; $i < $c; $i++) {
                    $cI = ($i === 0)
                        ? ""
                        : (string) $i;

                    $category = $categories[$i];
                    $data['category' . $cI] = $category;
                }
            }
        } else {
            $data['category'] = $categoryPath;
        }

        // Variant
        $variants = $this->newActionVarientFromProduct($product);
        if (strlen($variants)) {
            $data['item_variant'] = $variants;
        }

        return $data;
    }

    /**
     * Create a new GTM event cart/quote item action
     * @param Item $item
     * @param string $itemListName
     * @param int $categoryId
     * @param string $categoryPath
     * @param int $position
     * @return array
     */
    public function newActionCartItem(
        CartItemInterface $item,
        string $itemListName,
        ?int $categoryId = null,
        ?string $categoryPath = null,
        ?int $position = null
    ): array {

        /**
         * @var Product $product
         */
        $product = $item->getProduct();
        $price = (is_null($item->getRowTotalInclTax()))
            ? $product->getFinalPrice()
            : $item->getRowTotalInclTax();

        $data = [
            'item_name' => $item->getName(),
            'item_list_name' => $itemListName,
            'item_id' => $item->getSku(),
            'item_list_id' => $item->getSku(),
            'price' => $price,
            'quantity' => $item->getQty()
        ];

        // Position in category (if applicable)
        if ($position) {
            $data['index'] = $position;
        }

        // Brand (if applicable)
        if ($product->getManufacturer()) {
            $data['item_brand'] = $product->getManufacturer();
        }

        // List Item ID
        if ($categoryId) {
            $data['item_list_id'] = $categoryId;
        }

        // Variants
        $variants = $this->newActionVarientFromCartItem($item);
        if (strlen($variants)) {
            $data['item_variant'] = $variants;
        }

        // Product Categories
        if (!$categoryPath) {
            $categories = $this->allCategoryPaths($product);
            $c = count($categories);
            if ($c) {
                for ($i = 0; $i < $c; $i++) {
                    $cI = ($i === 0)
                        ? ""
                        : (string) $i;

                    $category = $categories[$i];
                    $data['category' . $cI] = $category;
                }
            }
        } else {
            $data['category'] = $categoryPath;
        }

        return $data;
    }

    /**
     * Create a new GTM event promotional item action
     * @param Product $product
     * @param string $itemListName
     * @param string $promoName
     * @param string $promoLabel
     * @param int $promoId
     * @param string $element
     * @param int $categoryId
     * @param string $categoryPath
     * @param int $position
     * @param float $qty
     * @return array
     */
    public function newActionPromoItem(
        ProductInterface $product,
        string $itemListName,
        string $promoName,
        string $promoLabel,
        int $promoId,
        string $element,
        ?int $categoryId = null,
        ?string $categoryPath = null,
        ?int $position = null,
        ?float $qty = null
    ): array {
        $data = [
            'item_name' => $product->getName(),
            'item_list_name' => $itemListName,
            'item_id' => $product->getSku(),
            'item_list_id' => $product->getSku(),
            'price' => $product->getFinalPrice(),
            'quantity' => ($qty) ? $qty : 1,
            'promotion_id' => $promoId,
            'promotion_name' => $promoName,
            'creative_name' => $promoLabel,
            'location_id' => $element
        ];

        // Position in category (if applicable)
        if ($position) {
            $data['index'] = $position;
        }

        // Brand (if applicable)
        if ($product->getManufacturer()) {
            $data['item_brand'] = $product->getManufacturer();
        }

        // List Item ID
        if ($categoryId) {
            $data['item_list_id'] = $categoryId;
        }

        // Product Categories
        if (!$categoryPath) {
            $categories = $this->allCategoryPaths($product);
            $c = count($categories);
            if ($c) {
                for ($i = 0; $i < $c; $i++) {
                    $cI = ($i === 0)
                        ? ""
                        : (string) ($i + 1);

                    $category = $categories[$i];
                    $data['category' . $cI] = $category;
                }
            }
        } else {
            $data['category'] = $categoryPath;
        }

        return $data;
    }

    /**
     * Get the GTM Variant
     * @param Product $product
     * @return string
     */
    public function newActionVarientFromProduct(ProductInterface $product): string
    {
        $variants = "";
        $delimiter = $this->configValue(static::CONFIG_PATH_GAEE_CATEGORY_SEPARATOR_DELIMITER);
        if (!$this->isGroupedProduct($product) && !$this->isConfigurableProduct($product) && !$this->isBundleProduct($product) && $this->isChildOfParent($product)) {
            $parentIds = $product->getTypeInstance()->getParentIdsByChild($product->getId());
            $pId = (int) $parentIds[0]; // For now, only use the first parent
            if ($pId !== 0) {
                try {
                    $parent = $this
                        ->returnProductRepository()
                        ->getById($pId);

                    $typeInstance = $product->getTypeInstance();
                    if ($product->isBundleProduct($product)) {

                        /**
                         * @var ProductBundleType $typeInstance
                         */
                        $bundleOptions = $typeInstance->getOptionsCollection($parent);
                        if ($bundleOptions->getSize()) {
                            $items = $bundleOptions->getItems();
                            foreach ($items as $option) {
                                if ($option->getSku() === $product->getSku()) {
                                    $variants .= $option->getTitle() . $delimiter;
                                }
                            }
                        }
                        if (strlen($variants)) {
                            $data['item_variant'] = trim($variants, $delimiter);
                        }
                    } elseif ($this->isConfigurableProduct($product)) {

                        /**
                         * @var ProductConfigurableType $typeInstance
                         */
                        $variants = $typeInstance->getConfigurableAttributesAsArray($parent);
                        $variations = "";
                        foreach ($variants as $attrCode => $value) {
                            if (isset($value['label']) && strlen((string) $value['label']) && (string) $product->getData($attrCode) === (string) $value['label']) {
                                $variations .= (string) $value['label'] . $delimiter;
                            }
                        }
                        if (strlen($variations)) {
                            $data['item_variant'] = trim($variations, $delimiter);
                        }
                    }
                } catch (NoSuchEntityException $e) {
                    $this->_logger->critical(__("[%1] ERROR: %2", static::class, $e->getMessage()));
                }
            }
        }

        return trim($variants, $delimiter);
    }

    /**
     * Get GTM event item variant based on cart/quote item
     * @param Item $item
     * @return string
     */
    public function newActionVarientFromCartItem(CartItemInterface $item): string
    {
        $variants = [];
        $delimiter = $this->configValue(static::CONFIG_PATH_GAEE_CATEGORY_SEPARATOR_DELIMITER);
        $options = $item->getOptions();
        if (count($options)) {
            foreach ($options as $option) {
                if ($option->getCode() === 'attributes') {
                    $values = $this->serialiser->unserialize($option->getValue());
                    foreach ($values as $attrId => $attrOptId) {
                        $connection = $this->newResourceConnection();
                        $t1 = $connection->getTableName('eav_attribute_option');
                        $t2 = $connection->getTableName('eav_attribute_option_value');
                        $w1 = $connection->prepareSqlCondition('eao.attribute_id', ['eq' => $attrId]);
                        $w2 = $connection->prepareSqlCondition('eaov.option_id', ['eq' => $attrOptId]);
                        $w3 = $connection->prepareSqlCondition('eaov.store_id', ['in' => [0, $item->getStoreId()]]);
                        $select = $connection
                            ->select()
                            ->from(['eao' => $t1], 'eaov.value')
                            ->joinInner(['eaov' => $t2], "eao.option_id=eaov.option_id")
                            ->where(implode(' AND ', [$w1, $w2, $w3]))
                            ->order(new Zend_Db_Expr('eaov.store_id' . ' ' . SortOrder::SORT_DESC));

                        // var_dump($select->__toString());
                        $variants[] = (string) $connection->fetchOne($select);
                    }
                }
            }
        }

        return implode($delimiter, $variants);
    }

    /**
     * Get all category paths belonging to this product delimited.
     *
     * @param Product $product
     * @param bool $doBreadcrumbTrail
     * @return array $allCategories
     */
    public function allCategoryPaths(Product $product): array
    {
        $allCategories = $this->fullCategoryPathsOfProduct($product);
        $delimiter = $this->configValue(static::CONFIG_PATH_GAEE_CATEGORY_PATH_DELIMITER);
        $doBreadcrumbTrail = filter_var($this->configValue(static::CONFIG_PATH_GAEE_CATEGORY_DELIMITER_ENABLED), FILTER_VALIDATE_BOOLEAN);
        if ($doBreadcrumbTrail) {

            // Return a list of categories full paths from tree root belonging to this product whilst avoiding duplicates
            return $allCategories;
        } else {
            $r = [];
            foreach ($allCategories as $categoryPaths) {
                $categories = explode($delimiter, $categoryPaths);
                $c = count($categories) - 1;
                if (!in_array($categories[$c], $r)) {
                    $r[] = $categories[$c];
                }
            }

            // Return the final category in the tree belonging to this product whilst avoiding duplicates
            return $r;
        }
    }

    /**
     * @param Product $product
     * @return array
     */
    public function fullCategoryPathsOfProduct(Product $product): array
    {
        $paths = [];

        /** @var \Magento\Catalog\Model\ResourceModel\Category\Collection $categoryCollection */
        $categoryCollection = $product->getCategoryCollection();
        $categoryCollection
            ->addAttributeToSelect(['entity_id', Category::KEY_NAME])
            ->setOrder(Category::KEY_LEVEL, SortOrder::SORT_DESC)
            ->addFieldToFilter(Category::KEY_IS_ACTIVE, 1);

        if ($categoryCollection->getSize()) {

            /** @var Category $category */
            foreach ($categoryCollection->getItems() as $category) {
                $fullPath = $this->fullCategoryPath($category);
                if (strlen($fullPath)) {
                    $paths[] = $fullPath;
                }
            }
        }

        return $paths;
    }

    /**
     * Get the full path of this category up to the root tree node.
     *
     * @param Category $category
     * @return string
     */
    public function fullCategoryPath(CategoryInterface $category): string
    {
        $path = [];
        $paths = "";
        $pathInStore = $category->getPathInStore();
        $pathIds = array_reverse(explode(',', $pathInStore));
        $categories = $category->getParentCategories();
        $delimiter = $this->configValue(static::CONFIG_PATH_GAEE_CATEGORY_PATH_DELIMITER);

        // Add category path breadcrumb
        foreach ($pathIds as $catId) {
            if (isset($categories[$catId]) && is_object($categories[$catId]) && $categories[$catId]->getName()) {
                $path[] = $categories[$catId]->getName();
            }
        }

        $categoryName = $category->getName();
        if (strlen($categoryName) && !in_array($categoryName, $path)) {
            $path[] = $categoryName;
        }

        if (count($path)) {
            $paths = implode($delimiter, array_unique($path));
        }

        return $paths;
    }

    /**
     * Create a new connection to the database
     * @return AdapterInterface
     */
    public function newResourceConnection(): AdapterInterface
    {
        return $this->_resourceConnectionFactory
            ->create()
            ->getConnection();
    }


    /**
     * @return StoreManagerInterface
     */
    public function returnStoreManager(): StoreManagerInterface
    {
        return $this->storeManager;
    }

    /**
     * @return UrlInterface
     */
    public function returnUrlBuilder(): UrlInterface
    {
        return $this->urlBuilder;
    }

    /**
     * @param Product $product
     * @return bool
     */
    public function isBundleProduct(ProductInterface $product): bool
    {
        return $product && $product->getTypeId() === ProductBundleType::TYPE_CODE;
    }

    /**
     * @param Product $product
     * @return bool
     */
    public function isConfigurableProduct(ProductInterface $product): bool
    {
        $isConfigurable = $product && $product->getTypeId() === ProductConfigurableType::TYPE_CODE;
        return (!$isConfigurable && $product->getTypeInstance() instanceof ProductConfigurableType && !empty($product->getTypeInstance()->getChildrenIds($product->getId())))
            ? true
            : $isConfigurable;
    }

    /**
     * @param Product $product
     * @return bool
     */
    public function isGroupedProduct(ProductInterface $product): bool
    {
        return $product && $product->getTypeId() === ProductGroupedType::TYPE_CODE;
    }

    /**
     * @param Product $product
     * @return bool
     */
    public function isDownloadableProduct(ProductInterface $product): bool
    {
        return $product && $product->getTypeId() === DownloadableProduct::TYPE_DOWNLOADABLE;
    }

    /**
     * @param Product $product
     * @return bool
     */
    public function isChildOfParent(ProductInterface $product): bool
    {
        return count((array) $product->getTypeInstance()->getParentIdsByChild($product->getId())) > 0;
    }

    /**
     * @return int|null
     */
    public function isOnProductViewPage(): ?int
    {
        return $this->_request->getActionName() === 'catalog_product_view'
            ? (int) $this->_request->getParam('id')
            : null;
    }

    /**
     * @return int|null
     */
    public function isOnCategoryViewPage(): ?int
    {
        return $this->_request->getActionName() === 'catalog_category_view'
            ? (int) $this->_request->getParam('id')
            : null;
    }
    /**
     * Get exchange rate code in in ISO-4217 format
     *
     * @return string
     */
    public function gtmDisplayCurrency(): string
    {
        /**
         * @var Store $store
         */
        $store = $this->storeManager->getStore();
        return $store
            ->getCurrentCurrency()
            ->getCode();
    }

    /**
     * Get current language
     *
     * @return array
     */
    public function gtmLanguage(): string
    {
        return (string) $this->configValue('general/locale/code');
    }

    /**
     * Get current store code
     *
     * @param Store $store
     * @return array
     */
    public function gtmAvailabilityZone(): string
    {
        return $this->storeManager
            ->getStore()
            ->getCode();
    }
}
