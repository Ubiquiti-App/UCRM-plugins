#!/usr/bin/env bash

# this is a bash helper for packing a plugin from its source files.
# Usage: pack-plugin.sh plugin_dir
# where plugin_dir is the directory where the plugin exists

set -o errexit
set -o errtrace
set -o nounset
set -o pipefail
#set -o xtrace

PLUGIN_DIR_INPUT=${1:-}
ZIP="$(which zip)"

if [[ ! -x "$ZIP" ]]; then
    echo "Cannot find executable: zip"
    exit 99
fi

if [[ "$PLUGIN_DIR_INPUT" = "" ]]; then
    echo "Usage: $0 plugin_dir"
    exit 1
fi
PLUGIN_DIR="$(cd "$PLUGIN_DIR_INPUT" && pwd)" || true
if [[ ! -d "$PLUGIN_DIR" ]]; then
    # try to find the plugin in the directory of pack-plugin
    cd "$(dirname $0)"
    PLUGIN_DIR="$(cd "$PLUGIN_DIR_INPUT" && pwd)" || true
    if [[ ! -d "$PLUGIN_DIR" ]]; then
        echo "No such plugin found: $PLUGIN_DIR_INPUT"
        exit 2
    fi
fi
PLUGIN_DIR_SRC="$(cd $PLUGIN_DIR/src && pwd)" || true
PLUGIN_BASE="$(basename "$PLUGIN_DIR")"
PLUGIN_ZIP_FILE="${PLUGIN_DIR}/${PLUGIN_BASE}.zip"
if [[ ! -d "$PLUGIN_DIR_SRC" ]]; then
    echo "No src directory: $PLUGIN_DIR_SRC"
    exit 3
fi

touch "$PLUGIN_ZIP_FILE" || (
    echo "Cannot touch output file: $PLUGIN_ZIP_FILE"
    exit 4
)

if [[ ! -w "$PLUGIN_ZIP_FILE" ]]; then
    echo "Cannot write output file: $PLUGIN_ZIP_FILE"
    exit 5
fi

cd "$PLUGIN_DIR_SRC" || (
    echo "Cannot enter directory: $PLUGIN_DIR_SRC"
    exit 6
)

rm "$PLUGIN_ZIP_FILE" && "$ZIP" -o -v -r "$PLUGIN_ZIP_FILE" ./*

echo "$PLUGIN_ZIP_FILE successfully created."
