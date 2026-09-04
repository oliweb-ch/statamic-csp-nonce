<?php

namespace Oliweb\StatamicCspNonce;

use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
    public function bootAddon(): void
    {
        $this->app->booted(function () {
            $replacers = config('statamic.static_caching.replacers', []);

            if (in_array(CspNonceReplacer::class, $replacers)) {
                return;
            }

            config(['statamic.static_caching.replacers' => array_merge(
                $replacers,
                [CspNonceReplacer::class],
            )]);
        });
    }
}
