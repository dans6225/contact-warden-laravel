<?php

declare(strict_types=1);

namespace ContactWardenLaravel\Tests;

use ContactWarden\Admin\AdminDataSource;
use ContactWarden\Admin\AdminMaintenance;
use ContactWardenLaravel\LaravelAdminConnector;
use PHPUnit\Framework\TestCase;

/**
 * handleAction() only delegates to core's MaintenanceActions (whose behaviour is
 * tested there), so this just proves the wiring. renderDashboard()/etc. call
 * Laravel's view() helper, which needs a booted Laravel application — not
 * present in a plain PHPUnit run — so those are exercised inside an actual
 * host app instead.
 */
final class LaravelAdminConnectorActionTest extends TestCase
{
    public function test_handle_action_delegates_to_the_maintenance_actions(): void
    {
        $maintenance = $this->createMock(AdminMaintenance::class);
        $maintenance->expects($this->once())->method('forgetReputation')->with('1.2.3.4');

        $connector = new LaravelAdminConnector($this->createStub(AdminDataSource::class), $maintenance);
        $result = $connector->handleAction('forget_reputation', ['subject' => '1.2.3.4']);

        $this->assertTrue($result->success);
        $this->assertSame('Reputation for 1.2.3.4 cleared.', $result->message);
    }

    public function test_an_unknown_action_is_a_failed_result(): void
    {
        $connector = new LaravelAdminConnector(
            $this->createStub(AdminDataSource::class),
            $this->createStub(AdminMaintenance::class),
        );

        $this->assertFalse($connector->handleAction('not_a_real_action', [])->success);
    }
}
