#!/usr/bin/env bash

echo -e "\033[0m \033[1;35m Running tests \033[0m"
docker exec -it laravel-resource-generator bash -c "composer validate"
