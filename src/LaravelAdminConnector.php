<?php

declare(strict_types=1);

namespace ContactWardenLaravel;

use ContactWarden\Admin\AbuseQuery;
use ContactWarden\Admin\ActionResult;
use ContactWarden\Admin\AdminConnectorInterface;
use ContactWarden\Admin\AdminDataSource;
use ContactWarden\Admin\AdminMaintenance;
use ContactWarden\Admin\ReputationQuery;
use ContactWarden\Admin\SubmissionQuery;

/**
 * Laravel's AdminConnectorInterface implementation. Renders through Blade
 * views bundled with this package (registered under the `contact-warden::`
 * namespace by ContactWardenServiceProvider), so it works without the host
 * app publishing/copying any templates in.
 *
 * Action names and behavior match the CI4 connector's Ci4AdminConnector —
 * both implement the same interface against the same core, so an app
 * switching frameworks doesn't also have to relearn the action vocabulary.
 */
final class LaravelAdminConnector implements AdminConnectorInterface
{
    public function __construct(
        private readonly AdminDataSource $data,
        private readonly AdminMaintenance $maintenance,
    ) {
    }

    public function renderDashboard(): string
    {
        $now = new \DateTimeImmutable();

        return $this->render('dashboard', [
            'submissionsTotal'  => $this->data->countSubmissions(new SubmissionQuery()),
            'submissionsAccept' => $this->data->countSubmissions(new SubmissionQuery(decision: 'ACCEPT')),
            'abuseTotal'        => $this->data->countAbuseEvents(new AbuseQuery()),
            'abuseRejected'     => $this->data->countAbuseEvents(new AbuseQuery(decision: 'REJECT')),
            'tokensTotal'       => $this->data->countTokens(),
            'tokensStale'       => $this->data->countExpiredOrConsumedTokens($now),
        ]);
    }

    public function renderSubmissionsList(SubmissionQuery $query): string
    {
        return $this->render('submissions-list', [
            'submissions' => $this->data->listSubmissions($query),
            'total'       => $this->data->countSubmissions($query),
        ]);
    }

    public function renderSubmissionDetail(int $submissionId): string
    {
        $submission = $this->data->getSubmission($submissionId);

        if ($submission === null) {
            return $this->render('not-found', ['what' => 'submission', 'id' => $submissionId]);
        }

        return $this->render('submission-detail', ['submission' => $submission]);
    }

    public function renderAbuseLog(AbuseQuery $query): string
    {
        return $this->render('abuse-log', [
            'events' => $this->data->listAbuseEvents($query),
            'total'  => $this->data->countAbuseEvents($query),
        ]);
    }

    public function renderReputationList(ReputationQuery $query): string
    {
        return $this->render('reputation-list', [
            'entries' => $this->data->listReputations($query),
        ]);
    }

    public function handleAction(string $action, array $input): ActionResult
    {
        return match ($action) {
            'purge_tokens' => new ActionResult(
                true,
                $this->maintenance->purgeExpiredTokens(new \DateTimeImmutable()) . ' expired or used tokens removed.',
            ),
            'purge_submissions' => $this->purgeOlderThan(
                fn (\DateTimeImmutable $cutoff) => $this->maintenance->purgeSubmissionsOlderThan($cutoff),
                $input,
                'submission log entries',
            ),
            'purge_abuse' => $this->purgeOlderThan(
                fn (\DateTimeImmutable $cutoff) => $this->maintenance->purgeAbuseEventsOlderThan($cutoff),
                $input,
                'abuse log entries',
            ),
            'forget_reputation' => $this->forgetReputation($input),
            'reset_reputation' => $this->resetReputation(),
            default => new ActionResult(false, "Unknown action \"{$action}\"."),
        };
    }

    /** @param array<string,mixed> $input */
    private function purgeOlderThan(callable $purge, array $input, string $label): ActionResult
    {
        $days = max(0, (int) ($input['days'] ?? 0));
        $cutoff = (new \DateTimeImmutable())->modify("-{$days} days");
        $removed = $purge($cutoff);

        return new ActionResult(true, "{$removed} {$label} older than {$days} days removed.");
    }

    /** @param array<string,mixed> $input */
    private function forgetReputation(array $input): ActionResult
    {
        $subject = trim((string) ($input['subject'] ?? ''));
        if ($subject === '') {
            return new ActionResult(false, 'No reputation subject given.');
        }

        $this->maintenance->forgetReputation($subject);

        return new ActionResult(true, "Reputation for {$subject} cleared.");
    }

    private function resetReputation(): ActionResult
    {
        $this->maintenance->resetAllReputation();

        return new ActionResult(true, 'All reputation scores cleared.');
    }

    /** @param array<string,mixed> $viewData */
    private function render(string $view, array $viewData): string
    {
        return view("contact-warden::{$view}", $viewData)->render();
    }
}
