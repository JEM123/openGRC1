#!/usr/bin/env bash
set -o monitor
trap exit SIGCHLD
httpd -DFOREGROUND &
php-fpm -F &
wait
