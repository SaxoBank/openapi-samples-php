<?php

/*
 *  This sample demonstrates the following:
 *    1. Get a token,
 *    2. Request data from the API,
 *    3. Refresh the token.
 *
 *  Rate limits are logged, as well as the X-Correlation response header.
 *
 *  Steps:
 *  1. Get an OpenAPI Developer Account here: https://www.developer.saxo/accounts/sim/signup.
 *  2. Navigate to App Management in the Developer Portal: https://www.developer.saxo/openapi/appmanagement.
 *  3. Create a PKCE Flow app with a redirect to http://localhost/openapi-samples-php/authentication/oauth2-pkce-flow/demonstrate-pkce-flow.php.
 *  4. Modify the server-config file, so it contains your app details.
 *  5. Copy the files in this folder to your webserver (running PHP) and make sure this file is listening to this URL:
 *     http://localhost/openapi-samples-php/authentication/oauth2-pkce-flow/demonstrate-pkce-flow.php
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
    echo '<!DOCTYPE html><html lang="en"><head><title>Basic PHP redirect example of the PKCE flow</title></head><body>';
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
 * @return void
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
 * Initiate cURL.
 * @param string $url The endpoint to call.
 * @return object
 */
function configureCurl($url) {
    $ch = curl_init($url);
    // https://www.php.net/manual/en/function.curl-setopt.php
    curl_setopt_array($ch, [
        CURLOPT_FAILONERROR    => false,  // Required for HTTP error codes to be reported via call to curl_error($ch)
        CURLOPT_SSL_VERIFYPEER => true,  // false to stop cURL from verifying the peer's certificate.
        CURLOPT_CAINFO         => __DIR__ . '/cacert-2022-07-19.pem',  // This Mozilla CA certificate store was generated at Tue Jul 19 03:12:06 2022 GMT and is downloaded from https://curl.haxx.se/docs/caextract.html
        CURLOPT_SSL_VERIFYHOST => 2,  // 2 to verify that a Common Name field or a Subject Alternate Name field in the SSL peer certificate matches the provided hostname.
        CURLOPT_FOLLOWLOCATION => false,  // true to follow any "Location: " header that the server sends as part of the HTTP header.
        CURLOPT_RETURNTRANSFER => true,  // true to return the transfer as a string of the return value of curl_exec() instead of outputting it directly.
        CURLOPT_ENCODING       => 'gzip'  // This enables decoding of the response. Supported encodings are "identity", "deflate", and "gzip". If an empty string is set, a header containing all supported encoding types is sent.
    ]);
    if (defined('CURL_VERSION_HTTP2') && (curl_version()['features'] & CURL_VERSION_HTTP2) !== 0) {
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_VERSION_HTTP2);  // CURL_HTTP_VERSION_2_0 (attempt to use HTTP 2, when available)
    }
    return $ch;
}

/**
 * Log request and response code/headers to track issues with calling the API.
 * @param string $method         HTTP Method.
 * @param string $url            The endpoint.
 * @param object $data           Data to send via the body.
 * @param int $httpCode          HTTP response code.
 * @param array $responseHeaders The response headers, useful for request limits and correlation.
 * @return void
 */
function logRequest($method, $url, $data, $httpCode, $responseHeaders) {
    $xCorrelationHeader = 'x-correlation: ';
    $xCorrelation = '-';
    $xRateLimitAppDayRemainingHeader = 'x-ratelimit-appday-remaining: ';
    $xRateLimitAppDayRemaining = '';
    // Find the x-correlation header, because with this header Saxo might be able to help with trouble shooting.
    // There are rate limits per service group and global ones. Log the x-ratelimit-appday-remaining header.
    foreach ($responseHeaders as $header) {
        if (stripos($header, $xCorrelationHeader) !== false) {
            $xCorrelation = substr($header, strlen($xCorrelationHeader));
        } else if (stripos($header, $xRateLimitAppDayRemainingHeader) !== false) {
            $xRateLimitAppDayRemaining = substr($header, strlen($xRateLimitAppDayRemainingHeader));
        }
    }
    $logLine = $httpCode . ' Request: ' . $method . ' ' . $url . ' x-correlation: ' . $xCorrelation;
    if ($xRateLimitAppDayRemaining !== '') {
        // On errors, this header is not sent to the client
        $logLine .= ' remaining requests today: ' . $xRateLimitAppDayRemaining;
    }
    if ($data != null) {
        $logLine .= ' body: ' . json_encode($data);
    }
    error_log($logLine);  // Location of this log can be found with ini_get('error_log')
    echo $logLine . '<br />';
}

