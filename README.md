# Netresearch: Magento-CatalogCache

Caching for Magento Catalog.

To enable caching simply change the following XML-configuration in the file:

 `app/design/frontend/[[YOUR_PACKAGE]]/[[YOUR_SKIN]]/layout/catalog.xml`

Change

`<block type="catalog/product_view" name="product.info" template="catalog/product/view.phtml">`

to

`<block type="catalogcache/product_view" name="product.info" template="catalog/product/view.phtml">`

## Changelog

### 0.1.1
* Added currency to cache keys