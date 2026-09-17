<h2>Reputation</h2>
<table>
    <tr><th>Subject</th><th>Score</th><th>Last updated</th></tr>
    @foreach ($entries as $entry)
    <tr>
        <td>{{ $entry->subject }}</td>
        <td>{{ $entry->record->score }}</td>
        <td>{{ $entry->record->lastUpdated->format('Y-m-d H:i:s') }}</td>
    </tr>
    @endforeach
</table>
