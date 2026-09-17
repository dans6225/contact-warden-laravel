<h2>Submission #{{ $submission->id }}</h2>
<dl>
    <dt>When</dt><dd>{{ $submission->createdAt->format('Y-m-d H:i:s') }}</dd>
    <dt>IP</dt><dd>{{ $submission->ip }}</dd>
    <dt>Decision</dt><dd>{{ $submission->decision }}</dd>
    <dt>Score</dt><dd>{{ $submission->score }}</dd>
</dl>
@if ($submission->meta !== [])
<h3>Meta</h3>
<pre>{{ json_encode($submission->meta, JSON_PRETTY_PRINT) }}</pre>
@endif
