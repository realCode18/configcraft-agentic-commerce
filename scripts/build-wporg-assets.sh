#!/usr/bin/env bash

set -euo pipefail

dxaic_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
dxaic_chrome="/Applications/Google Chrome.app/Contents/MacOS/Google Chrome"
dxaic_render_dir="$(mktemp -d)"

trap 'rm -rf "${dxaic_render_dir}"' EXIT

if [[ ! -x "${dxaic_chrome}" ]]; then
	echo "Google Chrome is required to render the WordPress.org banner sources." >&2
	exit 1
fi

xmllint --noout \
	"${dxaic_root}/.wordpress-org/icon.svg" \
	"${dxaic_root}/assets/brand/destinx-ai-commerce-horizontal.svg" \
	"${dxaic_root}/design/wordpress-org/banner-1544x500.svg"

qlmanage -t -s 256 -o "${dxaic_render_dir}" "${dxaic_root}/.wordpress-org/icon.svg" >/dev/null 2>&1
cp "${dxaic_render_dir}/icon.svg.png" "${dxaic_root}/.wordpress-org/icon-256x256.png"
sips -z 128 128 \
	"${dxaic_root}/.wordpress-org/icon-256x256.png" \
	--out "${dxaic_root}/.wordpress-org/icon-128x128.png" >/dev/null

"${dxaic_chrome}" \
	--headless=new \
	--disable-gpu \
	--hide-scrollbars \
	--force-device-scale-factor=1 \
	--window-size=1544,500 \
	--screenshot="${dxaic_root}/.wordpress-org/banner-1544x500.png" \
	"file://${dxaic_root// /%20}/design/wordpress-org/banner-1544x500.svg" >/dev/null 2>&1

sips -z 250 772 \
	"${dxaic_root}/.wordpress-org/banner-1544x500.png" \
	--out "${dxaic_root}/.wordpress-org/banner-772x250.png" >/dev/null

file \
	"${dxaic_root}/.wordpress-org/icon-128x128.png" \
	"${dxaic_root}/.wordpress-org/icon-256x256.png" \
	"${dxaic_root}/.wordpress-org/banner-772x250.png" \
	"${dxaic_root}/.wordpress-org/banner-1544x500.png"

shasum -a 256 \
	"${dxaic_root}/.wordpress-org/icon.svg" \
	"${dxaic_root}/.wordpress-org/icon-128x128.png" \
	"${dxaic_root}/.wordpress-org/icon-256x256.png" \
	"${dxaic_root}/.wordpress-org/banner-772x250.png" \
	"${dxaic_root}/.wordpress-org/banner-1544x500.png"
