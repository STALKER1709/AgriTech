<?php

use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Feature tests hit the full HTTP stack and therefore need the application
| test case plus a migrated database. Unit tests stay free of both so they
| remain fast and isolated.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
| Concurrency tests spawn real processes, which have their own connections and
| therefore cannot see data held inside a test transaction. They truncate
| between tests instead, so everything they write is committed.
*/

pest()->extend(TestCase::class)
    ->use(DatabaseTruncation::class)
    ->in('Concurrency');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| Domain-specific expectations live here. Amounts are always stored as
| integers in FCFA (business rule RG10), so this expectation guards against
| a float sneaking into a money assertion.
|
*/

expect()->extend('toBeMoney', function () {
    expect($this->value)->toBeInt();

    return $this;
});
