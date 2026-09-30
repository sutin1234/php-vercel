# php-vercel

PHP app on Vercel, packaged with **Docker + FrankenPHP** and deployed as a
container service — following [Vercel's guide](https://vercel.com/kb/guide/laravel-php-with-docker),
adapted from Laravel to this dependency-free app.

## Structure

```
.
├── Dockerfile.vercel    # FrankenPHP image (PHP 8.5 + Caddy)
├── Caddyfile            # docroot /app/public, everything else -> index.php
├── bootstrap.php        # autoloader (outside the document root)
├── public/              # the only directory served over HTTP
│   ├── index.php        # front controller: pages + JSON API + error handling
│   └── robots.txt
├── src/                 # app code (plain PSR-4, no framework)
│   ├── Config.php       # environment variable access
│   ├── HomePage.php     # HTML view
│   ├── Response.php     # json/html/text/error helpers
│   ├── Router.php       # regex router with 404 / 405 handling
│   └── UserRepository.php # in-memory data store (swap for a real DB)
├── tests/run.php        # dependency-free test runner
├── composer.json / composer.lock
└── vercel.json          # declares the container service
```

## Routes

| Method | Path           | Response                                  |
| ------ | -------------- | ----------------------------------------- |
| GET    | `/`            | Landing page (HTML)                       |
| GET    | `/health`      | Health check (JSON) — deployment smoke test |
| GET    | `/api/users`   | List users, `?role=` filters (JSON)       |
| GET    | `/api/users/1` | Single user (JSON)                        |
| POST   | `/api/users`   | Create a user (JSON body, `201`)          |

## How the deployment works

1. **Container service.** `vercel.json` declares `services.app` with
   `entrypoint: Dockerfile.vercel` and `runtime: container`, and rewrites
   `/(.*)` to that service. Vercel builds the image, stores it in the
   [Vercel Container Registry](https://vercel.com/docs/container-registry) and
   scales instances with traffic.
2. **FrankenPHP + Caddy.** The image runs `frankenphp run`, which is the PHP
   runtime embedded in the Caddy web server. `Caddyfile` sets the document root
   to `/app/public` and sends every non-file request to `/index.php`, so source
   files, `composer.json` and `src/` are unreachable over HTTP.
3. **Port contract.** Caddy binds `:{$PORT:80}`. Vercel assigns `PORT` at
   container start; a mismatch here is the usual cause of a `502`.
4. **Layered build.** Dependencies are installed in a separate stage from
   `composer.lock` only, so the vendor layer is cached until dependencies change.
   The application source is copied afterwards, keeping the image small.
5. **Statelessness.** The container filesystem is not durable. `UserRepository`
   is in-memory on purpose; put real data in a hosted database or KV store and
   read credentials from environment variables.

## Run locally

Docker is **not required** to run or test the app — only to deploy it. PHP 8.2+
and Composer are enough.

```bash
composer install     # once, generates vendor/
composer serve       # http://127.0.0.1:8000
composer test        # 10 assertions, no dependencies
```

```bash
curl localhost:8000/health
curl localhost:8000/api/users
curl localhost:8000/api/users/2
curl -X POST localhost:8000/api/users -d '{"name":"Ada","email":"ada@example.com"}'
```

`composer serve` runs `php -S 127.0.0.1:8000 -t public public/index.php`. The
front controller detects the `cli-server` SAPI and hands real files back to the
built-in server, so static assets and routing behave exactly like they do behind
Caddy in production.

To exercise the container itself (optional):

```bash
docker build -f Dockerfile.vercel -t php-vercel .
docker run --rm -p 8080:80 php-vercel
curl --fail localhost:8080/health
```

## Deploy

```bash
npm i -g vercel
vercel                 # preview deployment
vercel --prod          # production
```

Environment variables are injected when the container starts — use
`vercel env add APP_NAME production`, or set them in the dashboard. The image
never contains `.env` files (see `.dockerignore`); redeploy after changing them.

## Changing the PHP version

Edit the base image in `Dockerfile.vercel`:

| Base image                                | PHP    |
| ----------------------------------------- | ------ |
| `dunglas/frankenphp:1-php8.5.11-bookworm` | 8.5.x  |
| `dunglas/frankenphp:1-php8.4-bookworm`    | 8.4.x  |
| `dunglas/frankenphp:1-php8.3-bookworm`    | 8.3.x  |
| `dunglas/frankenphp:1-php8.2-bookworm`    | 8.2.x  |

Override PHP settings with `php.ini`, `php.ini-production`, or extra files in
`docker/php/conf.d/` in the repository root.

## Troubleshooting

| Symptom                            | Cause / fix                                                                 |
| ---------------------------------- | --------------------------------------------------------------------------- |
| `502` on every request             | Server not listening on `$PORT` — keep `:{$PORT:80}` in the Caddyfile        |
| `/health` never returns            | Deployment failed or routes never reach the service; check the build logs     |
| App name falls back to default     | `APP_NAME` missing in the project environment                                |
| `vendor/` missing in the container | `composer.lock` missing, or `.dockerignore` is too aggressive               |