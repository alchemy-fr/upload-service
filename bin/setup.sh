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

# Setup Auth
## Create rabbitmq vhost
exec_container rabbitmq "rabbitmqctl add_vhost auth && rabbitmqctl set_permissions -p auth ${RABBITMQ_DEFAULT_USER} '.*' '.*' '.*'"
## Setup container
exec_container auth-api-php "bin/setup.sh"
## Create OAuth client for Admin
exec_container auth-api-php "bin/console alchemy:oauth:create-client ${AUTH_ADMIN_CLIENT_ID} \
    --random-id=${AUTH_ADMIN_CLIENT_RANDOM_ID} \
    --secret=${AUTH_ADMIN_CLIENT_SECRET} \
    --grant-type password \
    --grant-type authorization_code \
    --redirect-uri ${AUTH_BASE_URL}"


# Setup Uploader
## Create rabbitmq vhost
exec_container rabbitmq "rabbitmqctl add_vhost upload && rabbitmqctl set_permissions -p upload ${RABBITMQ_DEFAULT_USER} '.*' '.*' '.*'"
## Setup container
exec_container uploader-api-php "bin/setup.sh"
## Create OAuth client
exec_container auth-api-php "bin/console alchemy:oauth:create-client ${UPLOADER_CLIENT_ID} \
    --random-id=${UPLOADER_CLIENT_RANDOM_ID} \
    --secret=${UPLOADER_CLIENT_SECRET} \
    --grant-type password \
    --grant-type authorization_code"
## Create OAuth client for Admin
exec_container auth-api-php "bin/console alchemy:oauth:create-client ${UPLOADER_ADMIN_CLIENT_ID} \
    --random-id=${UPLOADER_ADMIN_CLIENT_RANDOM_ID} \
    --secret=${UPLOADER_ADMIN_CLIENT_SECRET} \
    --grant-type password \
    --grant-type authorization_code \
    --grant-type client_credentials \
    --scope user:list \
    --scope group:list \
    --redirect-uri ${UPLOADER_BASE_URL}"

# Setup Hello
## Setup container
exec_container hello-api-php "bin/setup.sh"
## Create OAuth client for Admin
exec_container auth-api-php "bin/console alchemy:oauth:create-client ${HELLO_ADMIN_CLIENT_ID} \
    --random-id=${HELLO_ADMIN_CLIENT_RANDOM_ID} \
    --secret=${HELLO_ADMIN_CLIENT_SECRET} \
    --grant-type password \
    --grant-type authorization_code \
    --grant-type client_credentials \
    --scope user:list \
    --scope group:list \
    --redirect-uri ${HELLO_BASE_URL}"

# Setup Expose
## Setup container
exec_container expose-api-php "bin/setup.sh"
## Create OAuth client
exec_container auth-api-php "bin/console alchemy:oauth:create-client ${EXPOSE_CLIENT_ID} \
    --random-id=${EXPOSE_CLIENT_RANDOM_ID} \
    --secret=${EXPOSE_CLIENT_SECRET} \
    --grant-type client_credentials \
    --grant-type authorization_code"
## Create OAuth client for Admin
exec_container auth-api-php "bin/console alchemy:oauth:create-client ${EXPOSE_ADMIN_CLIENT_ID} \
    --random-id=${EXPOSE_ADMIN_CLIENT_RANDOM_ID} \
    --secret=${EXPOSE_ADMIN_CLIENT_SECRET} \
    --grant-type password \
    --grant-type authorization_code \
    --grant-type client_credentials \
    --scope user:list \
    --scope group:list \
    --redirect-uri ${EXPOSE_BASE_URL}"
## Create minio bucket
docker-compose ${CONF} run --rm -T --entrypoint "sh -c" minio-mc "\
  while ! nc -z minio 9000; do echo 'Wait minio to startup...' && sleep 0.1; done; \
  sleep 5 && \
  mc config host add minio http://minio:9000 \$MINIO_ACCESS_KEY \$MINIO_SECRET_KEY && \
  mc mb --ignore-existing minio/$EXPOSE_STORAGE_BUCKET_NAME \
"

# Setup Notify
## Create rabbitmq vhost
exec_container rabbitmq "rabbitmqctl add_vhost notify && rabbitmqctl set_permissions -p notify ${RABBITMQ_DEFAULT_USER} '.*' '.*' '.*'"
## Setup container
exec_container notify-api-php "bin/setup.sh"
## Create OAuth client for Notify Admin
exec_container auth-api-php "bin/console alchemy:oauth:create-client ${NOTIFY_ADMIN_CLIENT_ID} \
    --random-id=${NOTIFY_ADMIN_CLIENT_RANDOM_ID} \
    --secret=${NOTIFY_ADMIN_CLIENT_SECRET} \
    --grant-type password \
    --grant-type authorization_code \
    --redirect-uri ${NOTIFY_BASE_URL}"


# Setup Report
## Create DB
create_db "${REPORT_DB_NAME}"


# Setup Weblate
## Create DB
create_db "${WEBLATE_POSTGRES_DB}"

# Create default admin user in Auth (must be execute after Notify & Auth setup)
exec_container auth-api-php "bin/console app:user:create \
    --update-if-exist ${DEFAULT_USER_EMAIL} \
    -p ${DEFAULT_USER_PASSWORD} \
    --roles ROLE_SUPER_ADMIN"
