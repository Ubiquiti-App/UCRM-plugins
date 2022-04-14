#!/bin/bash

rm ../invoice-telcell.zip
rm -rf ./vendor
composer install
./vendor/bin/pack-plugin