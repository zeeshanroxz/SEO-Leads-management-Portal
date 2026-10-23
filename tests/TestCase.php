<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Phase 1 views reference Vite assets; tests must not depend on a built frontend.
        $this->withoutVite();
    }

    /** Preview the app as a demo role (frontend preview only - NOT authentication). */
    protected function asRole(string $role): static
    {
        return $this->withSession(['demo_role' => $role]);
    }
}
