#!/usr/bin/env bash

set -o errexit
set -o errtrace
set -o nounset
set -o pipefail
#set -o xtrace

cd "$(dirname "$0")"

composer validate --no-check-publish --no-interaction --quiet
composer check --no-interaction
HAS_WARNINGS="$(composer install --no-interaction --no-suggest --ansi |& tee /dev/stderr | grep -ci Warning || true)"
if [[ "$HAS_WARNINGS" -gt 0 ]]; then
  echo "composer check failed" > /dev/stderr
fi
exit "$HAS_WARNINGS"