/**
 * Request a token ($postData specifies code, or refresh type).
 * @param array $postData The data body to sent.
 * @return object
 */
function getTokenResponse($postData) {
    global $configuration;
    $logTimeFormat = 'Y-m-d H:i:s';
    $ch = configureCurl($configuration->tokenEndpoint);
    curl_setopt_array($ch, array(
        CURLOPT_POST       => true,
        CURLOPT_POSTFIELDS => $postData
    ));
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    logRequest('POST', $configuration->tokenEndpoint, null, $httpCode, []);
    if ($response === false) {
        // Something bad happened (couldn't reach the server). No internet conection?
        logErrorAndDie('Error connecting to POST ' . $configuration->tokenEndpoint . ': ' . curl_error($ch));
    }
    // If you are looking here, probably something is wrong.
    // Is PHP properly installed (including OpenSSL extension)?
    // Troubleshooting:
    //  You can follow these steps to see what is going wrong:
    //  1. Run PHP in development mode, with warnings displayed, by using the development.ini.
    //  2. Do a var_dump of all variables and exit with "die();"
    if ($httpCode != 201) {
        logErrorAndDie('Error ' . $httpCode . ' while getting a token.');
    }
    $responseJson = json_decode($response);
    if (json_last_error() == JSON_ERROR_NONE) {
        if (property_exists($responseJson, 'error')) {
            logErrorAndDie('Error: <pre>' . $responseJson . '</pre>');
        }
        echo 'New token received: <pre>' . json_encode($responseJson, JSON_PRETTY_PRINT) . '</pre>';
        // Current time might be wrong, when time zone is not set correctly (date_default_timezone_set('UTC'))
        echo 'Token is valid until ' . date($logTimeFormat, strtotime('+' . $responseJson->expires_in . ' sec')) . ' (current time: ' . date($logTimeFormat) . ').<br />';
        echo 'Refresh token is valid until ' . date($logTimeFormat, strtotime('+' . $responseJson->refresh_token_expires_in . ' sec')) . '.<br /><br />';
        if ($responseJson->expires_in < 0) {
            // The expires_in field can be negative. Maybe you experience this during development, but never during production!
            logErrorAndDie('Error: the token is already expired, probably because an old code has been used. Was there a delay between auth and this request?');
        }
        return $responseJson;
    } else {
        // Something bad happened, no JSON in response.
        logErrorAndDie('Error: ' . $response . ' (' . $configuration->tokenEndpoint . ')');
    }
}

/**
 * Return the bearer token.
 * @return object
 */
function getToken() {
    // Getting 401s? Test your challenge here: https://tonyxu-io.github.io/pkce-generator/
    global $configuration;
    $code = getQueryParameter('code', null);
    $verifier = getSessionVariable('verifier', null);
    if ($code === null) {
        logErrorAndDie('Error: No code found in the URL - this is unexpected. Aborting token request.');
    }
    if ($verifier === null) {
        logErrorAndDie('Error: No saved verifier found in the session - this is unexpected. Aborting token request.');
    }
    echo 'Requesting a token with the code from the redirect URL..<br />';
    return getTokenResponse(
        array(
            'grant_type'    => 'authorization_code',
            'client_id'     => $configuration->appKey,
            'redirect_uri'  => $configuration->redirectUri,
            'code'          => $code,
            'code_verifier' => $verifier
        )
    );
}

/**
 * Return an API response, if any.
 * @param string $accessToken Bearer token.
 * @param string $method      HTTP Method.
 * @param string $url         The endpoint.
 * @param object $data        Data to send via the body.
 * @return object
 */
