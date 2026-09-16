<?php

namespace Tests\Feature;

use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_security_headers_are_present_on_every_response(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        $response->assertHeader('Content-Security-Policy');
        $this->assertStringContainsString(
            "script-src 'self'",
            $response->headers->get('Content-Security-Policy')
        );
        $this->assertStringNotContainsString(
            'unsafe-inline',
            $response->headers->get('Content-Security-Policy')
        );
    }

    public function test_hsts_header_absent_on_plain_http_request(): void
    {
        $response = $this->get('/');

        $response->assertHeaderMissing('Strict-Transport-Security');
    }

    public function test_hsts_header_present_on_secure_request(): void
    {
        $response = $this->get('https://localhost/');

        $response->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }
}
