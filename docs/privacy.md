# Privacy and local data

DestinX AI Commerce for WooCommerce is a local diagnostic plugin. The current Free plugin does not create user accounts, set cookies, load remote assets, send telemetry, or make runtime requests to DestinX, ConfigCraft, AI providers, or other external services.

## Data read

The plugin reads only the WordPress and WooCommerce configuration needed for its readiness checks and published product catalog data such as titles, descriptions, prices, images, categories, brands, identifiers, SKUs, attributes, dimensions, variations, visibility, and stock status.

It does not read customer, order, payment, or checkout data.

## Data stored

Audit data remains in the site's own WordPress database. The custom audit table stores:

- scan identifier;
- product ID;
- readiness score and status;
- finding codes and severities;
- normalized pricing mode, provider label, adapter ID, verification level, purchase-state ownership, availability, and optional minimum/maximum amounts;
- product-data hash;
- scoring-model version;
- scan timestamp.

WordPress options store only the database version, scan state, locks, catalog revision, and the active snapshot identifier. These records are operational catalog data, not customer or visitor profiles.

## Export and removal

CSV files are generated only after an authorized administrator requests an export. They are streamed to that administrator and are not saved to a public server directory by the plugin.

Uninstalling the plugin removes its custom tables, options, and scheduled work from every affected site, including each site in a WordPress Multisite network. Deactivation alone preserves the last audit so it can be resumed after reactivation.

Because the current plugin does not collect personal data of its own, it does not register WordPress personal-data exporters or erasers. If a future optional service transmits data externally, it will require a separate documented privacy review and an explicit opt-in before release.
