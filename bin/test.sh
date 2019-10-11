#!/bin/bash

# Tests are run directly in the container without mounted volumes
# except if you run `bin/test.sh 1`
# A build is required after any modification.

set -ex

export APP_ENV=test

FILE=""
if [[ -z "$1" ]]; then
    FILE=" -f docker-compose.yml"
fi


SERVICES="
uploader_api_php
auth_api_php
expose_api_php
notify_api_php
"

for s in ${SERVICES}; do
    docker-compose$FILE run -T --user app --rm ${s} /bin/sh -c "composer install --no-interaction && bin/console doctrine:schema:update -f && bin/phpunit"
done
