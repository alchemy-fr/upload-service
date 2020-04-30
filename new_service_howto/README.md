# Create a new "hello" api service
from / of phraseanet-services

create a "env.local" file to set dev mode :
```text
# App globals
# Symfony env var
APP_ENV=dev
# Enables some features for debugging applications
DEV_MODE=true
```

log into dev container
```shell script
dc run --rm dev
``` 
 --> logged into /var/workspace (/ of phraseanet-services), as user "app"
 
## Step 0 : Tell phraseanet-services
edit bin/vars.sh, add the hello project in SYMFONY_PROJECTS :
<pre>
SYMFONY_PROJECTS="
auth/api
expose/api
uploader/api
notify/api
<b>hello/api</b>
"
</pre>
 
## Step 1 : create symfony project

```shell script
# create a dir for the service "hello"
mkdir hello && cd hello

# create a symfony project, eg. for api (see https://symfony.com/doc/4.4/setup.html)
composer create-project symfony/skeleton api ^4.4.0
cd api

# install api-platform
composer req api

# install maker-bundle
composer require symfony/maker-bundle --dev

# sync and add libs
../../bin/update-libs.sh
composer config repositories.psr-http-message-bridge '{"type": "git", "url": "https://github.com/4rthem/psr-http-message-bridge.git"}'
composer config repositories.admin-bundle '{"type": "path", "url": "./__lib/admin-bundle", "options": {"symlink": true}}'
composer config repositories.oauth-server-bundle '{"type": "path", "url": "./__lib/oauth-server-bundle", "options": {"symlink": true}}'
composer config repositories.remote-auth-bundle '{"type": "path", "url": "./__lib/remote-auth-bundle", "options": {"symlink": true}}'
composer config repositories.report-bundle '{"type": "path", "url": "./__lib/report-bundle", "options": {"symlink": true}}'
composer config repositories.report-sdk '{"type": "path", "url": "./__lib/report-sdk", "options": {"symlink": true}}'
composer config repositories.api-test '{"type": "path", "url": "./__lib/api-test", "options": {"symlink": true}}'

# allow to get "dev" libs
composer config "minimum-stability" "dev"
composer config "prefer-stable" true

# install libss (for now, only  "admin-bundle")
composer req "alchemy/admin-bundle:@dev"
# ...
# /!\ will end-up with error

```

if req admin-bundle ends-up with error

"You have requested a non-existent parameter "easy_admin.site_title".

- copy the conf from "expose" :
```shell script
cp ../../expose/api/config/packages/admin.yaml ./config/packages
```
- fix the bundles order :

edit ./config/bundles.php, moving AlchemyAdminBundle _before_ EasyAdminBundle
```text
....
    Alchemy\RemoteAuthBundle\AlchemyRemoteAuthBundle::class => ['all' => true],
    Alchemy\AdminBundle\AlchemyAdminBundle::class => ['all' => true],
    EasyCorp\Bundle\EasyAdminBundle\EasyAdminBundle::class => ['all' => true],

```

Test the fix by running bin/console cache:clear --> now ok

quit dev container
```shell script
exit
```

--> back to local / of phraseanet-services

## Step 2 : add to existing docker

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
set HELLO_API_PORT into /.env (here 8130)
```dotenv
# hello
HELLO_API_PORT=8130
```


add some docker stuff from templates, setting the name of new service

```shell script
cp -r ./new_service_howto/api/docker ./hello/api
cp -r ./new_service_howto/api/Dockerfile ./hello/api
sed -i 's/{{service-name}}/hello-api/g' ./hello/api/Dockerfile
sed -i 's/{{service-name}}/hello-api/g' ./hello/api/docker/nginx/conf.d/default.conf

```

### build the hello containers
```shell script
dc build hello-api-php hello-api-nginx
```
in case of error (like "starting container process caused "exec: \"/srv/app/docker/fpm-entrypoint.sh\": permission denied": unknown)


try without cache
```shell script
dc build --force-rm --no-cache hello-api-php hello-api-nginx
```

### boot
```shell script
dc up -d hello-api-nginx
```

## Step 3 :  test
http://localhost:8130/
--> welcome to symfony

http://localhost:8130/api --> api platform (No operations defined in spec!)

## Step 4 : add a db

Create a "hello" db using pgAdmin (http://localhost:8190)

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

### Add entities

follow the docs :

https://api-platform.com/docs/distribution/#using-symfony-flex-and-composer-advanced-users

using the maker-bundle to manipulate entities :


---
### notes
add user to docker group
```shell script
sudo groupadd docker
sudo usermod -aG docker $USER
newgrp docker
docker run hello-world
```
log into hello api container
```shell script
dc run "hello-api-php" /bin/ash
```





