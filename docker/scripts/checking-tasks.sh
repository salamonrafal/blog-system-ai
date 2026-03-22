#!/bin/bash
set -e

echo "Checking files ..."

if [ ! -d "./var/cache/" ] ; then
    mkdir -p ./var/cache/
    chown www-data:www-data ./var/cache/
    chmod 777 ./var/cache/
fi

if [ ! -d "./var/log/" ] ; then
    mkdir -p ./var/log/
    chown www-data:www-data ./var/log/
    chmod 777 ./var/log/
fi
