#!/usr/bin/env bash

set -euo pipefail

dxaic_version="${1:?Usage: scripts/build-wporg-assets-package.sh VERSION}"
dxaic_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
dxaic_archive="${dxaic_root}/build/destinx-ai-commerce-wordpress-org-assets-${dxaic_version}.zip"
dxaic_files=(
	icon.svg
	icon-128x128.png
	icon-256x256.png
	banner-772x250.png
	banner-1544x500.png
	screenshot-1.png
	screenshot-2.png
	screenshot-3.png
	screenshot-4.png
	screenshot-5.png
)

"${dxaic_root}/scripts/build-wporg-assets.sh"

for dxaic_file in "${dxaic_files[@]}"; do
	if [[ ! -f "${dxaic_root}/.wordpress-org/${dxaic_file}" ]]; then
		echo "Missing WordPress.org asset: ${dxaic_file}" >&2
		exit 1
	fi
done

mkdir -p "${dxaic_root}/build"
rm -f "${dxaic_archive}"

(
	cd "${dxaic_root}/.wordpress-org"
	zip -X -q "${dxaic_archive}" "${dxaic_files[@]}"
)

unzip -t "${dxaic_archive}"
shasum -a 256 "${dxaic_archive}"
