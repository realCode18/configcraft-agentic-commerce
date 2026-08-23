=== DestinX AI Commerce for WooCommerce ===
Contributors: realcode18
Tags: woocommerce, artificial intelligence, product feed, catalog, ecommerce
Requires at least: 6.6
Tested up to: 7.0
Requires PHP: 7.4
Requires Plugins: woocommerce
Stable tag: 0.4.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Audit WooCommerce product catalogs for AI discovery and agentic commerce readiness.

== Description ==

DestinX AI Commerce for WooCommerce finds incomplete or ambiguous WooCommerce product data that may prevent AI shopping assistants from understanding and recommending products.

The plugin adds an AI Commerce screen under WooCommerce. It provides a quick preview of the latest products and a background scan of the full published catalog for:

* Product title and description quality.
* Price and featured image availability.
* Category, brand, SKU, and global identifier coverage.
* Product attributes and physical shipping data.
* Variable completeness, variation prices, attributes, and purchasability.

Every product receives a score and a list of actionable findings. Full-scan results are saved locally, ordered by the lowest score, and split into manageable pages. A product editor panel explains how to correct each finding.

Full scans run in small background batches through WooCommerce Action Scheduler, with WordPress Cron as a fallback. The previous completed snapshot remains visible until the replacement scan finishes. Duplicate jobs, temporary failures, catalog changes, and stale scans are handled without publishing partial results.

All analysis runs locally. This version does not contact external services and does not collect telemetry.

Important: The plugin improves catalog data quality. It does not guarantee placement, ranking, recommendation, or legal compliance on any third-party AI platform.

== Installation ==

1. Install and activate WooCommerce.
2. Upload the plugin directory to `/wp-content/plugins/` or install the ZIP through Plugins > Add New.
3. Activate DestinX AI Commerce for WooCommerce.
4. Open WooCommerce > AI Commerce.

== Frequently Asked Questions ==

= Does the plugin send product data to an AI provider? =

No. Version 0.4.0 performs its audit locally and makes no external requests.

= Does it guarantee that ChatGPT or Gemini will show my products? =

No. It identifies catalog quality issues that can make products difficult for machines to understand, but third-party platforms control their own eligibility and ranking.

= How many products are scanned? =

The dashboard initially previews the latest 25 published products. Select Scan full catalog to process every published product in small background batches and persist the results locally.

== Screenshots ==

1. Catalog readiness summary, full-scan progress, and product findings.
2. Product editor panel with score and remediation guidance.

== Changelog ==

= 0.4.0 =

* Added atomic scan snapshots with a stable active-result pointer.
* Added Action Scheduler and WP-Cron job deduplication.
* Added expiring locks, bounded retries, heartbeats, and stale-scan recovery.
* Added catalog reconciliation when products change during a scan.
* Added migration of pre-snapshot audit results without losing the last valid scan.
* Added product data hashes and scoring model version 1.0.0.
* Expanded variable-product, variation, stock-status, and purchasability checks.

= 0.3.0 =

* Renamed the plugin to DestinX AI Commerce for WooCommerce.
* Aligned the plugin slug, text domain, namespace, hooks, and internal prefixes.
* Added the detailed WordPress.org MVP and submission plan.

= 0.2.0 =

* Added full-catalog scans using Action Scheduler or WordPress Cron.
* Added persistent, paginated results ordered by lowest readiness score.
* Added scan progress and aggregate status totals.
* Added product editor remediation guidance.
* Added complete cleanup on uninstall.

= 0.1.0 =

* Initial development release.
* Added product readiness scoring.
* Added the WooCommerce AI Commerce dashboard.
* Added checks for core catalog fields and variable products.
