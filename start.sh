#!/usr/bin/env bash
set -a

echo -e "\033[0m \033[1;35m Starting services \033[0m"
docker compose up -d
docker exec -it laravel-resource-generator bash -c "composer update"
