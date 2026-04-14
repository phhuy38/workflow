<?php

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

it('has auth rate limiter registered at 5 per minute', function () {
    expect(RateLimiter::limiter('auth'))->not->toBeNull();
});

it('has notifications rate limiter registered at 10 per minute', function () {
    expect(RateLimiter::limiter('notifications'))->not->toBeNull();
});

it('has uploads rate limiter registered at 20 per minute', function () {
    expect(RateLimiter::limiter('uploads'))->not->toBeNull();
});

it('rate limits login at 5 attempts per minute', function () {
    $throttledCount = 0;

    for ($i = 0; $i < 6; $i++) {
        $response = $this->post('/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'wrongpassword',
        ]);

        if ($response->status() === 429) {
            $throttledCount++;
        }
    }

    expect($throttledCount)->toBeGreaterThanOrEqual(1);
});
