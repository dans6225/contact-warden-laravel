<?php

declare(strict_types=1);

namespace ContactWardenLaravel;

use ContactWarden\Admin\AbuseQuery;
use ContactWarden\Admin\ActionResult;
use ContactWarden\Admin\AdminConnectorInterface;
use ContactWarden\Admin\AdminDataSource;
use ContactWarden\Admin\AdminMaintenance;
use ContactWarden\Admin\MaintenanceActions;
use ContactWarden\Admin\ReputationQuery;
use ContactWarden\Admin\SubmissionQuery;

/**
 * Laravel's AdminConnectorInterface implementation. Renders through Blade
 * views bundled with this package (registered under the `contact-warden::`
 * namespace by ContactWardenServiceProvider), so it works without the host
 * app publishing/copying any templates in.
 *
 * handleAction() delegates to core's MaintenanceActions, the same one every
 * connector uses, so an app switching frameworks doesn't have to relearn the
 * action vocabulary.
 */
final class LaravelAdminConnector implements AdminConnectorInterface
{
    private readonly MaintenanceActions $actions;

    public function __construct(
        private readonly AdminDataSource $data,
        AdminMaintenance $maintenance,
    ) {
        $this->actions = new MaintenanceActions($maintenance);
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
        return $this->actions->handle($action, $input);
    }

    /** @param array<string,mixed> $viewData */
    private function render(string $view, array $viewData): string
    {
        return view("contact-warden::{$view}", $viewData)->render();
    }
}
