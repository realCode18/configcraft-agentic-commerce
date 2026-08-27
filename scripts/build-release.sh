#!/usr/bin/env bash

set -euo pipefail

dxaic_version="${1:?Usage: scripts/build-release.sh VERSION [GIT_REF]}"
dxaic_ref="${2:-HEAD}"
dxaic_archive="build/destinx-ai-commerce-${dxaic_version}.zip"

mkdir -p build

git archive \
	--format=zip \
	--prefix=destinx-ai-commerce/ \
	--output="${dxaic_archive}" \
	"${dxaic_ref}" \
	LICENSE.md \
	assets \
	changelog.txt \
	destinx-ai-commerce.php \
	includes \
	languages \
	readme.txt \
	uninstall.php

unzip -t "${dxaic_archive}"
shasum -a 256 "${dxaic_archive}"
