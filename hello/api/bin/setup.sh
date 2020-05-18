#!/bin/sh

set -e

BASEDIR=$(dirname $0)

$BASEDIR/console doctrine:database:create --if-not-exists
$BASEDIR/console doctrine:schema:update -f
$BASEDIR/console doctrine:migrations:version --add --all --no-interaction
