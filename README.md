# Blog System AI

Symfony foundation for a blog application with:
- public article listing and details
- admin area for managing content
- Doctrine entity, repository, form and service layer
- Twig templates and starter configuration
- migration workflow for the database schema
- authenticated admin access with role-based authorization

## Development server

The project includes a small PHP-based development server helper.

Quick start:

1. Install dependencies:
   `composer install`
2. Create the SQLite database file:
   `composer db:create`
3. Run migrations:
   `composer db:migrate`
4. Start the development server:
   `composer serve:debug:start`
5. Open the application:
   `http://127.0.0.1:8888`

Useful commands:

- Start server:
  `composer serve:debug:start`
- Check status:
  `composer serve:debug:status`
- Restart server:
  `composer serve:debug:restart`
- Stop server:
  `composer serve:debug:stop`

The server writes its PID to:
- [`var/dev-server.pid`](./var/dev-server.pid)

Logs are written to:
- [`var/log/dev-server.log`](./var/log/dev-server.log)

## Unit tests

The project includes PHPUnit-based unit tests for the domain and service layer:
- slug generation in [`src/Service/ArticleSlugger.php`](./src/Service/ArticleSlugger.php)
- article save preparation in [`src/Service/ArticlePublisher.php`](./src/Service/ArticlePublisher.php)
- inactive user blocking in [`src/Security/UserChecker.php`](./src/Security/UserChecker.php)
- selected behavior of [`src/Entity/User.php`](./src/Entity/User.php)

How to run them:

1. Install project dependencies:
   `composer install`
2. Run the test suite:
   `composer test`

You can also run PHPUnit directly:
`vendor/bin/phpunit --configuration phpunit.xml.dist`

The unit tests do not require a database. Repository-dependent logic uses PHPUnit mocks.

## Suggested structure

```text
.
|-- bin/
|-- config/
|   |-- packages/
|   |-- bootstrap.php
|   |-- bundles.php
|   `-- routes.yaml
|-- migrations/
|-- public/
|-- src/
|   |-- Command/
|   |-- Controller/
|   |   `-- Admin/
|   |-- Entity/
|   |-- Enum/
|   |-- Form/
|   |-- Repository/
|   |-- Security/
|   `-- Service/
|-- templates/
|   |-- admin/
|   |-- blog/
|   `-- security/
`-- var/
```

## Database migrations

The project uses Doctrine Migrations. The schema is created by:
- [migrations/Version20260314120000.php](./migrations/Version20260314120000.php)
- [migrations/Version20260314133000.php](./migrations/Version20260314133000.php)

Typical workflow:

1. Create the SQLite database file:
   `composer db:create`
2. Run existing migrations:
   `composer db:migrate`
3. After changing entities, generate a new migration:
   `composer db:migration:diff`
4. Apply the new migration:
   `composer db:migrate`

For SQLite, the database file is configured in:
- [`.env`](./.env)

Current value:
`DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db"`

In this project `composer db:create` creates the SQLite file directly. It does not call `doctrine:database:create`, because that operation is not supported here by the SQLite platform/DBAL combination.

If you see `no such table: article` or `no such table: user`, it means migrations have not been executed yet.

## Admin access

The admin area under `/admin` is protected by Symfony Security and requires `ROLE_ADMIN`.

Setup flow:

1. Install packages:
   `composer install`
2. Create the SQLite file:
   `composer db:create`
3. Run migrations:
   `composer db:migrate`
4. Create the first administrator:
   `composer user:create-admin`
5. Open the login page:
   `/login`

The create-admin command also accepts arguments:
`php bin/console app:user:create-admin admin@example.com strong-password`

Main security files:
- [config/packages/security.yaml](./config/packages/security.yaml)
- [src/Entity/User.php](./src/Entity/User.php)
- [src/Security/UserChecker.php](./src/Security/UserChecker.php)
- [src/Controller/SecurityController.php](./src/Controller/SecurityController.php)
- [templates/security/login.html.twig](./templates/security/login.html.twig)

## Next steps

1. Add password reset and email verification.
2. Split admin roles into editor and administrator.
3. Add audit logging for content changes.
4. Extend the domain with categories, tags, comments and media.
