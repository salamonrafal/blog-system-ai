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

## JavaScript assets

JavaScript runs in two modes:
- `dev`: Twig loads source modules directly from `public/assets/js/` without minification
- `prod`: Twig loads one bundled and minified file from `public/assets/build/app.min.js`

Build commands:

- Install frontend dependencies:
  `npm install`
- Reproducible install from lockfile:
  `npm ci`
- Build production bundle with minification:
  `npm run build:assets:prod`

In daily development Twig serves the source modules directly from `public/assets/js/`, so no JavaScript build step is required.

Optional command:

- Build an unminified development bundle for manual inspection/debugging of the bundled output:
  `npm run build:assets:dev`

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

## Export queues

The project includes background export queues for:
- articles
- article categories
- top menu

All generated files are stored in:
[`var/exports/`](./var/exports/)

The admin panel includes:
- queue view: `/admin/queues/status`
- export history and download list: `/admin/exports`

### Article export

Flow overview:
- In the admin article list, exporting an article creates a pending record in `article_export_queue`
- The console consumer collects pending queue items and builds a JSON export file with article metadata and content
- After processing, the consumer creates a record in `article_export` with status, type and file path
- The exported article payload includes stable identifiers such as:
  - article `slug`
  - assigned category `category_slug`

Important files:
- [src/Command/ProcessArticleExportQueueCommand.php](./src/Command/ProcessArticleExportQueueCommand.php)
- [src/Service/ArticleExportFileWriter.php](./src/Service/ArticleExportFileWriter.php)
- [src/Entity/ArticleExportQueue.php](./src/Entity/ArticleExportQueue.php)
- [src/Entity/ArticleExport.php](./src/Entity/ArticleExport.php)
- [src/Controller/Admin/ArticleExportController.php](./src/Controller/Admin/ArticleExportController.php)

Manual run:
- Composer shortcut:
  `composer article-export:process-queue`
- Direct Symfony command:
  `php bin/console app:article-export:process-queue`

What the manual run does:
- reads pending entries from `article_export_queue`
- sets them to `processing`
- generates a JSON export file in `var/exports`
- creates a new record in `article_export`
- marks processed queue entries as `completed`

If there are no pending entries, the command exits successfully and prints:
`No queued article exports to process.`

### Category export

Flow overview:
- In the admin category list, exporting a category creates a pending record in `category_export_queue`
- The console consumer builds a JSON export file with category metadata and translations
- The exported category payload includes a stable category `slug`

Important files:
- [src/Command/ProcessCategoryExportQueueCommand.php](./src/Command/ProcessCategoryExportQueueCommand.php)
- [src/Service/CategoryExportFileWriter.php](./src/Service/CategoryExportFileWriter.php)
- [src/Entity/CategoryExportQueue.php](./src/Entity/CategoryExportQueue.php)

Manual run:
- Composer shortcut:
  `composer category-export:process-queue`
- Direct Symfony command:
  `php bin/console app:category-export:process-queue`

### Top menu export

Flow overview:
- In the admin top menu view, exporting creates a pending record in `top_menu_export_queue`
- The console consumer exports the full menu hierarchy to one JSON file
- The exported menu item payload includes stable identifiers for cross-environment matching:
  - item `unique_name`
  - parent `parent_unique_name`
  - category target `category_slug`
  - article target `article_slug`

Important files:
- [src/Command/ProcessTopMenuExportQueueCommand.php](./src/Command/ProcessTopMenuExportQueueCommand.php)
- [src/Service/TopMenuExportFileWriter.php](./src/Service/TopMenuExportFileWriter.php)
- [src/Entity/TopMenuExportQueue.php](./src/Entity/TopMenuExportQueue.php)

Manual run:
- Composer shortcut:
  `composer top-menu-export:process-queue`
- Direct Symfony command:
  `php bin/console app:top-menu-export:process-queue`

### Scheduled processing with cron

