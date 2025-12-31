<?php

namespace App\Tenancy\Bootstrappers;

use Illuminate\Support\Facades\URL;
use Stancl\Tenancy\Contracts\TenancyBootstrapper;
use Stancl\Tenancy\Contracts\Tenant;

/**
 * AppUrlBootstrapper
 *
 * Ensure URLs generated while tenancy is initialized (queues, mail, signed routes)
 * use the current tenant's domain as the application root.
 *
 * Why:
 * - APP_URL is global, but in subdomain multi-tenancy, links must be per-tenant.
 * - Queue workers often run without an HTTP request, so we cannot rely on Request::getHost().
 * - By forcing the root URL during tenancy bootstrap, url(), route(), temporarySignedRoute()
 *   and mail links will point to the tenant domain.
 */
class AppUrlBootstrapper implements TenancyBootstrapper
{
    protected ?string $originalAppUrl = null;
    protected ?string $originalRootUrl = null;

    public function bootstrap(Tenant $tenant): void
    {
        $this->originalAppUrl = config('app.url');

        // URL generator keeps an internal forced root URL; we store the effective one.
        // (There is no public getter, so we store config + set our own forced root.)
        $this->originalRootUrl = config('app.url');

        $tenantUrl = $this->resolveTenantUrl($tenant);

        if (! $tenantUrl) {
            return;
        }

        config(['app.url' => $tenantUrl]);
        URL::forceRootUrl($tenantUrl);
    }

    public function revert(): void
    {
        if ($this->originalAppUrl !== null) {
            config(['app.url' => $this->originalAppUrl]);
            URL::forceRootUrl($this->originalAppUrl);
        }
    }

    protected function resolveTenantUrl(Tenant $tenant): ?string
    {
        // If we are in an HTTP request, prefer the real scheme/host (keeps port in dev).
        try {
            $request = request();
            if ($request && method_exists($request, 'getSchemeAndHttpHost')) {
                $host = $request->getHost();
                if ($host) {
                    return $request->getSchemeAndHttpHost();
                }
            }
        } catch (\Throwable) {
            // Ignore if no request is bound (queue workers).
        }

        // Queue worker fallback: use the tenant's first domain.
        try {
            $domain = $tenant->domains()->first()?->domain;
            if (! $domain) {
                return null;
            }

            // Determine scheme from current app.url, defaulting to http for local.
            $base = config('app.url');
            $scheme = parse_url($base, PHP_URL_SCHEME) ?: 'http';
            $port = parse_url($base, PHP_URL_PORT);
            $baseHost = parse_url($base, PHP_URL_HOST);

            // If the domain is stored as a subdomain only (e.g. "test"),
            // expand it using the base host (e.g. "localhost" => "test.localhost").
            // This matches stancl/tenancy's InitializeTenancyBySubdomain behavior.
            if ($baseHost && ! str_contains($domain, '.') && $domain !== $baseHost) {
                $domain = "{$domain}.{$baseHost}";
            }

            $url = $scheme . '://' . $domain;
            if ($port && ! in_array($port, [80, 443], true)) {
                $url .= ':' . $port;
            }

            return $url;
        } catch (\Throwable) {
            return null;
        }
    }
}

