<?php

declare(strict_types=1);

namespace App;

/**
 * Builds the landing page HTML.
 */
final class HomePage
{
    public static function render(): string
    {
        $appName = htmlspecialchars(Config::appName(), ENT_QUOTES);
        $php = htmlspecialchars(PHP_VERSION, ENT_QUOTES);
        $where = Config::isVercel()
            ? 'FrankenPHP container on Vercel'
            : 'PHP built-in dev server (local)';
        $requestUri = htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/', ENT_QUOTES);

        $links = [
            '/health' => 'Health check (JSON)',
            '/api/users' => 'List users (JSON)',
            '/api/users/1' => 'Single user (JSON)',
        ];

        $items = '';
        foreach ($links as $href => $label) {
            $safeHref = htmlspecialchars($href, ENT_QUOTES);
            $safeLabel = htmlspecialchars($label, ENT_QUOTES);
            $items .= "<li><a href=\"{$safeHref}\">{$safeLabel}</a></li>";
        }

        return <<<HTML
        <!doctype html>
        <html lang="en">
        <head>
          <meta charset="utf-8">
          <meta name="viewport" content="width=device-width, initial-scale=1">
          <title>{$appName}</title>
          <style>
            :root { color-scheme: dark; }
            body {
              margin: 0; min-height: 100vh; display: grid; place-items: center;
              font: 16px/1.6 ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
              background: #0b0d10; color: #e7e9ee;
            }
            main { width: min(42rem, 90vw); }
            h1 { font-size: 2rem; margin: 0 0 .25rem; letter-spacing: -.02em; }
            p.sub { margin: 0 0 2rem; color: #8b93a5; }
            dl { display: grid; grid-template-columns: auto 1fr; gap: .5rem 1rem; margin: 0 0 2rem; }
            dt { color: #8b93a5; }
            dd { margin: 0; font-family: ui-monospace, "SF Mono", Menlo, monospace; font-size: .9rem; }
            ul { padding-left: 1.1rem; margin: 0; }
            a { color: #6ee7b7; }
            code { font-family: ui-monospace, "SF Mono", Menlo, monospace; }
          </style>
        </head>
        <body>
          <main>
            <h1>{$appName}</h1>
            <p class="sub">PHP on Vercel — minimal serverless starter</p>
            <dl>
              <dt>PHP version</dt><dd>{$php}</dd>
              <dt>Runtime</dt><dd>{$where}</dd>
              <dt>Server time</dt><dd><code>{$requestUri}</code></dd>
            </dl>
            <ul>{$items}</ul>
          </main>
        </body>
        </html>
        HTML;
    }
}