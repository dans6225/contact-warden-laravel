# contact-warden-laravel

Laravel admin-UI connector for [`dans6225/contact-warden`](https://github.com/dans6225/contact-warden)
— the abuse-detection engine itself has no admin UI, and no framework dependency; this package is
the Laravel-specific piece that renders one.

`LaravelAdminConnector` implements ContactWarden's `AdminConnectorInterface`: five methods that
render plain HTML (dashboard, submissions list, submission detail, abuse log, reputation list) plus
a `handleAction()` dispatch for maintenance operations, all backed by ContactWarden's own
`AdminDataSource`/`AdminMaintenance` interfaces — no raw SQL in this package, no knowledge of
storage internals. Same action vocabulary and behavior as
[`dans6225/contact-warden-ci4`](https://github.com/dans6225/contact-warden-ci4), so an app switching
frameworks doesn't have to relearn it.

## Installation

```bash
composer require dans6225/contact-warden dans6225/contact-warden-laravel
```

`dans6225/contact-warden`'s own migrations must already be applied against whichever database you
point `AdminDataSource`/`AdminMaintenance` at — see that package's own README for `bin/migrate.php`.
The `cw_*` tables can live in your app's own database alongside its regular schema; they don't need
a dedicated database.

The service provider is auto-discovered — nothing to register by hand. It binds `AdminDataSource`,
`AdminMaintenance`, and `LaravelAdminConnector` into the container, each built from your app's own
`database.default` connection config (a dedicated `\PDO` instance, not Laravel's own
`Illuminate\Database\Connection`, since ContactWarden's storage classes need a real PDO regardless
of your app's configured driver).

## Wiring it up

Type-hint `LaravelAdminConnector` in a controller — the container resolves it automatically:

```php
use ContactWardenLaravel\LaravelAdminConnector;
use ContactWarden\Admin\SubmissionQuery;

class ContactWardenAdminController extends Controller
{
    public function dashboard(LaravelAdminConnector $connector)
    {
        return view('layouts.admin', ['content' => $connector->renderDashboard()]);
    }

    public function submissions(LaravelAdminConnector $connector, Request $request)
    {
        $query = new SubmissionQuery(decision: $request->query('decision'));

        return view('layouts.admin', ['content' => $connector->renderSubmissionsList($query)]);
    }

    // renderSubmissionDetail(int $id), renderAbuseLog(AbuseQuery), renderReputationList(ReputationQuery)
    // follow the same shape.

    public function action(LaravelAdminConnector $connector, Request $request)
    {
        $input = $request->except('action');
        $result = $connector->handleAction((string) $request->input('action'), $input);

        return back()->with($result->success ? 'message' : 'error', $result->message);
    }
}
```

Gate these routes behind your app's own admin authentication/authorization middleware — the
connector assumes that's already handled by the time its methods are called. It never renders a
full page, just the content fragment, so wrap it in your own layout view as shown above.

## Maintenance actions

`handleAction(string $action, array $input): ActionResult` recognizes:

| Action | Input | Effect |
|---|---|---|
| `purge_tokens` | — | Removes expired or already-consumed tokens |
| `purge_submissions` | `days` (int) | Removes submission log rows older than `days` |
| `purge_abuse` | `days` (int) | Removes abuse log rows older than `days` |
| `forget_reputation` | `subject` (string) | Clears one subject's reputation score |
| `reset_reputation` | — | Clears every subject's reputation score |

Any other action name returns a failed `ActionResult`. The dispatch itself lives in core's
`MaintenanceActions`, which `LaravelAdminConnector::handleAction()` delegates to. To add actions of
your own, handle them in your controller and pass everything else through to `handleAction()`.

## What this doesn't cover

Same scope boundary as core and the CI4 connector: no message content (`ContactRecordStore` is
write-only by design — an inbox with read/archived state is host-app territory) and no settings
persistence (rebuild `ContactWardenConfig` from whatever your app already uses for admin-managed
settings, e.g. Laravel's own config/cache). Both are real tabs in a full admin UI, but neither
belongs in a framework connector any more than it belongs in core itself.

Also out of scope: the public-facing contact form itself (token issuance, honeypot field, `Engine`
wiring). This package is admin-only — see `dans6225/contact-warden`'s own
[`examples/plain-php/`](https://github.com/dans6225/contact-warden/tree/main/examples/plain-php) for
the pattern a Laravel controller would follow for the public side.

## Testing

```bash
composer install
vendor/bin/phpunit
```

Only `handleAction()` has PHPUnit coverage. The five `render*()` methods call Laravel's `view()`
helper, which needs a booted Laravel application — not present in a plain PHPUnit run — so they're
exercised inside an actual host app instead.

## License

MIT — see [LICENSE](LICENSE).
