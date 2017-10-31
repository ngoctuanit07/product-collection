<?php

/**
 * @Author: Ngo Quang Cuong
 * @Date:   2017-11-01 00:49:07
 * @Last Modified by:   https://www.facebook.com/giaphugroupcom
 * @Last Modified time: 2017-11-01 01:40:33
 */

namespace PHPCuong\ProductCollection\Block;

use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableType;
use Magento\Catalog\Model\Product\Type;
use Magento\Downloadable\Model\Product\Type as DownloadableType;
use Magento\GroupedProduct\Model\Product\Type\Grouped as GroupedType;

class ProductList extends \Magento\Framework\View\Element\Template
{
    /**
     * Product collection factory
     *
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * Product Status
     *
     * @var \Magento\Catalog\Model\Product\Attribute\Source\Status
     */
    private $productStatus;

    /**
     * Product Visibility
     *
     * @var \Magento\Catalog\Model\Product\Visibility
     */
    private $productVisibility;

    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * ImageFactory
     *
     * @var \Magento\Catalog\Helper\ImageFactory
     */
    private $imageHelper;

    /**
     * Path to template file in theme.
     *
     * @var string
     */
    protected $_template = 'product/list.phtml';

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Magento\Catalog\Model\Product\Attribute\Source\Status $productStatus
     * @param \Magento\Catalog\Model\Product\Visibility $productVisibility
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Helper\ImageFactory $imageHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Catalog\Model\Product\Attribute\Source\Status $productStatus,
        \Magento\Catalog\Model\Product\Visibility $productVisibility,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Helper\ImageFactory $imageHelper,
        array $data = []
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->productStatus = $productStatus;
        $this->productVisibility = $productVisibility;
        $this->storeManager = $storeManager;
        $this->imageHelper = $imageHelper;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve the product list
     *
     * @return \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    public function getProductCollection()
    {
        $collection = $this->productCollectionFactory->create();
        // get all the product's attributes
        $collection = $collection->addAttributeToSelect('*');
        // filtering the products by status
        $collection = $collection->addAttributeToFilter('status', ['in' => $this->productStatus->getVisibleStatusIds()]);
        // filtering the products by visibility
        $collection = $collection->addAttributeToFilter('visibility', ['in' => $this->productVisibility->getVisibleInSiteIds()]);
        // filtering the products by the type id
        $collection = $collection->addAttributeToFilter('type_id', ['in' => $this->getProductTypeId()]);
        // filtering the products by the current store view
        $collection = $collection->addStoreFilter($this->storeManager->getStore()->getStoreId());
        /**
         * Add field filter to collection
         *
         * If $condition integer or string - exact value will be filtered ('eq' condition)
         *
         * If $condition is array - one of the following structures is expected:
         * <pre>
         * - ["from" => $fromValue, "to" => $toValue]
         * - ["eq" => $equalValue]
         * - ["neq" => $notEqualValue]
         * - ["like" => $likeValue]
         * - ["in" => [$inValues]]
         * - ["nin" => [$notInValues]]
         * - ["notnull" => $valueIsNotNull]
         * - ["null" => $valueIsNull]
         * - ["moreq" => $moreOrEqualValue]
         * - ["gt" => $greaterValue]
         * - ["lt" => $lessValue]
         * - ["gteq" => $greaterOrEqualValue]
         * - ["lteq" => $lessOrEqualValue]
         * - ["finset" => $valueInSet]
         * </pre>
         *
         * If non matched - sequential parallel arrays are expected and OR conditions
         * will be built using above mentioned structure.
         *
         * Example:
         * <pre>
         * $field = ['age', 'name'];
         * $condition = [42, ['like' => 'Mage']];
         * </pre>
         * The above would find where age equal to 42 OR name like %Mage%.
         */
        // only get the product has the SKU is 24-MB04
        //$collection = $collection->addFieldToFilter('sku', ['eq' => '24-MB04']);
        // only get the products are in stock
        $collection = $collection->joinField(
            'is_in_stock',
            'cataloginventory_stock_item',
            'is_in_stock',
            'product_id=entity_id',
            'is_in_stock=1',
            '{{table}}.stock_id=1',
            'left' // left join
        );
        // limit the results, only get 10 products every page
        $collection = $collection->setPageSize(10);
        // set the current page
        $collection = $collection->setCurPage(1);
        // set order by
        //$collection = $collection->setOrder('updated_at', 'DESC');
        // get total of products
        //$collection = $collection->getSize();
        // get total of pages
        //$collection = $collection->getLastPageNumber();
        // add attribute to sort
        $collection = $collection->addAttributeToSort('name', 'ASC');
        return $collection;
    }

    /**
     * Retrieve entityIds of all products
     *
     * @return array
     */
    public function getAllProductIds()
    {
        return $this->getProductCollection()->getAllIds();
    }

    /**
     * Retrieve collection products
     *
     * @return \Magento\Framework\DataObject[]
     */
    public function getProducts()
    {
        return $this->getProductCollection()->getItems();
    }

    /**
     * Retrieve collection last item
     *
     * @return \Magento\Framework\DataObject
     */
    public function getFirstProduct()
    {
        return $this->getProductCollection()->getFirstItem();
    }

    /**
     * Retrieve collection product item
     *
     * @return \Magento\Framework\DataObject
     */
    public function getLastProduct()
    {
        return $this->getProductCollection()->getLastItem();
    }

    /**
     * Retrieve the product image
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @return string
     */
    public function getProductImage($product)
    {
        return $this->imageHelper->create()
            ->init($product, 'product_page_image_small')
            ->setImageFile($product->getFile())
            ->resize(240, 300)->getUrl();
    }

    /**
     * Retrieve the product type id
     *
     * @return array
     */
    protected function getProductTypeId()
    {
        return [
            ConfigurableType::TYPE_CODE,
            Type::TYPE_SIMPLE,
            Type::TYPE_BUNDLE,
            Type::TYPE_VIRTUAL,
            DownloadableType::TYPE_DOWNLOADABLE,
            GroupedType::TYPE_CODE
        ];
    }
}
