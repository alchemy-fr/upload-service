#!/bin/sh

if [ ${XDEBUG_ENABLED} == "1" ]; then
    docker-php-ext-enable xdebug
fi

# Must refresh cache to interpret /configs/config.json
if [ "${APP_ENV}" == "prod" ]; then
    su app /bin/sh -c "bin/console cache:clear"
fi

exec "$@"