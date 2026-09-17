<h2>Submissions</h2>
<p>{{ $total }} matching</p>
<table>
    <tr><th>ID</th><th>When</th><th>IP</th><th>Decision</th><th>Score</th></tr>
    @foreach ($submissions as $s)
    <tr>
        <td><a href="submissions/{{ $s->id }}">{{ $s->id }}</a></td>
        <td>{{ $s->createdAt->format('Y-m-d H:i:s') }}</td>
        <td>{{ $s->ip }}</td>
        <td>{{ $s->decision }}</td>
        <td>{{ $s->score }}</td>
    </tr>
    @endforeach
</table>
