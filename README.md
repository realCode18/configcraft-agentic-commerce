# DestinX AI Commerce for WooCommerce

DestinX AI Commerce for WooCommerce is a WordPress plugin that audits WooCommerce catalogs for AI discovery and agentic commerce readiness.

The plugin is intentionally local and diagnostic. It does not call AI providers, expose write operations, collect telemetry, or promise placement on third-party platforms.

## Current functionality

- Adds **WooCommerce > AI Commerce**.
- Shows a quick audit of the latest 25 published products.
- Scans the complete published catalog in small background batches.
- Persists results locally and paginates them with the lowest scores first.
- Scores product title, description, price, image, category, brand, identifier, SKU, attributes, shipping data, and variations.
- Links every finding back to the WooCommerce product editor.
- Adds live scoring and practical remediation guidance to each product editor.
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

Run the WordPress and WooCommerce smoke test in an isolated Playground environment:

```bash
npx --yes @wp-playground/cli@latest php \
  --mount=.:/wordpress/wp-content/plugins/destinx-ai-commerce \
  --blueprint=tests/playground-blueprint.json \
  -- /wordpress/wp-content/plugins/destinx-ai-commerce/tests/playground-smoke.php
```

Copy or symlink the repository into `wp-content/plugins/destinx-ai-commerce`, activate it, and open **WooCommerce > AI Commerce**.

## Extension hooks

- `destinx_ai_commerce_product_data`
- `destinx_ai_commerce_product_issues`
- `destinx_ai_commerce_audit_limit`
- `destinx_ai_commerce_batch_size`

## Roadmap

See the [submission-ready MVP plan](docs/mvp-plan.md), the [competitive analysis](docs/competitive-analysis.md), the [Free/Pro architecture](docs/free-pro-architecture.md), the [WordPress.org checklist](docs/wordpress-org-submission-checklist.md), and the concise [roadmap](docs/roadmap.md).

## License

GPL-2.0-or-later. See [LICENSE.md](LICENSE.md).
