<?php

use Oliweb\StatamicCspNonce\CspNonceReplacer;
use Symfony\Component\HttpFoundation\Response;

beforeEach(function () {
    view()->share('csp_nonce', null);
});

test('replaces nonce with placeholder before caching', function () {
    $nonce = 'abc123xyz';
    view()->share('csp_nonce', $nonce);

    $html = '<script nonce="abc123xyz">alert(1)</script><link rel="stylesheet" nonce="abc123xyz">';
    $response = new Response($html);

    (new CspNonceReplacer())->prepareResponseToCache($response, new Response($html));

    expect($response->getContent())
        ->toContain('STATAMIC_CSP_NONCE')
        ->not->toContain('abc123xyz');
});

test('reinjects current nonce into cached response', function () {
    $nonce = 'fresh456nonce';
    view()->share('csp_nonce', $nonce);

    $cached = '<script nonce="STATAMIC_CSP_NONCE">alert(1)</script>';
    $response = new Response($cached);

    (new CspNonceReplacer())->replaceInCachedResponse($response);

    expect($response->getContent())
        ->toContain('fresh456nonce')
        ->not->toContain('STATAMIC_CSP_NONCE');
});

test('replaces all occurrences of nonce in prepareResponseToCache', function () {
    $nonce = 'multi999';
    view()->share('csp_nonce', $nonce);

    $html = '<script nonce="multi999"></script><style nonce="multi999"></style>';
    $response = new Response($html);

    (new CspNonceReplacer())->prepareResponseToCache($response, new Response($html));

    expect(substr_count($response->getContent(), 'STATAMIC_CSP_NONCE'))->toBe(2)
        ->and($response->getContent())->not->toContain('multi999');
});

test('replaces all occurrences of placeholder in replaceInCachedResponse', function () {
    $nonce = 'reinject999';
    view()->share('csp_nonce', $nonce);

    $cached = '<script nonce="STATAMIC_CSP_NONCE"></script><style nonce="STATAMIC_CSP_NONCE"></style>';
    $response = new Response($cached);

    (new CspNonceReplacer())->replaceInCachedResponse($response);

    expect(substr_count($response->getContent(), 'reinject999'))->toBe(2)
        ->and($response->getContent())->not->toContain('STATAMIC_CSP_NONCE');
});

test('does nothing in prepareResponseToCache when no nonce is shared', function () {
    $html = '<script nonce="leaked">alert(1)</script>';
    $response = new Response($html);

    (new CspNonceReplacer())->prepareResponseToCache($response, new Response($html));

    expect($response->getContent())->toBe($html);
});

test('does nothing in replaceInCachedResponse when no nonce is shared', function () {
    $html = '<script nonce="STATAMIC_CSP_NONCE">alert(1)</script>';
    $response = new Response($html);

    (new CspNonceReplacer())->replaceInCachedResponse($response);

    expect($response->getContent())->toBe($html);
});

test('service provider registers replacer in statamic config', function () {
    $replacers = config('statamic.static_caching.replacers', []);

    expect($replacers)->toContain(\Oliweb\StatamicCspNonce\CspNonceReplacer::class);
});

test('service provider does not register replacer twice', function () {
    // Le ServiceProvider a déjà tourné — on simule un double boot
    $provider = new \Oliweb\StatamicCspNonce\ServiceProvider(app());
    $provider->bootAddon();

    $replacers = config('statamic.static_caching.replacers', []);
    $count = array_count_values($replacers)[\Oliweb\StatamicCspNonce\CspNonceReplacer::class] ?? 0;

    expect($count)->toBe(1);
});
