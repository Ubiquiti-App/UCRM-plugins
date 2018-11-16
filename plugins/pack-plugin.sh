#!/usr/bin/env bash

# This is a bash helper for packing a UCRM plugin from its source files.
# Usage: ./pack-plugin.sh plugin_dir
# where plugin_dir is the directory where the plugin exists

# bash script execution options - for details, see e.g. https://ss64.com/bash/set.html (or run `man bash`)
# exit if any command fails
set -o errexit
# error if unset variable
set -o nounset
# set error if a command in pipeline fails
set -o pipefail
# debug: show commands as they are executed, uncomment following line to enable
#set -o xtrace

# first script argument is the directory to use
PLUGIN_DIR_INPUT="${1:-}"
# get zip executable if not provided in env variable
if [[ "${ZIP:-}" = "" ]]; then
    ZIP="$(which zip)" || true
fi
# get composer executable if not provided in env variable
if [[ "${COMPOSER:-}" = "" ]]; then
    COMPOSER="$(which composer)" || true
fi

# no zip, cannot continue
if [[ ! -x "${ZIP}" ]]; then
    echo "ERROR: Cannot find executable: zip - unable to pack the plugin"
    exit 99
fi

# no composer, continue but warn
if [[ ! -x "${COMPOSER}" ]]; then
    echo "Warning: Cannot find executable: composer - won't update plugin's dependencies"
fi

# no plugin specified, show help and exit
if [[ "${PLUGIN_DIR_INPUT}" = "" ]]; then
    echo "Usage: $0 plugin_dir"
    echo "where plugin_dir is the directory where the plugin exists"
    exit 1
fi

# try to get the plugin's absolute path
PLUGIN_DIR="$(cd "${PLUGIN_DIR_INPUT}" && pwd)" || true
if [[ ! -d "${PLUGIN_DIR}" ]]; then
    # on fail, try to find the plugin in the directory of pack-plugin
    cd "$(dirname $0)"
    PLUGIN_DIR="$(cd "${PLUGIN_DIR_INPUT}" && pwd)" || true
    if [[ ! -d "${PLUGIN_DIR}" ]]; then
        echo "ERROR: No such plugin found: ${PLUGIN_DIR_INPUT}"
        exit 2
    fi
fi

# populate variables for the zip command
PLUGIN_DIR_SRC="$(cd ${PLUGIN_DIR}/src && pwd)" || true
PLUGIN_BASE="$(basename "$PLUGIN_DIR")"
PLUGIN_ZIP_FILE="${PLUGIN_DIR}/${PLUGIN_BASE}.zip"
if [[ ! -d "${PLUGIN_DIR_SRC}" ]]; then
    echo "ERROR: No src directory: ${PLUGIN_DIR_SRC}"
    exit 3
fi

# check if ZIP archive is writable
touch "${PLUGIN_ZIP_FILE}" || (
    echo "ERROR: Cannot touch output file: ${PLUGIN_ZIP_FILE}"
    exit 4
)
if [[ ! -w "${PLUGIN_ZIP_FILE}" ]]; then
    echo "ERROR: Cannot write output file: ${PLUGIN_ZIP_FILE}"
    exit 5
fi

# go to plugin directory
cd "${PLUGIN_DIR_SRC}" || (
    echo "ERROR: Cannot enter directory: ${PLUGIN_DIR_SRC}"
    exit 6
)

# compose if available
if [[ -x "${COMPOSER}" ]]; then
    if [[ -f "./composer.json" ]] ; then
        "${COMPOSER}" install
    fi
fi

rm "${PLUGIN_ZIP_FILE}" && "${ZIP}" --latest-time --verbose --recurse-paths "${PLUGIN_ZIP_FILE}" ./*

echo "OK: ${PLUGIN_ZIP_FILE} successfully created."
