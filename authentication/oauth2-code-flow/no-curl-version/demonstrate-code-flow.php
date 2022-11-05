<?php

/*
 *  This sample demonstrates the following:
 *    1. Get a token,
 *    2. Request data from the API,
 *    3. Refresh the token.
 *
 *  Rate limits are logged, as well as the X-Correlation response header.
 *
 *  IMPORTANT: Use this sample only if you have no CURL support, because it has serious downsides, like no HTTP2 support!
 *
 *  Steps:
 *  1. Get an OpenAPI Developer Account here: https://www.developer.saxo/accounts/sim/signup.
 *  2. Navigate to App Management in the Developer Portal: https://www.developer.saxo/openapi/appmanagement.
 *  3. Create a Code Flow app with a redirect to http://localhost/openapi-samples-php/authentication/oauth2-code-flow/no-curl-version/demonstrate-code-flow.php.
 *  4. Modify the server-config file, so it contains your app details.
 *  5. Copy the files in this folder to your webserver (running PHP) and make sure this file is listening to this URL:
 *     http://localhost/openapi-samples-php/authentication/oauth2-code-flow/no-curl-version/demonstrate-code-flow.php
 *  6. Navigate to this file, sign in and get data from the API.
 *
 *  What this file does:
 *  1. It determines if there were no errors in the OAuth2 process, by looking at the query parameters.
 *  2. It determines if this is a valid redirect, by comparing the previously generated CSRF token with the received one.
 *  3. It requests a bearer token.
 *  4. It extracts the UserKey from the token and requests user data from the API.
 *  5. It changes the trade session to demonstrate a PUT request.
 *  6. It refreshes the token, which it normally done just before token expiry and at least before refresh token expiry.
 *
 */

// Load the file with the app settings:
require __DIR__ . '/server-config.php';

/**
 * Display the header of the HTML, including the link with CSRF token in the state.
 */
function printHeader() {
    echo '<!DOCTYPE html><html lang="en"><head><title>Basic PHP redirect example of the code flow</title></head><body>';
    echo 'Initiate a sign in using this link:<br /><a href="index.php">index.php</a><br /><br /><br />';
}

/**
 * Display the footer of the HTML.
 */
function printFooter() {
    echo '</body></html>';
}

/**
 * Log an issue to PHP error log and stop further processing.
 * @param string $error The error to be logged.
 * @throws Exception
 */
function logErrorAndDie($error) {
    error_log($error);  // Location of this log can be found with ini_get('error_log')
    throw new Exception($error);
}

/**
 * Verify if no error was returned.
 */
function checkForErrors() {
    $error = getQueryParameter('error', null);
    $error_description = getQueryParameter('error_description', null);
    if ($error !== null || $error_description !== null) {
        // Something went wrong. Maybe the login failed?
        logErrorAndDie('Auth Error: ' . $error . ' ' . $error_description);
    }
    echo 'No error found in the redirect URL, so we can validate the CSRF token in the state parameter.<br />';
}

/**
 * Get a query parameter from the URL and default if not available.
 * @param string $key The name of the variable.
 * @param mixed $defaultValue The value to return if not available.
 * @return mixed
 */
function getQueryParameter($key, $defaultValue) {
    $result = filter_input(INPUT_GET, $key, FILTER_SANITIZE_URL);
    return (
        $result === false
        ? $defaultValue
        : $result
    );
}

/**
 * Get a variable from the session and default if not set.
 * @param string $key The name of the variable.
 * @param mixed $defaultValue The value to return if not set.
 * @return mixed
 */
function getSessionVariable($key, $defaultValue) {
    return (
        isset($_SESSION[$key])
        ? $_SESSION[$key]
        : $defaultValue
    );
}

/**
 * Verify the CSRF token.
 */
function checkCsrfToken() {
    $receivedState = getQueryParameter('state', null);
    $expectedCsrfToken = getSessionVariable('csrf', null);
    if ($receivedState === null) {
        logErrorAndDie('Error: No state found in the URL - this is unexpected. Aborting token request.');
    }
    if ($expectedCsrfToken === null) {
        logErrorAndDie('Error: No saved state found in the session - this is unexpected. Aborting token request.');
    }
    $receivedStateObjectString = base64_decode($receivedState);
    $receivedStateObject = json_decode($receivedStateObjectString);
    if (json_last_error() == JSON_ERROR_NONE) {
        if ($receivedStateObject->csrf != $expectedCsrfToken) {
            logErrorAndDie('Error: The generated csrfToken (' . $expectedCsrfToken . ') differs from the csrfToken in the state (' . $receivedStateObject->csrf . '). This can indicate a malicious request (or was the state set in a different session?). Stop further processing and redirect back to the authentication.');
        }
        echo 'CSRF token in the state parameter is available and expected, so the redirect is trusted and a token can be requested.<br />';
        echo 'Data submitted via the state: ' . $receivedStateObject->data . '<br />';
    } else {
        logErrorAndDie('Error: Invalid state found - this is unexpected. Aborting token request.');
    }
}

