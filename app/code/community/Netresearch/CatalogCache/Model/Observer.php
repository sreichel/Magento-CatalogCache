<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category   Mage
 * @package    Mage_Catalog
 * @copyright  Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Observer that handles clearance of product cache if it was sold
 *
 * @category   Netresearch
 * @package    Netresearch_CatalogCache
 * @author     Netresearch <info@netresearch.de>
 */
class Netresearch_CatalogCache_Model_Observer
{
    public function clearProductCache($observer)
    {
        return;
        
        $_product = $observer['item']->getProduct();
        if(trim(Mage::getStoreConfig('catalog/frontend/refresh_cache_when_stock_is')) == "") {
            $_product->cleanCache();
            return;
        }
        $_currentStock = $_product->getStockItem()->getQty();
        $_futureStock = $_currentStock - $observer['item']->getQty();
        $stocks = explode(',',','.Mage::getStoreConfig('catalog/frontend/refresh_cache_when_stock_is'));
        foreach($stocks as $stock) {
            $stock = trim($stock);
            if(
                $stock &&
                $_currentStock > $stock && $_futureStock <= $stock
            ) {
                $_product->cleanCache();
                return;
            }
        }
    }

    /**
     * Clean review-related product cache
     * 
     * @param  Varien_Event_Observer $observer
     * @return void
     */
    public function cleanReviewProductCache($observer)
    {
        return;

        $review = $observer->getEvent()->getObject();
        $productId = $review->getEntityPkValue();
        $product = Mage::getModel('catalog/product')->load(
            $productId
        );

        if ($product instanceof Mage_Catalog_Model_Product
            && $product->getId()) {
            $product->cleanCache();
        }
    }

    /**
     * Clean tag-related product cache
     * 
     * @param  Varien_Event_Observer $observer
     * @return void
     */
    public function cleanTagProductCache($observer)
    {
        $tag = $observer->getObject();
        if ($tag instanceof Mage_Tag_Model_Tag_Relation) {
            foreach($tag->getProductIds() as $productId) {
                Mage::app()->cleanCache(Mage_Catalog_Model_Product::CACHE_TAG.'_'.$productId);
            }
        }
    }

    ##################################################

    public function initBlockCache(Varien_Event_Observer $observer)
    {
        $block  = $observer->getEvent()->getBlock();
 
        if ($block instanceof Mage_Catalog_Block_Layer_View
        || $block instanceof Mage_Catalog_Block_Product_List
        || $block instanceof Mage_CatalogSearch_Block_Layer
        ) {
            $block->addData(array(
                'cache_lifetime'    => $this->_getCacheLifetime(),
                'cache_key'         => $this->_getCacheKey($block),
                'cache_tags'        => $this->_getCacheTags($block),
            ));
        }
    }

    ##################################################

    /**
     * Netresearch_CatalogCache_Block_Layer_View
     */
    private function _getCacheKey($block)
    {
        if (!$this->_isCacheActive()) {
            return $block->getCacheKey();
        }
        $_taxRateRequest = Mage::getModel('tax/calculation')->getRateRequest();
        $_customer = Mage::getSingleton('customer/session')->getCustomer();
        $this->_category = Mage::getSingleton('catalog/layer')->getCurrentCategory();
        $_page = $block->getPage();

        $toolbar = new Mage_Catalog_Block_Product_List_Toolbar();
        $cacheKey = get_class($block).'_'.
            /* Create different caches for different categories */
            $this->_category->getId().'_'.
            /* ... orders */
            $toolbar->getCurrentOrder().'_'.
            /* ... direction */
            $toolbar->getCurrentDirection().'_'.
            /* ... mode */
            $toolbar->getCurrentMode().'_'.
            /* ... page */
            $toolbar->getCurrentPage().'_'.
            /* ... items per page */
            $toolbar->getLimit().'_'.
            /* ... stores */
            Mage::App()->getStore()->getCode().'_'.
            /* ... currency */
            Mage::App()->getStore()->getCurrentCurrencyCode().'_'.
            /* ... customer groups */
            $_customer->getGroupId().'_'.
            $_taxRateRequest->getCountryId()."_".
            $_taxRateRequest->getRegionId()."_".
            $_taxRateRequest->getPostcode()."_".
            $_taxRateRequest->getCustomerClassId()."_".
            /* ... tags */
            Mage::registry('current_tag').'_'.
            '';
        /* ... layern navigation + search */
        foreach (Mage::app()->getRequest()->getParams() as $key => $value) {
            $cacheKey .= $key.'-'.$value.'_';
        }
        return $cacheKey;
    }

    /**
     * Netresearch_CatalogCache_Block_Layer_View
     */
    private function _getCacheTags($block)
    {
        if (!$this->_isCacheActive()) {
            return $block->getCacheTags();
        }

        $cacheTags = array(
            Mage_Catalog_Model_Category::CACHE_TAG,
            Mage_Catalog_Model_Category::CACHE_TAG.'_'.$this->_category->getId()
        );

        /* 1.9 already has cache tags set ! */
        if ($block instanceof Mage_Catalog_Block_Product_List) {
            $ids = $block->getLoadedProductCollection()->getAllIds();
            foreach ($ids as $id) {
                $cacheTags[] = Mage_Catalog_Model_Product::CACHE_TAG.'_'.$id;
            }
        }

        return $cacheTags;
    }
    ##################################################
    
    private function _isCacheActive()
    {
        if (!Mage::getStoreConfig('catalog/frontend/cache_list')) {
            return false;
        }

        /* if there are any messages dont read from cache to show them */
        if (Mage::getSingleton('core/session')->getMessages(true)->count() > 0) {
            return false;
        }
        return true;
    }


    public function _getCacheLifetime()
    {
        if ($this->_isCacheActive()) {
            return false;
        }
    }
}
