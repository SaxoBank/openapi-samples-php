<?php
header("Content-Type: text/plain");

$accessToken = '';

/**
 * Get token claims.
 * @param string $accessToken The bearer token.
 */
function displayTokenClaims($accessToken) {
    $tokenArray = explode('.', $accessToken);
    $header = json_decode(base64_decode($tokenArray[0]));
    $payload = json_decode(base64_decode($tokenArray[1]));
    echo 'Token: ' . $accessToken . PHP_EOL . PHP_EOL;
    echo 'Header: ' . json_encode($header, JSON_PRETTY_PRINT) . PHP_EOL . PHP_EOL;
    echo 'Payload: ' . json_encode($payload, JSON_PRETTY_PRINT) . PHP_EOL . PHP_EOL;
    echo 'UserKey: ' . $payload->uid . PHP_EOL;
    echo 'ClientKey: ' . $payload->cid . PHP_EOL;
    $expirationDateTime = new DateTime("@$payload->exp", new DateTimeZone('UTC'));
    $now = new DateTime(null, new DateTimeZone('UTC'));
    echo 'Expiration Time (UTC): ' . $expirationDateTime->format('Y-m-d H:i:s') . ' (' . $expirationDateTime->getTimestamp() - $now->getTimestamp() . ' seconds remaining)' . PHP_EOL;
}

if ($accessToken === '') {
    // Only for demonstration purposes:
    die('You must add an access (bearer) token first. Get your 24-hour token here https://www.developer.saxo/openapi/token/current, or create an app and request one.');
}
displayTokenClaims($accessToken);