/**
 * Create the context for the HTTP request, including SSL verification.
 * @param string $method    HTTP Method.
 * @param array $header     The endpoint.
 * @param string|null $data Data to submit via the body.
 * @return array
 */
function createRequestContext($method, $header, $data) {
    $http = array(
                'method' => $method,
                'header' => $header,
                'ignore_errors' => false
            );
    if ($data !== null) {
        $http['content'] = $data;
    }
    return array(
            'http' => $http,
            'ssl' => array(
                // This Mozilla CA certificate store was generated at Tue Jul 19 03:12:06 2022 GMT and is downloaded from https://curl.haxx.se/docs/caextract.html
                'cafile' => __DIR__ . '/cacert-2022-07-19.pem',
                'verify_peer' => true,
                'verify_peer_name' => true
            )
        );
}

/**
 * Log request and response code/headers to track issues with calling the API.
 * @param string $url            The endpoint.
 * @param array $context         Request context.
 * @param array $responseHeaders The response headers, useful for request limits and correlation.
 */
function logRequest($url, $context, $responseHeaders) {
    global $configuration;
    $xCorrelationHeader = 'x-correlation: ';
    $xCorrelation = '-';
    $xRateLimitAppDayRemainingHeader = 'x-ratelimit-appday-remaining: ';
    $xRateLimitAppDayRemaining = '';
    // Find the x-correlation header, because with this header Saxo might be able to help with trouble shooting.
    // There are rate limits per service group and global ones. Log the x-ratelimit-appday-remaining header.
    foreach ($responseHeaders as $header)  {
        if (stripos($header, $xCorrelationHeader) !== false) {
            $xCorrelation = substr($header, strlen($xCorrelationHeader));
        } else if (stripos($header, $xRateLimitAppDayRemainingHeader) !== false) {
            $xRateLimitAppDayRemaining = substr($header, strlen($xRateLimitAppDayRemainingHeader));
        }
    }
    $logLine = $responseHeaders[0] . ' Request: ' . $context['http']['method'] . ' ' . $url . ' x-correlation: ' . $xCorrelation;
    if ($xRateLimitAppDayRemaining !== '') {
        // On errors, this header is not sent to the client
        $logLine .= ' remaining requests today: ' . $xRateLimitAppDayRemaining;
    }
    // Don't log data of OAuth2 requests, because of sensitive information!
    if ($url !== $configuration->tokenEndpoint && array_key_exists('content', $context['http'])) {
        $logLine .= ' body: ' . json_encode($context['http']['content']);
    }
    error_log($logLine);  // Location of this log can be found with ini_get('error_log')
    echo $logLine . '<br />';
}

/**
 * Request data from Saxo.
 * @param string $url    The endpoint.
 * @param array $context Request context.
 * @return object|null
 */
function doRequest($url, $context) {
    $result = @file_get_contents($url, false, stream_context_create($context));
    logRequest($url, $context, $http_response_header);
    if (!$result) {
        if ($http_response_header[0] == 'HTTP/1.1 201 Created' || $http_response_header[0] == 'HTTP/1.1 202 Accepted') {
            // No response is expected.
            return null;
        }
        logErrorAndDie('Error: ' . error_get_last()['message'] . ' (' . $url . ')');
    }
    $responseJson = json_decode($result);
    if (json_last_error() == JSON_ERROR_NONE) {
        if (property_exists($responseJson, 'error')) {
            logErrorAndDie('Error: <pre>' . $responseJson . '</pre>');
        }
        return $responseJson;
    } else {
        // Something bad happened, no JSON in response.
        logErrorAndDie('Error: ' . $result . ' (' . $url . ')');
    }
}

/**
 * Return the bearer token
 * @return object
 */
