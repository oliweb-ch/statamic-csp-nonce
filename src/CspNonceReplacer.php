<?php

namespace Oliweb\StatamicCspNonce;

use Illuminate\Http\Request;
use Statamic\StaticCaching\Replacer;
use Symfony\Component\HttpFoundation\Response;

class CspNonceReplacer implements Replacer
{
    private const PLACEHOLDER = 'STATAMIC_CSP_NONCE';

    public function prepareResponseToCache(Response $response, Response $initial): void
    {
        $nonce = view()->shared('csp_nonce');
        if (! $nonce) {
            return;
        }

        $response->setContent(
            str_replace($nonce, self::PLACEHOLDER, $response->getContent())
        );
    }

    public function replaceInCachedResponse(Response $response): void
    {
        $nonce = view()->shared('csp_nonce');
        if (! $nonce) {
            return;
        }

        $response->setContent(
            str_replace(self::PLACEHOLDER, $nonce, $response->getContent())
        );
    }
}
