# WordPress.org audit - 0.13.6

## Scope

Version 0.13.6 extends the existing per-user Pro banner dismissal from 24 hours to 30 days.

## Guideline review

The change reduces repetition while preserving the previously audited boundaries:

- the banner appears only on the plugin-owned `WooCommerce > AI Commerce` screen;
- it remains optional, dismissible, factual, and untracked;
- it does not block, delay, or limit any Free capability;
- no Pro implementation, license validation, entitlement state, updater, telemetry, or remote-service request exists in Free;
- the integration smoke test verifies that the banner stays hidden after one day and returns only after the full 30-day interval.

This more conservative frequency better reflects the WordPress.org requirement that contextual upgrade prompts remain limited in scope and be used sparingly.
