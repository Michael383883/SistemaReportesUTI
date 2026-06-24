#!/bin/bash

set -e

echo "Esperando SQL Server..."
sleep 5

echo "Ejecutando migraciones..."
php artisan migrate --force

echo "Ejecutando seeders..."
php artisan db:seed --force

echo "Habilitando Storage..."
php artisan storage:link || true

echo "Iniciando Laravel..."
exec php artisan serve --host=0.0.0.0 --port=8000