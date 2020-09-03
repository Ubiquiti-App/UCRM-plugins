#!/usr/bin/env bash

set -o errexit
set -o errtrace
set -o nounset
set -o pipefail
set -o xtrace

# run all the lines after "script:" in .travis.yml
bash -c "$(grep -A 50 'script:' < .travis.yml | cut -b7-)"
