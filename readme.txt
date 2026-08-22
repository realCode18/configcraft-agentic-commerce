=== ConfigCraft Agentic Commerce ===
Contributors: realcode18
Tags: woocommerce, artificial intelligence, product feed, catalog, ecommerce
Requires at least: 6.6
Tested up to: 7.0
Requires PHP: 7.4
Requires Plugins: woocommerce
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Audit WooCommerce product catalogs for AI discovery and agentic commerce readiness.

== Description ==

ConfigCraft Agentic Commerce finds incomplete or ambiguous WooCommerce product data that may prevent AI shopping assistants from understanding and recommending products.

The first MVP adds an AI Commerce screen under WooCommerce and audits the latest published products for:

* Product title and description quality.
* Price and featured image availability.
* Category, brand, SKU, and global identifier coverage.
* Product attributes and physical shipping data.
* Empty variable products.

Every product receives a score and a list of actionable findings. All analysis runs locally. This version does not contact external services and does not collect telemetry.

Important: The plugin improves catalog data quality. It does not guarantee placement, ranking, recommendation, or legal compliance on any third-party AI platform.

== Installation ==

1. Install and activate WooCommerce.
2. Upload the plugin directory to `/wp-content/plugins/` or install the ZIP through Plugins > Add New.
3. Activate ConfigCraft Agentic Commerce.
4. Open WooCommerce > AI Commerce.

== Frequently Asked Questions ==

= Does the plugin send product data to an AI provider? =

No. Version 0.1.0 performs its audit locally and makes no external requests.

= Does it guarantee that ChatGPT or Gemini will show my products? =

No. It identifies catalog quality issues that can make products difficult for machines to understand, but third-party platforms control their own eligibility and ranking.

= How many products are scanned? =

The initial MVP scans the latest 25 published products. Background full-catalog scanning is planned.

== Screenshots ==

1. Catalog readiness summary and product findings.

== Changelog ==

= 0.1.0 =

* Initial development release.
* Added product readiness scoring.
* Added the WooCommerce AI Commerce dashboard.
* Added checks for core catalog fields and variable products.
