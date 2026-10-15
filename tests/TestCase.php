<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;

abstract class TestCase extends BaseTestCase
{
    /**
     * The API key Kopokopo signs callbacks with, for tests.
     */
    protected const KOPOKOPO_TEST_API_KEY = 'test-kopokopo-api-key';

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['live', 'sandbox'] as $environment) {
            config(["services.kopokopo.{$environment}.apiKey" => self::KOPOKOPO_TEST_API_KEY]);
        }
    }

    /**
     * POST a Kopokopo callback signed the way Kopokopo signs them (HMAC
     * SHA256 of the raw JSON body with the API key).
     *
     * @param  array<string, mixed>  $payload
     */
    protected function postKopokopoCallback(string $uri, array $payload): TestResponse
    {
        $body = json_encode($payload);

        return $this->call('POST', $uri, [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_KOPOKOPO_SIGNATURE' => hash_hmac('sha256', $body, self::KOPOKOPO_TEST_API_KEY),
        ], $body);
    }

    protected function skipUnlessRouteExists(string $routeName, ?string $message = null): void
    {
        if (! Route::has($routeName)) {
            $this->markTestSkipped($message ?? "Route [{\$routeName}] is not defined.");
        }
    }
}
