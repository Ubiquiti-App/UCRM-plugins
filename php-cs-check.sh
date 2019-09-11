#!/usr/bin/env bash

set -o errexit
set -o errtrace
set -o nounset
set -o pipefail
#set -o xtrace

cd "$(dirname "$0")"

DIR_NAME="$(pwd)"
PHP_CS_FIXER="${DIR_NAME}/vendor/bin/php-cs-fixer"
PHP_CF_OPTIONS=(--diff "--diff-format=udiff" --no-interaction --ansi "--rules=@PHP73Migration")
if [[ ! -x "$PHP_CS_FIXER" ]]; then
	composer install --no-interaction --no-suggest --quiet
fi

${PHP_CS_FIXER} fix --quiet "${PHP_CF_OPTIONS[@]}" --dry-run "${DIR_NAME}" ||
	(
		RESULT=$?
		${PHP_CS_FIXER} fix "${PHP_CF_OPTIONS[@]}" --dry-run "${DIR_NAME}" ||
			printf " %s" "Fix by running: " "${PHP_CS_FIXER}" fix "${PHP_CF_OPTIONS[@]}" "${DIR_NAME}" $'\n'
		exit ${RESULT}
	)
