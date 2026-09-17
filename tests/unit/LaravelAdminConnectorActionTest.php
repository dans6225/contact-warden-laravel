<?php

declare(strict_types=1);

namespace ContactWardenLaravel\Tests;

use ContactWarden\Admin\AdminDataSource;
use ContactWarden\Admin\AdminMaintenance;
use ContactWardenLaravel\LaravelAdminConnector;
use PHPUnit\Framework\TestCase;

/**
 * Covers handleAction() only. renderDashboard()/renderSubmissionsList()/etc.
 * call Laravel's view() helper, which needs a booted Laravel application —
 * not present in a plain PHPUnit run, so those are exercised inside an
 * actual host app instead, not here.
 */
final class LaravelAdminConnectorActionTest extends TestCase
{
    public function test_purge_tokens_reports_removed_count(): void
    {
        $maintenance = $this->createMock(AdminMaintenance::class);
        $maintenance->expects($this->once())->method('purgeExpiredTokens')->willReturn(5);

        $connector = new LaravelAdminConnector($this->createStub(AdminDataSource::class), $maintenance);
        $result = $connector->handleAction('purge_tokens', []);

        $this->assertTrue($result->success);
        $this->assertSame('5 expired or used tokens removed.', $result->message);
    }

    public function test_purge_submissions_uses_days_input_for_cutoff(): void
    {
        $maintenance = $this->createMock(AdminMaintenance::class);
        $maintenance->expects($this->once())
            ->method('purgeSubmissionsOlderThan')
            ->with($this->callback(static function (\DateTimeImmutable $cutoff): bool {
                $expected = (new \DateTimeImmutable())->modify('-30 days');

                return abs($cutoff->getTimestamp() - $expected->getTimestamp()) < 5;
            }))
            ->willReturn(12);

        $connector = new LaravelAdminConnector($this->createStub(AdminDataSource::class), $maintenance);
        $result = $connector->handleAction('purge_submissions', ['days' => '30']);

        $this->assertTrue($result->success);
        $this->assertSame('12 submission log entries older than 30 days removed.', $result->message);
    }

    public function test_purge_abuse_uses_days_input_for_cutoff(): void
    {
        $maintenance = $this->createMock(AdminMaintenance::class);
        $maintenance->expects($this->once())->method('purgeAbuseEventsOlderThan')->willReturn(3);

        $connector = new LaravelAdminConnector($this->createStub(AdminDataSource::class), $maintenance);
        $result = $connector->handleAction('purge_abuse', ['days' => '7']);

        $this->assertTrue($result->success);
        $this->assertSame('3 abuse log entries older than 7 days removed.', $result->message);
    }

    public function test_forget_reputation_requires_a_subject(): void
    {
        $maintenance = $this->createMock(AdminMaintenance::class);
        $maintenance->expects($this->never())->method('forgetReputation');

        $connector = new LaravelAdminConnector($this->createStub(AdminDataSource::class), $maintenance);
        $result = $connector->handleAction('forget_reputation', []);

        $this->assertFalse($result->success);
    }

    public function test_forget_reputation_clears_the_given_subject(): void
    {
        $maintenance = $this->createMock(AdminMaintenance::class);
        $maintenance->expects($this->once())->method('forgetReputation')->with('1.2.3.4');

        $connector = new LaravelAdminConnector($this->createStub(AdminDataSource::class), $maintenance);
        $result = $connector->handleAction('forget_reputation', ['subject' => '1.2.3.4']);

        $this->assertTrue($result->success);
        $this->assertSame('Reputation for 1.2.3.4 cleared.', $result->message);
    }

    public function test_reset_reputation_clears_everything(): void
    {
        $maintenance = $this->createMock(AdminMaintenance::class);
        $maintenance->expects($this->once())->method('resetAllReputation');

        $connector = new LaravelAdminConnector($this->createStub(AdminDataSource::class), $maintenance);
        $result = $connector->handleAction('reset_reputation', []);

        $this->assertTrue($result->success);
    }

    public function test_unknown_action_fails(): void
    {
        $connector = new LaravelAdminConnector(
            $this->createStub(AdminDataSource::class),
            $this->createStub(AdminMaintenance::class),
        );

        $result = $connector->handleAction('not_a_real_action', []);

        $this->assertFalse($result->success);
    }
}
