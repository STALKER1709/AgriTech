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
        /*
         * PHPUnit écrit les variables du bloc <env> de phpunit.xml dans $_ENV
         * (et putenv), mais phpdotenv lit $_SERVER EN PRIORITÉ. Sur une machine
         * où l'environnement du shell exporte déjà APP_ENV, SESSION_DRIVER,
         * DB_DATABASE…, ces valeurs du shell écrasent donc celles de la suite
         * de tests : les POST répondaient 419 (CSRF actif hors « testing ») et
         * la file ne tournait plus en sync. On promeut donc les valeurs de
         * phpunit.xml dans $_SERVER avant que l'application ne démarre ; ailleurs,
         * la copie est un simple non-op, les deux tableaux coïncident déjà.
         */
        foreach ($_ENV as $key => $value) {
            $_SERVER[$key] = $value;
        }

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
