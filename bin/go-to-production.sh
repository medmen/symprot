#!/usr/bin/env bash

### show debug messages for asset-map
php console cache:clear
php console debug:asset-map

### compile assets for asset-mapper
php console asset-map:compile

### clean up composer for production
composer install --no-dev --optimize-autoloader

### install production env file
composer dump-env prod