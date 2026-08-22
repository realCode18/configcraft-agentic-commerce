# ConfigCraft Agentic Commerce

ConfigCraft Agentic Commerce is a WordPress plugin that audits WooCommerce catalogs for AI discovery and agentic commerce readiness.

The initial MVP is intentionally local and diagnostic. It does not call AI providers, expose write operations, collect telemetry, or promise placement on third-party platforms.

## Current functionality

- Adds **WooCommerce > AI Commerce**.
- Audits the latest 25 published products.
- Scores product title, description, price, image, category, brand, identifier, SKU, attributes, shipping data, and variations.
- Links every finding back to the WooCommerce product editor.
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
  --mount=.:/wordpress/wp-content/plugins/configcraft-agentic-commerce \
  --blueprint=tests/playground-blueprint.json \
  -- /wordpress/wp-content/plugins/configcraft-agentic-commerce/tests/playground-smoke.php
```

Copy or symlink the repository into `wp-content/plugins/configcraft-agentic-commerce`, activate it, and open **WooCommerce > AI Commerce**.

## Extension hooks

- `configcraft_agentic_commerce_product_data`
- `configcraft_agentic_commerce_product_issues`
- `configcraft_agentic_commerce_audit_limit`

## Roadmap

See [docs/roadmap.md](docs/roadmap.md).

## License

GPL-2.0-or-later. See [LICENSE.md](LICENSE.md).
