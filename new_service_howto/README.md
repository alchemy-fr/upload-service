# Create a new "hello" api service
from / of phraseanet-services

## Step 0

If not already done, create / add to "env.local" to set dev mode :
```text
# App globals
# Symfony env var
APP_ENV=dev
# Enables some features for debugging applications
DEV_MODE=true
```
## Step 1 : Create sf project

log into dev container
```shell script
dc run --rm dev
``` 
 --> logged into /var/workspace (/ of phraseanet-services), as user "app"
 
```shell script
# create a dir for the service "hello"
mkdir hello && cd hello

# create a symfony "api" project (see https://symfony.com/doc/4.4/setup.html)
composer create-project symfony/skeleton api ^4.4.0

exit
```

### add to existing docker

add the services into docker-compose.yml
```yaml
  hello-api-php:
    image: ${REGISTRY_NAMESPACE}hello-api-php:$DOCKER_TAG
    build:
      context: ./hello/api
      target: hello-api-php
    networks:
      internal:
        aliases:
          - hello-api-php
    volumes:
      - ./configs:/configs
    depends_on:
      - db
    environment:
      - APP_ID=hello
      - DB_USER=${POSTGRES_USER}
      - DB_PASSWORD=${POSTGRES_PASSWORD}

  hello-api-nginx:
    image: ${REGISTRY_NAMESPACE}hello-api-nginx:$DOCKER_TAG
    build:
      context: ./hello/api
      target: hello-api-nginx
    networks:
      internal:
        aliases:
          - hello-api
    ports:
      - ${HELLO_API_PORT}:80
    depends_on:
      - hello-api-php
```

edit docker-compose-override.yml to share "hello" dir as volume;

add :
```yaml
  hello-api-php:
    environment:
      - XDEBUG_ENABLED
      - XDEBUG_CONFIG=remote_host=${PS_GATEWAY_IP} idekey=${IDE_KEY} remote_enable=1
      - PHP_IDE_CONFIG=serverName=${PS_DEBUG_SERVER_NAME_PREFIX}auth
    volumes:
      - ./hello/api:/srv/app

  hello-api-nginx:
    volumes:
      - ./hello/api:/srv/app
```

set HELLO_API_PORT into /.env (here 8130)
```dotenv
# hello
HELLO_API_PORT=8130
```

add some docker stuff from templates, setting the name of new service
```shell script
cp -r ./new_service_howto/api/docker ./hello/api
cp -r ./new_service_howto/api/Dockerfile ./hello/api
sed -i 's/{{service-name}}/hello/g' ./hello/api/Dockerfile
sed -i 's/{{service-name}}/hello/g' ./hello/api/docker/nginx/conf.d/default.conf
chmod 0775 hello/api/docker/fpm-entrypoint.sh
```
nb : copy those files __from dev container__ to avoid permissions problems
during up / build.

If you did copy those files __from host__ and not from dev container, 
fix permissions before up / build :
```shell script
chown :docker hello/api/docker/fpm-entrypoint.sh
chmod 0775 hello/api/docker/fpm-entrypoint.sh
```


to be checked / confirmed ? :

As oposite to other services like expose which is in "prod" mode,
 hello/api/Dockerfile contains for now APP_ENV=dev and DEV_MODE=true.

Setting only global / shell vars seems not enough
to set php container in dev mode ?

### test

```shell script
dc up -d hello-api-nginx
```

http://localhost:8130/ --> welcome to symfony

## Step 2 : install api-platform and makers

log into dev container
```shell script
dc run --rm dev
``` 

```shell script
cd hello/api

# install api-platform
composer req api

# composer req doctrine  # no need, installed by api-platform
composer req maker

# create a controller
sf make:controller foo

exit
```

### test

http://localhost:8130/foo --> Hello FooController

http://localhost:8130/api --> API Platform (no ops defined)


## Step 3 : add db and entity

stop the service
```shell script
dc down
```

### Add db

if not already done, start pgadmin service...

```shell script
dc up -d pgadmin
```
... and create a "hello" db using pgAdmin (http://localhost:8190)


Add db dependency to hello-api-php

```yaml
services:
  hello-api-php:
    ...
    depends_on:
      - db
    ...
```


Edit config/packages/doctrine.yaml
```yaml
parameters:
  env(DATABASE_URL): 'pgsql://%env(DB_USER)%:%env(DB_PASSWORD)%@db:5432/%env(DB_NAME)%'

doctrine:
  dbal:
    driver: 'pdo_pgsql'
    server_version: '11.2'
    charset: utf8
    url: '%env(resolve:DATABASE_URL)%'
  orm:
    auto_generate_proxy_classes: true
    naming_strategy: doctrine.orm.naming_strategy.underscore
    auto_mapping: true
    mappings:
      App:
        is_bundle: false
        type: annotation
        dir: '%kernel.project_dir%/src/Entity'
        prefix: 'App\Entity'
        alias: App

```

Edit "hello/api/.env" file, simply add DB_NAME
<pre>
###> doctrine/doctrine-bundle ###
# Format described at http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/configuration.html#connecting-using-a-url
# For an SQLite database, use: "sqlite:///%kernel.project_dir%/var/data.db"
# Configure your db driver and server_version in config/packages/doctrine.yaml
<b>DB_NAME=hello</b>
###< doctrine/doctrine-bundle ###
</pre>
nb : other settings (user, pwd, ...) comes from the top level .env

Restart hello :
```shell script
dc up -d hello-api-nginx
```
### Add entity

from dev container

```shell script
composer require migrations

sf make:entity --api-resource bar
sf make:migration
```

follow the docs :

https://api-platform.com/docs/distribution/#using-symfony-flex-and-composer-advanced-users

using the maker-bundle to manipulate entities.

### Test

http://localhost:8130/api --> bar crud api !


## Step 4 : Add admin


---
