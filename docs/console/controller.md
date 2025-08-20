# Basic controller

php theplugs make:controller UserController

## Resource controller (full CRUD)

php theplugs make:controller PostController --resource

## API resource controller

php theplugs make:controller ApiUserController --api

## Single action controller

php theplugs make:controller SendEmailController --invokable

## Multiple Controller Types

--resource or -r: Full CRUD resource controller
--api or -a: API resource controller (no create/edit views)
--invokable or -i: Single action controller with __invoke method
Default: Basic controller with just index() method
