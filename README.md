# LARAVEL RESOURCE GENERATOR #

This README would normally document whatever steps are necessary to get your application up and running.

### What is this repository for? ###

* Quick summary
    * This repository is the Laravel Resource Generator.
* Version
    * v1.0

### How do I get set up? ###

* Dependencies
    * [Docker](https://docs.docker.com/get-docker/)
* Summary of set-up
    * To start the application
    ```
    docker compose up -d && docker exec -it laravel-resource-generator bash -c "composer update"
    ```
    * To stop the application
    ```
    docker compose down
    ```
* Validate composer.json
    ```
    docker exec -it laravel-resource-generator bash -c "composer validate"
    ```

### How to use in your project? ###
* To install
    ```
    composer require jaylordibe/laravel-resource-generator
    ```
* To generate a resource for User model
    ```
    php artisan app:generate-resource User
    ```

### Contribution guidelines ###

* Writing tests
* Code review
* Other guidelines

### Who do I talk to? ###

* Repo owner or admin
* Other community or team contact
