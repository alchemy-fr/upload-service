# Create a new "hello" api service

nb : every task described here is relative to the / of phraseanet-services

Commands to be executed inside a container are described from the host, eg.:

```shell script
dc exec --user app dev ls
```

but can be played from the dev container:

```shell script
# log into dev container
dc run --rm dev
# --> logged into /var/workspace (/ of phraseanet-services), as user "app"
ls
``` 


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

 
```shell script
# up required services 
dc up -d dev db auth-api-nginx

# create a dir for the service "hello"
dc exec --user app dev mkdir hello

# add a readme
cp new_service_howto/_readme_hello.md hello/README.md

# create a symfony "api" project (see https://symfony.com/doc/4.4/setup.html)
dc exec --user app dev composer create-project symfony/skeleton hello/api ^4.4.0
```

### Configure and add to ps build tools

Add env vars to "/ps/.env"

```dotenv
# hello
HELLO_API_PORT=8130
HELLO_ADMIN_CLIENT_ID=hello-admin
HELLO_ADMIN_CLIENT_RANDOM_ID=12345
HELLO_ADMIN_CLIENT_SECRET=cli3nt_s3cr3t
HELLO_FRONT_DEV_PORT=3003
```

Add to symfony projects so the common libs can be updated by script: Edit "ps/bin/vars.sh"

```shell script
SYMFONY_PROJECTS="
...
hello/api
"
```

Add to the builds: Edit "ps/bin/build.sh", add hello service after the last similar service (here after "expose") 

```shell script
docker-compose -f docker-compose.yml build expose-front
docker-compose -f docker-compose.yml build hello-api-php
docker-compose -f docker-compose.yml build hello-api-nginx
```

Add to setup script: Edit "ps/bin/setup.sh", add 
```shell script
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
```

Add to install-dev script: Edit "/ps/bin/install-dev.sh", add
```shell script
installComposer hello-api-php
```

Add to migration script: Edit "/ps/bin/migrate.sh", add into APPS list
```shell script
APPS="
...
hello-api-php
"
```

### add to  docker

Add the services into "/ps/docker-compose.yml"

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
      # transfer values
      - APP_ENV
      - AUTH_BASE_URL
      # here we set values used by hello/api/config/packages/doctrine.yaml
      #   POSTGRES_USER and POSTGRES_PASSWORD are defined in /.env
      # DB_NAME is defined in hello/api/.env
      - DB_USER=${POSTGRES_USER}
      - DB_PASSWORD=${POSTGRES_PASSWORD}

      - ADMIN_CLIENT_ID=${HELLO_ADMIN_CLIENT_ID}
      - ADMIN_CLIENT_RANDOM_ID=${HELLO_ADMIN_CLIENT_RANDOM_ID}
      - ADMIN_CLIENT_SECRET=${HELLO_ADMIN_CLIENT_SECRET}

  hello-api-nginx:
    image: ${REGISTRY_NAMESPACE}hello-api-nginx:$DOCKER_TAG
    build:
      context: ./hello/api
      target: hello-api-nginx
    networks:
      internal:
        aliases:
          - hello_api
    ports:
      - ${HELLO_API_PORT}:80
    depends_on:
      - hello-api-php
```

Share "hello" dir as volume: Edit "/ps/docker-compose-override.yml", add :

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

Add some docker stuff from templates, setting the name of new service

```shell script
cp new_service_howto/api/Dockerfile hello/api && \
cp -r new_service_howto/api/docker hello/api && \
sed -i 's/{{service-name}}/hello/g' hello/api/Dockerfile && \
sed -i 's/{{service-name}}/hello/g' hello/api/docker/nginx/conf.d/default.conf && \
# !!! fix permissions
dc exec --user app dev chmod 0775 hello/api/docker/fpm-entrypoint.sh
```

If you did copy those files __from host__ and not using dev container, 
fix permissions before up / build :
```shell script
chown :docker hello/api/docker/fpm-entrypoint.sh
chmod 0775 hello/api/docker/fpm-entrypoint.sh
```

### Start hello container

```shell script
dc up -d --build hello-api-nginx
```

to be checked / confirmed ? :

As oposite to other services like expose which is in "prod" mode,
 hello/api/Dockerfile contains for now APP_ENV=dev and DEV_MODE=true.

Setting only global / shell vars seems not enough
to set php container in dev mode ?

### test

http://localhost:8130/ --> welcome to symfony !


## Step 2 : install sf tools (api-platform, makers, ...)

Since hello-api-php is up, commands can be played from the container
 (not from "dev" container anymore)
 
BUT some interactive commands (like bin/console xxx) are easier to run 
 from the dev container, thanks to zsh and shortcuts like "sf"="bin/console" 

```shell script
dc exec --user app hello-api-php composer req api maker migrations form
# nb: doctrine is installed by api-platform
# nb : "form" is installed now, as required later by "alchemy/oauth-server-bundle"
```

```shell script
# create a controller
dc exec --user app hello-api-php bin/console make:controller foo
```

### test

http://localhost:8130/foo --> Hello FooController

http://localhost:8130/api --> API Platform (no ops defined)


## Step 3: Add db and entity
db parameters were already defined in docker-compose.yml:

```yaml
services:
  ...
  hello-api-php:
    ...
    depends_on:
      - db
    ...
    environment:
      ...
      # DB_NAME is defined in hello/api/.env
      - DB_USER=${POSTGRES_USER}
      - DB_PASSWORD=${POSTGRES_PASSWORD}
