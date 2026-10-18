#!/usr/bin/env bash
set -e

echo "=== RailShift Wartung & Bereinigung ==="
php artisan optimize:clear
rm -rf storage/framework/views/*
rm -f bootstrap/cache/*.php

# Dateiberechtigungen für Webserver sicherstellen
chmod -R 777 storage bootstrap/cache
chmod -R g+s storage bootstrap/cache

echo "=== Statusprüfung ==="
php artisan about

echo "=== Bereitstellung erfolgreich abgeschlossen! ==="
