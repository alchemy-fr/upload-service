#!/bin/bash

BASEDIR=$(dirname $0)
. "$BASEDIR/functions.sh"

load-env

. "${BASEDIR}/vars.sh"

set -e

function installComposer() {
    echo "Installing composer in $1..."
    docker-compose run --rm $1 su app sh -c "composer install"
    echo "Done."
    echo ""
    echo ""
}

function installNodeModules() {
    echo "Installing node modules in $1..."
    docker-compose run --rm $1 su node sh -c "yarn install"
    echo "Done."
    echo ""
    echo ""
}

installComposer auth-api-php
installComposer notify-api-php
installComposer uploader-api-php
installComposer expose-api-php
installComposer hello-api-php

installNodeModules expose-front-dev
installNodeModules uploader-client-dev
