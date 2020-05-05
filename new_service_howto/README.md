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
 
## Step 1 : create "api" symfony project

```shell script
# create a dir for the service "hello"
mkdir hello && cd hello

# create a symfony project, for api and front (see https://symfony.com/doc/4.4/setup.html)
composer create-project symfony/skeleton api ^4.4.0

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
set HELLO_API_PORT - here 8130 - into /.env (or /.env.local) 
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
chown :docker hello/api/docker/fpm-entrypoint.sh
chmod 0775 hello/api/docker/fpm-entrypoint.sh
```

### build the hello containers
```shell script
dc build hello-api-php hello-api-nginx
```
in case of error "starting container process caused "exec: \"/srv/app/docker/fpm-entrypoint.sh\": permission denied": unknown"

(try to) fix the permision, using the dev container ?
```shell script
dc run --rm dev
chmod 0775 hello/api/docker/fpm-entrypoint.sh
exit;
```

or retry without cache
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

