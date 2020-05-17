#!/bin/bash

set -e

BASEDIR=$(dirname $0)
. "$BASEDIR/functions.sh"
cd "$BASEDIR/.."

load-env

"$BASEDIR/update-config.sh"

set -ex

d-c up -d

# Wait for services to be ready
d-c run --rm dockerize

APPS="
auth-api-php
expose-api-php
notify-api-php
uploader-api-php
hello-api-php
"

for app in ${APPS}; do
    exec_container $app "echo y | bin/console doctrine:migrations:migrate"
done
