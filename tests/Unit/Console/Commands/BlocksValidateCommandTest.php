<?php

namespace Xavcha\PageContentManager\Tests\Unit\Console\Commands;

use Xavcha\PageContentManager\Tests\TestCase;

class BlocksValidateCommandTest extends TestCase
{
    public function test_command_can_be_executed(): void
    {
        $this->artisan('page-content-manager:blocks:validate')
            ->assertSuccessful();
    }

    public function test_command_with_json_output(): void
    {
        $this->artisan('page-content-manager:blocks:validate', ['--json' => true])
            ->assertSuccessful();
    }
}


