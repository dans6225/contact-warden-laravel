<h2>Contact Warden</h2>
<table>
    <tr>
        <th>Submissions</th>
        <td>{{ $submissionsTotal }} total, {{ $submissionsAccept }} accepted</td>
    </tr>
    <tr>
        <th>Abuse events</th>
        <td>{{ $abuseTotal }} total, {{ $abuseRejected }} rejected</td>
    </tr>
    <tr>
        <th>Tokens</th>
        <td>{{ $tokensTotal }} total, {{ $tokensStale }} expired or used</td>
    </tr>
</table>
