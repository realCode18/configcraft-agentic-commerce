# DestinX AI Commerce for WooCommerce

DestinX AI Commerce for WooCommerce is a WordPress plugin that audits WooCommerce catalogs for AI discovery and agentic commerce readiness.

The plugin is intentionally local and diagnostic. It does not call AI providers, expose write operations, collect telemetry, or promise placement on third-party platforms.

## Current functionality

- Adds **WooCommerce > AI Commerce**.
- Shows a quick audit of the latest 25 published products.
- Scans the complete published catalog in small background batches.
- Keeps the last completed snapshot visible until a replacement scan finishes atomically.
- Deduplicates jobs and recovers safely from retries, catalog changes, and stale scans.
- Persists results locally and paginates them with the lowest scores first.
- Searches full-scan results by partial product name or exact SKU and filters them by status, finding, and category.
- Exports the current filtered result set as a nonce-protected, spreadsheet-safe UTF-8 CSV.
- Shows when the visible catalog snapshot was updated and which scoring model produced it.
- Scores product title, description, price, image, category, brand, identifier, SKU, attributes, shipping data, variations, and purchasability with model version 1.1.0.
- Supports fixed, dynamic, quote-based, externally managed, and not-applicable pricing contexts through a public extension contract.
- Links every finding back to the WooCommerce product editor.
- Adds live scoring and practical remediation guidance to each product editor.
- Checks 15 site-level readiness conditions separately from product scores, including HTTPS, indexing, store pages, policies, REST, and shipping.
- Supports site-level and network-wide WordPress Multisite activation and cleanup.
- Declares WooCommerce HPOS compatibility and does not read order tables.
- Includes keyboard, reduced-motion, screen-reader, and responsive dashboard safeguards.
- Provides filters for custom product data and catalog-specific rules.

## Requirements

- WordPress 6.6 or later.
- WooCommerce.
- PHP 7.4 or later.

## Local development

```bash
composer install
composer lint
composer phpcs
composer test
```

Regenerate the translation template with WP-CLI after changing a user-facing string:

```bash
wp i18n make-pot . languages/destinx-ai-commerce.pot \
  --slug=destinx-ai-commerce \
  --domain=destinx-ai-commerce \
  --exclude=vendor,tests,docs \
  --skip-js \
  --headers='{"Report-Msgid-Bugs-To":"https://github.com/realCode18/destinx-ai-commerce/issues"}'
```

Run the WordPress and WooCommerce smoke test in an isolated Playground environment:

```bash
npx --yes @wp-playground/cli@latest php \
  --mount=.:/wordpress/wp-content/plugins/destinx-ai-commerce \
  --blueprint=tests/playground-blueprint.json \
  -- /wordpress/wp-content/plugins/destinx-ai-commerce/tests/playground-smoke.php
```

Run the network-activation, new-site, and network-uninstall lifecycle test:

```bash
npx --yes @wp-playground/cli@latest php \
  --site-url=http://localhost \
  --mount=.:/wordpress/wp-content/plugins/destinx-ai-commerce \
  --blueprint=tests/playground-multisite-blueprint.json \
  -- /wordpress/wp-content/plugins/destinx-ai-commerce/tests/playground-multisite-smoke.php
```

Run the progressive 0, 1, 26, 500, and 5,000-product scale test:

```bash
npx --yes @wp-playground/cli@latest php \
  --mount=.:/wordpress/wp-content/plugins/destinx-ai-commerce \
  --blueprint=tests/playground-blueprint.json \
  -- /wordpress/wp-content/plugins/destinx-ai-commerce/tests/playground-scale-smoke.php
```

Run the update, deactivation, and reactivation lifecycle test after building a previous tagged release in `build/previous/destinx-ai-commerce`:

```bash
npx --yes @wp-playground/cli@latest php \
  --mount=build/previous/destinx-ai-commerce:/wordpress/wp-content/plugins/destinx-ai-commerce \
  --mount=.:/wordpress/dxaic-current \
  --blueprint=tests/playground-upgrade-blueprint.json \
  -- /wordpress/dxaic-current/tests/playground-upgrade-smoke.php
```

Copy or symlink the repository into `wp-content/plugins/destinx-ai-commerce`, activate it, and open **WooCommerce > AI Commerce**.

## Privacy

All analysis and storage are local. The plugin does not read customer, order, payment, or checkout data; it does not add cookies, telemetry, external requests, or remote assets. See the complete [local-data inventory](docs/privacy.md).

## Extension hooks

- `destinx_ai_commerce_product_data`
- `destinx_ai_commerce_product_issues`
- `destinx_ai_commerce_store_data`
- `destinx_ai_commerce_audit_limit`
- `destinx_ai_commerce_batch_size`
- `destinx_ai_commerce_max_retries`
- `destinx_ai_commerce_stale_scan_seconds`

### External pricing contract

Pricing engines can use `destinx_ai_commerce_product_data` to replace the
normalized `pricing` element. The missing-price finding is suppressed only when
the provider explicitly sets `is_available` to `true`:

```php
add_filter( 'destinx_ai_commerce_product_data', function ( $data, $product ) {
	if ( my_pricing_engine_manages( $product ) ) {
		$data['pricing'] = array(
			'mode'         => 'dynamic',
			'source'       => 'my_pricing_engine',
			'label'        => 'My Pricing Engine',
			'is_available' => true,
			'min_price'    => '',
			'max_price'    => '',
		);
	}

	return $data;
}, 10, 2 );
```

Supported modes are `fixed`, `dynamic`, `quote`, `external`, and
`not_applicable`. Dynamic providers also own their purchasability state, so the
core evaluator does not apply native WooCommerce price/variation purchase rules
to those products.

## Roadmap

See the [submission-ready MVP plan](docs/mvp-plan.md), the [test and performance matrix](docs/test-matrix.md), the [competitive analysis](docs/competitive-analysis.md), the [Free/Pro architecture](docs/free-pro-architecture.md), the [privacy inventory](docs/privacy.md), the [WordPress.org checklist](docs/wordpress-org-submission-checklist.md), and the concise [roadmap](docs/roadmap.md).

## License

GPL-2.0-or-later. See [LICENSE.md](LICENSE.md).
