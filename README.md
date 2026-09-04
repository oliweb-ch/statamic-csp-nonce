# Statamic CSP Nonce

[![Tests](https://github.com/oliweb/statamic-csp-nonce/actions/workflows/tests.yml/badge.svg)](https://github.com/oliweb/statamic-csp-nonce/actions/workflows/tests.yml)

A Statamic addon that preserves Content Security Policy (CSP) nonces through the static cache (half/application driver).

## The Problem

Statamic's half static cache stores rendered HTML. If that HTML contains `nonce="abc123"` attributes (required for a strict `script-src` or `style-src` CSP), those nonces are frozen in the cache. On the next request a new nonce is generated, the cached one no longer matches the CSP header, and the browser blocks your scripts and styles.

## The Solution

This addon registers a `Replacer` that:

1. **Before caching** – swaps the nonce with an internal placeholder.
2. **On cache hit** – swaps the placeholder back with the fresh nonce for the current request.

No configuration required. No extra dependencies.

## Requirements

- PHP `^8.3`
- Statamic `^6`
- Static cache driver set to `application` (half mode)

## Installation

```bash
composer require oliweb/statamic-csp-nonce
```

The service provider is auto-discovered. Nothing else to do.

## Convention

Your CSP middleware **must** share the nonce via the View factory:

```php
view()->share('csp_nonce', $nonce);
```

That is the only contract between your application and this package. The package does not generate nonces, does not write CSP headers, and does not define any directives — all of that stays in your middleware.

### Minimal middleware example

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddCspHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = base64_encode(random_bytes(16));

        view()->share('csp_nonce', $nonce);

        $response = $next($request);

        $csp = "default-src 'self'; "
             . "script-src 'self' 'nonce-{$nonce}'; "
             . "style-src 'self' 'nonce-{$nonce}';";

        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}
```

Register it in `bootstrap/app.php` (Laravel 11+):

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        \App\Http\Middleware\AddCspHeaders::class,
    ]);
})
```

### Using the nonce in Blade

```blade
<script nonce="{{ view()->shared('csp_nonce') }}">
    // your inline script
</script>

<style nonce="{{ view()->shared('csp_nonce') }}">
    /* your inline style */
</style>
```

Or share it as a global helper in `AppServiceProvider`:

```php
Blade::directive('cspNonce', fn () => "<?php echo view()->shared('csp_nonce'); ?>");
```

```blade
<script nonce="@cspNonce">...</script>
```

## How It Works

The addon auto-registers `Oliweb\StatamicCspNonce\CspNonceReplacer` into `statamic.static_caching.replacers` at boot time, after the config is loaded. Existing replacers (CSRF token, no-cache fragments) are preserved.

## License

MIT
