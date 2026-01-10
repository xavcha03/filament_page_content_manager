<?php

namespace Xavcha\PageContentManager\Tests\Unit\Console\Commands;

use Xavcha\PageContentManager\Tests\TestCase;

class BlockInspectCommandTest extends TestCase
{
    public function test_command_can_inspect_existing_block(): void
    {
        // Test avec un bloc core qui devrait exister
        $this->artisan('page-content-manager:block:inspect', ['type' => 'hero'])
            ->assertSuccessful();
    }

    public function test_command_returns_error_for_non_existent_block(): void
    {
        $this->artisan('page-content-manager:block:inspect', ['type' => 'non_existent_block'])
            ->assertExitCode(3); // ExitCodes::BLOCK_NOT_FOUND
    }

    public function test_command_with_json_output(): void
    {
        $this->artisan('page-content-manager:block:inspect', [
            'type' => 'hero',
            '--json' => true,
        ])
            ->assertSuccessful();
    }
}

