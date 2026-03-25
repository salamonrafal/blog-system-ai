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

if [ ! -d "./var/imports/" ] ; then
    mkdir -p ./var/imports/
    chown www-data:www-data ./var/imports/
    chmod 777 ./var/imports/
fi

if [ ! -d "./var/exports/" ] ; then
    mkdir -p ./var/exports/
    chown www-data:www-data ./var/exports/
    chmod 777 ./var/exports/
fi
