# WordPress.org audit - 0.13.5

## Scope

Version 0.13.5 moves the existing factual DestinX AI Commerce Pro banner to the top of the plugin-owned `WooCommerce > AI Commerce` screen, directly below its page header and before the setup guide.

## Guideline review

The implementation retains the WordPress.org boundaries audited for version 0.13.4:

- the banner appears only on the plugin-owned screen and is not a global admin notice;
- each authorized user can dismiss it for 24 hours;
- the Free scan, filters, CSV export, Store Readiness, and product guidance remain complete;
- the Free runtime contains no Pro implementation, licensing, entitlement, updater, tracking, or remote-service code;
- the single HTTPS commercial link remains static and untracked;
- Pro remains a separately installed add-on that depends on the Free engine.

The integration smoke test now also verifies that the banner is rendered before the setup guide so its intended top placement cannot regress.
