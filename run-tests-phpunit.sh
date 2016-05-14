#!/usr/bin/env sh

rm -rRf ./Tests/Functional/app/cache/*
rm -rRf ./Tests/Functional/app/logs/*

rm -f ./Tests/Functional/app/config/parameters.yml
echo 'parameters:
    database_driver:   pdo_mysql
    database_host:     127.0.0.1
    database_port:     null
    database_name:     doctrine_entity_override_test
    database_user:     root
    database_password: qweqwe
    locale:            en

' > ./Tests/Functional/app/config/parameters.yml

composer install --dev --prefer-dist

php ./Tests/Functional/app/console --env=test doctrine:database:drop --force
php ./Tests/Functional/app/console --env=test doctrine:database:create
php ./Tests/Functional/app/console --env=test doctrine:schema:create
php ./Tests/Functional/app/console --env=test doctrine:schema:update --force

php ./vendor/bin/phpunit -c ./ --testdox