function getApiResponse($accessToken, $method, $url, $data) {
    global $configuration;
    $ch = configureCurl($configuration->openApiBaseUrl . $url);
    $header = array(
        'Authorization: Bearer ' . $accessToken  // CURLOPT_XOAUTH2_BEARER is added in cURL 7.33.0. Available since PHP 7.0.7.
    );
    if ($data != null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));  // The full data to post in a HTTP "POST" operation. This parameter can either be passed as a urlencoded string like 'para1=val1&para2=val2&...' or as an array with the field name as key and field data as value.
        $header[] = 'Content-Type: application/json; charset=utf-8';  // This is different than the token request content!
    }
    curl_setopt_array($ch, array(
        CURLOPT_CUSTOMREQUEST => $method,  // A custom request method to use instead of "GET" or "HEAD" when doing a HTTP request. This is useful for doing "DELETE" or other, more obscure HTTP requests. Valid values are things like "GET", "POST", "CONNECT" and so on; i.e.
        CURLOPT_HEADER        => true,  // true to include the header in the output.
        CURLOPT_ENCODING      => 'gzip',  // This enables decoding of the response. Supported encodings are "identity", "deflate", and "gzip". If an empty string is set, a header containing all supported encoding types is sent.
        CURLOPT_HTTPHEADER    => $header  // An array of HTTP header fields to set, in the format array('Content-type: text/plain', 'Content-length: 100')
    ));
    $response = curl_exec($ch);
    if ($response === false) {
        // Something bad happened (couldn't reach the server). No internet conection?
        logErrorAndDie('Error connecting to ' . $method . ' ' . $url . ': ' . curl_error($ch));
    }
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);  // As of cURL 7.10.8, this is a legacy alias of CURLINFO_RESPONSE_CODE
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    // Separate response header from body
    $headers = explode("\n", substr($response, 0, $header_size));
    logRequest($method, $url, $data, $httpCode, $headers);
    $body = substr($response, $header_size);
    if ($body === '') {
        if ($httpCode >= 200 && $httpCode < 300) {
            // No response body, but response code indicates success https://developer.mozilla.org/en-US/docs/Web/HTTP/Status#successful_responses
            return null;
        } else {
            // Don't quit immediately. Contruct a valid error and continue.
            $body = '{"ErrorCode":"' . $httpCode . '","Message":"' . trim($headers[0]) . '"}';
        }
    }
    $responseJson = json_decode($body);
    if (json_last_error() == JSON_ERROR_NONE) {
        if ($httpCode >= 400) {
            logErrorAndDie('Error: ' . processErrorResponse($responseJson));
        }
        return $responseJson;
    } else {
        // Something bad happened, no JSON in response.
        logErrorAndDie('Error parsing JSON response of request ' . $method . ' ' . $url . ': ' . $body);
    }
}

/**
 * Both UserKey and ClientKey are stored as claims in the token. They are required for many API requests.
 * @param string $accessToken Bearer token.
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
    echo 'Requesting user data from the API..<br />';
    $responseJson = getApiResponse($accessToken, 'GET', '/port/v1/users/' . urlencode(getUserKeyFromToken($accessToken)), null);
    echo 'Response from /users endpoint: <pre>' . json_encode($responseJson, JSON_PRETTY_PRINT) . '</pre><br />';
}

/**
 * (Try to) set the TradeLevel to FullTradingAndChat.
 * @param string $accessToken Bearer token.
 */
function setTradeSession($accessToken) {
    $data = array(
        'TradeLevel' => 'FullTradingAndChat'
    );
    echo 'Elevating Trade Session using PUT..<br />';
    getApiResponse($accessToken, 'PUT', '/root/v1/sessions/capabilities', $data);
    echo 'Elevation of session requested.<br />';
}

/**
 * Return the bearer token
 * @param string $refreshToken This argument must contain the refresh_token.
 * @return object
 */
function refreshToken($refreshToken) {
    global $configuration;
    echo 'Requesting a new token with the refresh_token..<br />';
    return getTokenResponse(
        array(
            'grant_type'    => 'refresh_token',
            'refresh_token' => $refreshToken
        )
    );
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
