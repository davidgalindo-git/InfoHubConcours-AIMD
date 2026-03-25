<?php
declare(strict_types=1);

/**
 * Lightweight debug logger for this coding session.
 * Writes to the expected workspace log file and also posts to the debug ingest endpoint.
 */
function agent_log(string $hypothesisId, string $location, string $message, array $data = [], string $runId = 'pre'): void
{
  $sessionId = '0c7d6b';
  $payload = [
    'sessionId' => $sessionId,
    'id' => 'log_' . uniqid('', true),
    'timestamp' => (int)(microtime(true) * 1000),
    'location' => $location,
    'message' => $message,
    'data' => $data,
    'runId' => $runId,
    'hypothesisId' => $hypothesisId,
  ];

  $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

  // Try writing to the expected file path (runtime evidence).
  $logPath = dirname(__DIR__) . '/debug-0c7d6b.log';
  @file_put_contents($logPath, $json . PHP_EOL, FILE_APPEND);

  // Also POST to the debug ingest endpoint as a fallback transport.
  $endpoint = 'http://127.0.0.1:7548/ingest/8865479e-fa5f-4fa3-b873-997f605975cd';
  $headers = "Content-Type: application/json\r\nX-Debug-Session-Id: {$sessionId}\r\n";
  $opts = [
    'http' => [
      'method' => 'POST',
      'header' => $headers,
      'content' => $json,
      'timeout' => 1,
    ],
  ];
  $context = stream_context_create($opts);
  @file_get_contents($endpoint, false, $context);
}

