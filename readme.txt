=== Stock Sync for WooCommerce Especially for Dukan Marketplace===
Contributors: wooelements
Tags: woocommerce, stock synchronization, shared stock
Requires at least: 4.5
Tested up to: 5.3
Requires PHP: 5.4
Stable tag: 1.2.2
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Sync stock quantities between two WooCommerce stores.

== Description ==

Stock Sync for WooCommerce allows you to share stock quantities and statuses between two WooCommerce stores. When someone purchases a product or you set stock quantity via admin edit screen, quantity will be instantly updated to the other store.

Alternatively, the plugin can also sync stock status if you are not managing stock quantities. In that case when you set product to be in stock or out of stock, the plugin will automatically sync the change to the other site.

The plugin uses WooCommerce built-in API to communicate between stores. It's as secure as WooCommerce.

= Features =

* Share stock quantities and statuses between two WooCommerce stores
* Instantly sync stock changes when a product is purchased, refunded or edited via admin screen
* Easily view which products are being synced
* Import all stock quantities from another store
* Uses WooCommerce built-in REST API for secure communication between stores
* Compatible with WooCommerce 3.5 or above

= Pro Features =

* Support for unlimited amount of products (WordPress.org free version maximum 100 products)
* Support for syncing between 2 - 4 stores (WordPress.org free version 2 stores)

[Upgrade to Pro](https://wooelements.com/products/stock-sync-pro)

= How to Use =

Please see [the documentation](https://wooelements.com/products/stock-sync-pro/guide).

= Support Policy =

If you need any help with the plugin, please create a new post on the [WordPress plugin support forum](https://wordpress.org/support/plugin/stock-sync-for-woocommerce). Priority email support is available for the Pro version.

= Other Useful Plugins =

Make sure to check out other useful plugins from the author.

* [Conditional Shipping for WooCommerce](https://wordpress.org/plugins/conditional-shipping-for-woocommerce)
* [Conditional Payments for WooCommerce](https://wordpress.org/plugins/conditional-payments-for-woocommerce)

== Installation ==
Stock Sync is installed just like any other WordPress plugin.

1. Download the plugin zip file
2. Go to Plugins in the WordPress admin panel
3. Click Add new and Upload plugin
4. Choose the downloaded zip file and upload it
5. Activate the plugin

Once the plugin is activated, you need to set up API credentials and import stock quantities from one store to the other. Please see [the documentation](https://wooelements.com/products/stock-sync-pro/guide).

== Changelog ==

= 1.2.2 =

* Added better logging about syntax error when confirming credentials in the settings

= 1.2.1 =

* Improved compatibility with servers which don't support PUT requests

= 1.2.0 =

* Added possibility to sync stock status in addition to stock quantity

= 1.1.3 =

* Added filter for 3rd party plugins to prevent syncing in certain situations
* Added settings link to the plugins page

= 1.1.2 =

* Added missing files from the last update

= 1.1.1 =

* Improved Stock Sync page in the WordPress admin
* Added support for the upcoming Pro version

= 1.1.0 =
* Added API credentials check to the settings page
* Added debug logging option
* Syncing will be now done immediately after stock changed. Before there could be a delay of a few seconds.
* First retry in case of a failed sync will be now done immediately and later retries after 10 seconds.

= 1.0.1 =
* Bug fixes

= 1.0.0 =
* Initial version