```

Edit "hello/api/.env" file, simply define DB_NAME
```dotenv
###> doctrine/doctrine-bundle ###
# DB_USER and DB_PASSWORD must be set here for sf
DB_USER=hello
DB_PASSWORD=change-me
DB_NAME=hello
###< doctrine/doctrine-bundle ###
```

nb : other settings (DB_USER, DB_PASSWORD) comes from /docker-compose.yml
 and inherit from POSTGRES_USER, POSTGRES_PASSWORD defined in /.env, 
 or were already included in config/packages/doctrine.yaml

Replace default doctrine.yml with a preconfigured one
```shell script
dc exec --user app dev cp new_service_howto/api/config/packages/doctrine.yaml hello/api/config/packages
```
Add setup script to create/maintain db
```shell script
dc exec --user app dev cp new_service_howto/api/bin/setup.sh hello/api/bin
```

Run hello setup to create db
```shell script
dc exec --user app hello-api-php bin/setup.sh
```
### Create an entity

```shell script
# this will create a "bar" entity with only a "id" property
dc exec --user app hello-api-php bin/console make:entity --api-resource -n  bar && \
dc exec --user app hello-api-php bin/console make:migration
```

run hello setup to update db
```shell script
dc exec --user app hello-api-php bin/setup.sh
```

since the entity maker is normally interactive, it's better to log to dev to create a more complex entity...

### Test

http://localhost:8130/api --> bar crud api !


## Step 4 : Add admin

see : https://github.com/alchemy-fr/phraseanet-services/tree/master/lib/admin-bundle

```shell script
# sync and add libs
bin/update-libs.sh && \
dc exec --user app hello-api-php composer config repositories.psr-http-message-bridge '{"type":"git","url":"https://github.com/4rthem/psr-http-message-bridge.git"}' && \
dc exec --user app hello-api-php composer config repositories.admin-bundle '{"type":"path","url":"./__lib/admin-bundle","options":{"symlink":true}}' && \
dc exec --user app hello-api-php composer config repositories.oauth-server-bundle '{"type":"path","url":"./__lib/oauth-server-bundle","options":{"symlink":true}}' && \
dc exec --user app hello-api-php composer config repositories.remote-auth-bundle '{"type":"path","url":"./__lib/remote-auth-bundle","options":{"symlink":true}}' && \
dc exec --user app hello-api-php composer config repositories.report-bundle '{"type":"path","url":"./__lib/report-bundle","options":{"symlink":true}}' && \
dc exec --user app hello-api-php composer config repositories.report-sdk '{"type":"path","url":"./__lib/report-sdk","options":{"symlink":true}}' && \
dc exec --user app hello-api-php composer config repositories.api-test '{"type":"path","url":"./__lib/api-test","options":{"symlink":true}}' && \
dc exec --user app hello-api-php composer config repositories.acl-bundle '{"type":"path","url":"./__lib/acl-bundle","options":{"symlink":true}}' && \

# allow to get "dev" libs
dc exec --user app hello-api-php composer config "minimum-stability" "dev" && \
dc exec --user app hello-api-php composer config "prefer-stable" true && \

# install bundles
#    accept (y) the uuid recipe
dc exec --user app hello-api-php composer req "alchemy/oauth-server-bundle:@dev alchemy/acl-bundle:@dev alchemy/admin-bundle:@dev"

# ...
# /!\ will end-up with error

```
"You have requested a non-existent parameter "easy_admin.site_title".

Fix the bundles order :

edit ./config/bundles.php, moving AlchemyAdminBundle __before__ EasyAdminBundle

```text
....
    Alchemy\RemoteAuthBundle\AlchemyRemoteAuthBundle::class => ['all' => true],
    Alchemy\AdminBundle\AlchemyAdminBundle::class => ['all' => true],
    EasyCorp\Bundle\EasyAdminBundle\EasyAdminBundle::class => ['all' => true],
```

Re-install the admin-bundle so it will end-up with clean install of assets
```shell script
dc exec --user app hello-api-php composer req alchemy/admin-bundle:@dev
```

Add a minimal conf file for admin:
```shell script
cp new_service_howto/api/config/packages/security.yaml hello/api/config/packages && \
cp new_service_howto/api/config/routes/admin.yaml hello/api/config/routes && \
cp new_service_howto/api/config/packages/admin.yaml hello/api/config/packages && \
sed -i 's/{{service-name}}/hello/g' hello/api/config/packages/admin.yaml
```

upddate sql
```shell script
dc exec --user app hello-api-php bin/setup.sh
```

## Create auth credentials for hello admin
```shell script
dc exec --user app hello-api-php "bin/console alchemy:oauth:create-client hello-admin \
    --random-id=12345 \
    --secret=cli3nt_s3cr3t \
    --grant-type password \
    --grant-type authorization_code \
    --grant-type client_credentials \
    --scope user:list \
    --scope group:list \
    --redirect-uri http://localhost:8130"
```

### Test

http://localhost:8130/admin --> admin with login page


## Step 5 : Add front

...todo...

## Step 6 : Add worker

...todo...

### Add to dashboard

...todo...