function getToken() {
    global $configuration;
    $logTimeFormat = 'Y-m-d H:i:s';
    $code = getQueryParameter('code', null);
    if ($code === null) {
        logErrorAndDie('Error: No code found in the URL - this is unexpected. Aborting token request.');
    }
    $header = array(
        'Content-Type: application/x-www-form-urlencoded'
    );
    $data = array(
        'grant_type' => 'authorization_code',
        'client_id' => $configuration->appKey,
        'client_secret' => $configuration->appSecret,
        'code' => $code
    );
    $context = createRequestContext('POST', $header, http_build_query($data));
    // If you are looking here, probably something is wrong.
    // Is PHP properly installed (including OpenSSL extension)?
    // Troubleshooting:
    //  You can follow these steps to see what is going wrong:
    //  1. Run PHP in development mode, with warnings displayed, by using the development.ini.
    //  2. Remove the @ before "file_get_contents".
    //  3. Echo the $result and exit with "die();":
    //     $result = file_get_contents($configuration->tokenEndpoint, false, $context);
    //     echo $result;
    //     die();
    echo 'Requesting token..<br />';
    $responseJson = doRequest($configuration->tokenEndpoint, $context);
    echo 'New token from code: <pre>' . json_encode($responseJson, JSON_PRETTY_PRINT) . '</pre>';
    // Current time might be wrong, when time zone is not set correctly (date_default_timezone_set('UTC'))
    echo 'Token is valid until ' . date($logTimeFormat, strtotime('+' . $responseJson->expires_in . ' sec')) . ' (current time: ' . date($logTimeFormat) . ').<br />';
    echo 'Refresh token is valid until ' . date($logTimeFormat, strtotime('+' . $responseJson->refresh_token_expires_in . ' sec')) . '.<br /><br />';
    if ($responseJson->expires_in < 0) {
        // The expires_in field can be negative. Maybe you experience this during development, but never during production!
        logErrorAndDie('Error: the token is already expired, probably because an old code has been used. Was there a delay between auth and this request?');
    }
    return $responseJson;
}

/**
 * Both UserKey and ClientKey are stored as claims in the token. They are required for many API requests.
 * @param string $accessToken Bearer token.
 * @return string
 */
function getUserKeyFromToken($accessToken) {
    $tokenArray = explode('.', $accessToken);
    $payload = json_decode(base64_decode($tokenArray[1]));
    return $payload->uid;  // ClientId is $payload->cid
}

/**
 * Request user data, usually the first request, to get the locale.
 * @param string $accessToken Bearer token.
 */
function getUserFromApi($accessToken) {
    global $configuration;
    $header = array(
        'Authorization: Bearer ' . $accessToken
    );
    $context = createRequestContext('GET', $header, null);
    echo 'Requesting user data from the API..<br />';
    $responseJson = doRequest($configuration->openApiBaseUrl . '/port/v1/users/' . urlencode(getUserKeyFromToken($accessToken)), $context);
    echo 'Response from /users endpoint: <pre>' . json_encode($responseJson, JSON_PRETTY_PRINT) . '</pre><br />';
}

/**
 * (Try to) set the TradeLevel to FullTradingAndChat.
 * @param string $accessToken Bearer token.
 */
function setTradeSession($accessToken) {
    global $configuration;
    $header = array(
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json; charset=utf-8'  // This is different than the token request content!
    );
    $data = array(
        'TradeLevel' => 'FullTradingAndChat'
    );
    $context = createRequestContext('PUT', $header, json_encode($data));
    echo 'Elevating Trade Session using PUT..<br />';
    doRequest($configuration->openApiBaseUrl . '/root/v1/sessions/capabilities', $context);
    echo 'Elevation of session requested.<br />';
}

/**
 * Return the bearer token
 * @param string $refreshToken This argument must contain the refresh_token.
 * @return object
 */
function refreshToken($refreshToken) {
    global $configuration;
    $logTimeFormat = 'Y-m-d H:i:s';
    $header = array(
        'Content-Type: application/x-www-form-urlencoded'
    );
    $data = array(
        'grant_type' => 'refresh_token',
        'client_id' => $configuration->appKey,
        'client_secret' => $configuration->appSecret,
        'refresh_token' => $refreshToken
    );
    $context = createRequestContext('POST', $header, http_build_query($data));
    echo 'Refreshing token..<br />';
    $responseJson = doRequest($configuration->tokenEndpoint, $context);
    echo 'New token from refresh: <pre>' . json_encode($responseJson, JSON_PRETTY_PRINT) . '</pre>';
    // Current time might be wrong, when time zone is not set correctly (date_default_timezone_set('UTC'))
    echo 'Token is valid until ' . date($logTimeFormat, strtotime('+' . $responseJson->expires_in . ' sec')) . ' (current time: ' . date($logTimeFormat) . ').<br />';
    echo 'Refresh token is valid until ' . date($logTimeFormat, strtotime('+' . $responseJson->refresh_token_expires_in . ' sec')) . '.<br /><br />';
    if ($responseJson->expires_in < 0) {
        // The expires_in field can be negative. Maybe you experience this during development, but never during production!
        logErrorAndDie('Error: the token is already expired, probably because an old code has been used. Was there a delay between auth and this request?');
    }
    return $responseJson;
}

session_start(['use_strict_mode' => true, 'cookie_httponly' => true]);  // The CSRF token is stored in the session.
printHeader();
try {
    checkForErrors();
    checkCsrfToken();
    $tokenObject = getToken();
    getUserFromApi($tokenObject->access_token);
    setTradeSession($tokenObject->access_token);
    $tokenObject = refreshToken($tokenObject->refresh_token);
} catch (Exception $ex) {
    echo $ex;
} finally {
    printFooter();
}
