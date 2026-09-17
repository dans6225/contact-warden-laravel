<h2>Abuse Log</h2>
<p>{{ $total }} matching</p>
<table>
    <tr><th>ID</th><th>When</th><th>IP</th><th>Decision</th><th>Score</th><th>Signals fired</th></tr>
    @foreach ($events as $e)
    @php $fired = array_filter($e->evidence, fn ($ev) => (int) ($ev['score'] ?? 0) > 0); @endphp
    <tr>
        <td>{{ $e->id }}</td>
        <td>{{ $e->createdAt->format('Y-m-d H:i:s') }}</td>
        <td>{{ $e->ip }}</td>
        <td>{{ $e->decision }}</td>
        <td>{{ $e->score }}</td>
        <td>{{ implode(', ', array_map(fn ($ev) => $ev['name'] . ' +' . $ev['score'], $fired)) }}</td>
    </tr>
    @endforeach
</table>
