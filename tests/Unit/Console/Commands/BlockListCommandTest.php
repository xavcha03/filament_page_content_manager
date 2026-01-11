<?php

namespace Xavcha\PageContentManager\Tests\Unit\Console\Commands;

use Xavcha\PageContentManager\Tests\TestCase;

class BlockListCommandTest extends TestCase
{
    public function test_command_can_be_executed(): void
    {
        $this->artisan('page-content-manager:block:list')
            ->assertSuccessful();
    }

    public function test_command_with_json_output(): void
    {
        $this->artisan('page-content-manager:block:list', ['--json' => true])
            ->assertSuccessful();
    }

    public function test_command_with_core_filter(): void
    {
        $this->artisan('page-content-manager:block:list', ['--core' => true])
            ->assertSuccessful();
    }

    public function test_command_with_custom_filter(): void
    {
        $this->artisan('page-content-manager:block:list', ['--custom' => true])
            ->assertSuccessful();
    }
}




