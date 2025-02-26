#!/usr/bin/env bash
set -e

echo -e "\033[0m \033[1;35m Stopping existing services \033[0m"
docker compose down

echo -e "\033[0m \033[1;35m Starting services... \033[0m"
docker compose up -d

# Wait for the containers to initialize
echo -e "\033[0m \033[1;35m Waiting for the containers to initialize \033[0m"
sleep 5

docker exec -it laravel-resource-generator bash -c "composer update"
