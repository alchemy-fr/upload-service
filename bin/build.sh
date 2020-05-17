#!/bin/bash

set -e

$(dirname $0)/update-libs.sh

# Force build order
docker-compose -f docker-compose.yml build uploader-api-php
docker-compose -f docker-compose.yml build uploader-worker
docker-compose -f docker-compose.yml build uploader-api-nginx
docker-compose -f docker-compose.yml build uploader-client
docker-compose -f docker-compose.yml build auth-api-php
docker-compose -f docker-compose.yml build auth-worker
docker-compose -f docker-compose.yml build auth-api-nginx
docker-compose -f docker-compose.yml build redis
docker-compose -f docker-compose.yml build db
docker-compose -f docker-compose.yml build rabbitmq
docker-compose -f docker-compose.yml build dockerize
docker-compose -f docker-compose.yml build expose-api-php
docker-compose -f docker-compose.yml build expose-worker
docker-compose -f docker-compose.yml build expose-api-nginx
docker-compose -f docker-compose.yml build expose-front
docker-compose -f docker-compose.yml build hello-api-php
docker-compose -f docker-compose.yml build hello-api-nginx
docker-compose -f docker-compose.yml build notify-api-php
docker-compose -f docker-compose.yml build notify-worker
docker-compose -f docker-compose.yml build notify-api-nginx
docker-compose -f docker-compose.yml build minio
docker-compose -f docker-compose.yml build minio-mc
docker-compose -f docker-compose.yml build weblate
docker-compose -f docker-compose.yml build report-api
docker-compose -f docker-compose.yml build dashboard