The repository includes a ready cron file:
- [docker/conf/cron/article-queue](./docker/conf/cron/article-queue)

Current entries process all background queues once per minute:
- `app:article-export:process-queue`
- `app:category-export:process-queue`
- `app:top-menu-export:process-queue`
- `app:article-import:process-queue`

Before running consumers manually or from cron, make sure the database exists and migrations are applied:

1. Install dependencies:
   `composer install`
2. Create the SQLite database file:
   `composer db:create`
3. Run migrations:
   `composer db:migrate`

## Import queue

The project also includes an article import queue for processing previously exported JSON files in the background.

Flow overview:
- In the admin panel, uploading a file on `/admin/imports` creates a pending record in `article_import_queue`
- The console consumer reads the queued JSON export file and expects the `article-export` format produced by the export mechanism
- If an article with the same `slug` already exists, it is updated
- If no article with that `slug` exists, a new article is created
- If validation fails or the file is invalid, the queue item is marked as `failed` and the error reason is stored in `error_message`
- Uploaded files are stored in:
  [`var/imports/`](./var/imports/)
- The admin panel includes:
  - upload and status list: `/admin/imports`
  - pending queue view: `/admin/queues/status`

Important files:
- [src/Command/ProcessArticleImportQueueCommand.php](./src/Command/ProcessArticleImportQueueCommand.php)
- [src/Service/ArticleImportProcessor.php](./src/Service/ArticleImportProcessor.php)
- [src/Entity/ArticleImportQueue.php](./src/Entity/ArticleImportQueue.php)
- [src/Controller/Admin/ArticleImportController.php](./src/Controller/Admin/ArticleImportController.php)

### Manual consumer run

Before running the import consumer manually, make sure the database exists and migrations are applied:

1. Install dependencies:
   `composer install`
2. Create the SQLite database file:
   `composer db:create`
3. Run migrations:
   `composer db:migrate`

Run the queue consumer manually with one of these commands:

- Composer shortcut:
  `composer article-import:process-queue`
- Direct Symfony command:
  `php bin/console app:article-import:process-queue`

What the manual run does:
- reads pending entries from `article_import_queue`
- sets them to `processing`
- loads the uploaded JSON file from `var/imports`
- validates required fields and article constraints
- updates an existing article by `slug` or creates a new one
- marks successful items as `completed`
- marks invalid items as `failed` and stores the reason in `error_message`

If there are no pending entries, the command exits successfully and prints:
`No queued article imports to process.`

For a local non-Docker setup you can use equivalent crontab entries, for example:

- `* * * * * cd /path/to/project && composer article-export:process-queue`
- `* * * * * cd /path/to/project && composer category-export:process-queue`
- `* * * * * cd /path/to/project && composer top-menu-export:process-queue`
- `* * * * * cd /path/to/project && composer article-import:process-queue`

## Next steps

1. Add password reset and email verification.
2. Split admin roles into editor and administrator.
3. Add audit logging for content changes.
4. Extend the domain with categories, tags, comments and media.


## Docker Manual Command

### Build image
```bash
docker image build -t salamonrafal/blog-system-ai:dev .
```

### Create develop container 
```bash
docker container run -d -p 8888:8888 -p 8080:80 \
   -e APP_ENV=dev \
   -e APP_DEBUG=1 \
   -e APP_SECRET="test12345_deko1" \
   -e DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db" \
 --name blog-system-ai salamonrafal/blog-system-ai:dev
```

### Run command in container
```bash
docker container exec -it blog-system-ai bash
```

### Run Composer install
```bash
docker container exec -u www-data -it blog-system-ai composer install
```

### Run database migrations
```bash
docker container exec -u www-data -it blog-system-ai php bin/console doctrine:migrations:migrate --no-interaction
```

### Display log
```bash
docker container logs blog-system-ai
```

### Delete develop container 
```bash
docker container stop blog-system-ai && docker container rm blog-system-ai
```
