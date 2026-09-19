<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
    /**
     * These tests are about HTTP behaviour and business rules, not about the
     * asset pipeline.
     *
     * Without this, every page-rendering test fails on a fresh clone until
     * `npm run build` has been run — a confusing failure that says nothing
     * about the code under test. Placed here rather than in Pest.php so that
     * it covers the PHPUnit-style classes too.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
