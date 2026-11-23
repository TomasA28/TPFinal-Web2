# Copilot Instructions

## Stack and Runtime

- Plain PHP MVC-style application with no framework and no Composer manifest.
- MySQL database, accessed through PDO in `helper/MyDatabase.php`.
- Mustache templates loaded by `helper/MustacheRender.php`.
- Apache is the intended web server; `.htaccess` provides clean routes and static/public rewrites.
- Vendor code is committed under `vendor/` (`Mustache`, `PHPMailer`, and `phpqrcode`).

## Setup and Commands

There is no build step, package manifest, test framework, or configured linter.

- Import the database from the repository root:

  ```bash
  mysql -u root -p < app/sql/base_datos.sql
  ```

- Configure `config/config.ini` for the local MySQL host, credentials, and database name. The checked-in default database is `my_web`.
- Run locally with an Apache/PHP setup that enables `mod_rewrite`; the rewrite rules in `.htaccess` expect `/controller/method` URLs.
- There is no full test suite or single-test command in this repository.
- PHP syntax checks do not require a build tool:

  ```bash
  php -l app/controller/ReporteController.php
  find app helper index.php -name '*.php' -print0 | xargs -0 -n1 php -l
  ```

- Pushes to `main` trigger `.github/workflows/deploy.yml`, which deploys the repository over FTP to `/htdocs/`.

## Architecture

1. `index.php` starts the PHP session, loads `app/Configuration.php`, and asks `Router` to dispatch `controller` and `method` query parameters.
2. `.htaccess` translates URLs such as `/lobby/show` into `index.php?controller=lobby&method=show`.
3. `helper/Router.php` maps the requested name to `get{ControllerName}Controller()` on `Configuration`, then invokes the public method. Unknown controllers and methods fall back to `home/show`.
4. `app/Configuration.php` is the manual dependency container: it creates database, model, and render dependencies and wires each controller.
5. Controllers in `app/controller/` handle HTTP input, session checks, redirects, validation, and view data. Models in `app/model/` own SQL and domain operations.
6. `helper/MustacheRender.php` combines `app/view/header.mustache`, the requested view, and `app/view/footer.mustache`. Views use `.mustache` files under `app/view/`.
7. `public/` contains browser-served CSS and images. `.htaccess` serves that directory without routing it through PHP.
8. The canonical database schema and seed data are in `app/sql/base_datos.sql`; keep SQL schema changes aligned with the PHP model methods that use them.

## Request and Data Conventions

- Controllers read input through `Request::get()` and `Request::post()` rather than direct `$_GET`/`$_POST`; validate scalar types and IDs before using them.
- Global helper classes such as `Router`, `Request`, `Redirect`, `MyDatabase`, `Log`, `Mailer`, and `MustacheRender` are autoloaded from `helper/` by `helper/Autoloader.php`.
- Namespaced app classes are resolved from their namespace-relative path, for example `app\controller\LoginController` to `app/controller/LoginController.php`.
- Redirects use `Redirect::to('/controller/method')` or `Redirect::toIndex()` and terminate execution.
- Logged-in user state is stored in `$_SESSION['usuario_id']` and `$_SESSION['usuario']`; controller login guards consistently verify `usuario_id`.
- Database access goes through `MyDatabase::queryOne()`, `queryAll()`, and `execute()`. Use prepared statement `?` placeholders with the `$params` array.
- SQL and application logging are written through `Log`; MyDatabase logs executed SQL in `log/YYYY-MM-DD.log`.
- Models and controllers are mostly Spanish-named and should preserve existing names and terminology where extending a feature.

## Feature-Specific Patterns

- Each controller has a matching model and Mustache view in the corresponding `app/controller`, `app/model`, and `app/view` locations.
- Add a controller to routing by adding its factory method (`getXxxController`) to `app/Configuration.php`; the router derives that method name from the URL controller segment.
- The game has two flows: the category/roulette flow in `JuegoController`/`JuegoModel`, and the bot match flow in `PartidaController`/`PartidaModel`. Do not mix their session, answer, or score state without updating both paths.
- `preguntas_activas` implements a timed single-question state for the roulette flow. Match-specific questions and answers use `partida_pregunta` and `partida_id`/`respuesta_elegida` fields.
- Trampitas purchases and balance changes are represented through `compra_trampitas` and `movimiento_trampitas`; keep those records consistent when changing balance behavior.
- Profile uploads go to `public/subidas/`; profile HTML references the stored filename through the `fotoDePerfil` view value.
- `base_datos.sql` is destructive: it drops and recreates application tables. Do not run or modify it casually in a working local database.
