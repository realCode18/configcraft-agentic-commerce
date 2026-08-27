# WordPress.org publication assets

## Brand source

The DestinX AI Commerce identity preserves the original DestinX X geometry used by ConfigCraft Suite. The new application tile, green readiness indicator, wordmark, and listing composition are specific to DestinX AI Commerce.

All original brand and listing artwork in this repository is distributed under GPL-2.0-or-later. It uses system-font fallbacks and loads no remote font, image, script, or stylesheet.

## Files for the WordPress.org SVN `assets` directory

| File | Required dimensions | Purpose |
| --- | ---: | --- |
| `.wordpress-org/icon.svg` | Vector | Scalable directory icon |
| `.wordpress-org/icon-128x128.png` | 128 × 128 | Standard fallback icon |
| `.wordpress-org/icon-256x256.png` | 256 × 256 | High-DPI fallback icon |
| `.wordpress-org/banner-772x250.png` | 772 × 250 | Standard plugin header |
| `.wordpress-org/banner-1544x500.png` | 1544 × 500 | High-DPI plugin header |
| `.wordpress-org/screenshot-1.png` | 1568 × 920 | Getting started and scan control |
| `.wordpress-org/screenshot-2.png` | 1568 × 350 | Full-scan status and catalog summary |
| `.wordpress-org/screenshot-3.png` | 1568 × 1000 | Filters, findings, and CSV export |
| `.wordpress-org/screenshot-4.png` | 1568 × 770 | Store Readiness checklist |
| `.wordpress-org/screenshot-5.png` | 360 × 550 | Product editor guidance |

The banner source is `design/wordpress-org/banner-1544x500.svg`. The in-plugin horizontal logo is `assets/brand/destinx-ai-commerce-horizontal.svg`.

## Rebuild

On the project Mac, run:

```bash
scripts/build-wporg-assets.sh
scripts/build-wporg-assets-package.sh 0.13.7
```

The first script validates the SVG sources, renders the required PNG sizes, reports the exact dimensions, and prints SHA-256 checksums. The second produces one ZIP containing the exact files destined for the WordPress.org SVN `assets/` directory. The screenshots are captured separately from an installed release so they always reflect the real interface. The current set was captured from the installed Free 0.13.7 build, contains English UI only, and excludes customer, order, payment, license, and account data.

## Publishing notes

- Upload these files to the top-level WordPress.org SVN `assets/` directory, never to plugin `trunk/assets`.
- Keep filenames lowercase and unchanged.
- Set PNG MIME types in SVN when required.
- The high-DPI banner supplements the standard banner; both must be present.
- Each screenshot number must have one matching caption in `readme.txt`.
- WordPress.org may cache changed artwork for several hours.
