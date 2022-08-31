<?php
header("Content-Type: text/plain");

$accessToken = '';
$openApiBaseUrl = 'https://gateway.saxobank.com/sim/openapi';

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
    foreach ($responseHeaders as $header)  {
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
    echo $logLine . "\n";
}

/**
 * Show a message for the user to indicate what happened.
 * @param object $error HTTP Method.
 * @return string
 */
function processErrorResponse($error) {
    if (isset($error->ErrorInfo)) {
        $error = $error->ErrorInfo;
    }
    $result = $error->Message;
    if (isset($error->ModelState)) {
        foreach ($error->ModelState as $modelState)  {
            $result .= "\n" . $modelState[0];
        }
    }
    /*
    {
        "ErrorCode": "IllegalInstrumentId",
        "Message": "Instrument-ID is ongeldig"
    }

    {
        "Message": "One or more properties of the request are invalid!",
        "ModelState": {
            "AssetType": [
                "'Asset Type' must not be empty."
            ],
            "OrderDuration": [
                "The specified condition was not met for 'Order Duration'."
            ]
        },
        "ErrorCode": "InvalidModelState"
    }
    */
    return $result;
}

/**
 * Log an issue to PHP error log and stop further processing.
 * @param string $error The error to be logged.
 * @return void
 */
function logErrorAndDie($error) {
    error_log($error);  // Location of this log can be found with ini_get('error_log')
    // This function should die after an unsuccessful request. But for this demo, it doesn't.
    //die($error);
    echo $error . PHP_EOL;
}

/**
 * Configure the CURL options with SSL verification, GZip compression and HTTP2.
 * @param string $method                    HTTP Method.
 * @param string $url                       The endpoint.
 * @param object $data                      Data to send via the body.
 * @param object $isRequestIdHeaderRequired When true, include a unique numer to prevent 409 Conflict error when two identical orders are placed within 15 seconds.
 * @return object
 */
function configureCurlRequest($method, $url, $data = null, $isRequestIdHeaderRequired = false) {
    global $openApiBaseUrl;
    global $accessToken;
    $ch = curl_init($openApiBaseUrl . $url);
    if (defined('CURL_VERSION_HTTP2') && (curl_version()['features'] & CURL_VERSION_HTTP2) !== 0) {
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_VERSION_HTTP2);  // CURL_HTTP_VERSION_2_0 (attempt to use HTTP 2, when available)
    }
    $header = array(
        'Authorization: Bearer ' . $accessToken  // CURLOPT_XOAUTH2_BEARER is added in cURL 7.33.0. Available since PHP 7.0.7.
    );
    if ($isRequestIdHeaderRequired) {
        $header[] = 'X-Request-ID: ' . random_int(PHP_INT_MIN, PHP_INT_MAX);
    }
    if ($data != null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));  // The full data to post in a HTTP "POST" operation. This parameter can either be passed as a urlencoded string like 'para1=val1&para2=val2&...' or as an array with the field name as key and field data as value.
        $header[] = 'Content-Type: application/json; charset=utf-8';  // This is different than the token request content!
    }
    // https://www.php.net/manual/en/function.curl-setopt.php
    curl_setopt_array($ch, array(
        CURLOPT_FAILONERROR    => false,  // Required for HTTP error codes to be reported via call to curl_error($ch)
        CURLOPT_SSL_VERIFYPEER => true,  // false to stop cURL from verifying the peer's certificate.
        CURLOPT_CAINFO         => __DIR__ . '/cacert-2022-07-19.pem',  // This Mozilla CA certificate store was generated at Tue Jul 19 03:12:06 2022 GMT and is downloaded from https://curl.haxx.se/docs/caextract.html
        CURLOPT_SSL_VERIFYHOST => 2,  // 2 to verify that a Common Name field or a Subject Alternate Name field in the SSL peer certificate matches the provided hostname.
        CURLOPT_FOLLOWLOCATION => false,  // true to follow any "Location: " header that the server sends as part of the HTTP header.
        CURLOPT_RETURNTRANSFER => true,  // true to return the transfer as a string of the return value of curl_exec() instead of outputting it directly.
        CURLOPT_ENCODING       => 'gzip',  // This enables decoding of the response. Supported encodings are "identity", "deflate", and "gzip". If an empty string is set, a header containing all supported encoding types is sent.
        CURLOPT_CUSTOMREQUEST  => $method,  // A custom request method to use instead of "GET" or "HEAD" when doing a HTTP request. This is useful for doing "DELETE" or other, more obscure HTTP requests. Valid values are things like "GET", "POST", "CONNECT" and so on; i.e.
        CURLOPT_HEADER         => true,  // true to include the header in the output.
        CURLOPT_HTTPHEADER     => $header  // An array of HTTP header fields to set, in the format array('Content-type: text/plain', 'Content-length: 100')
    ));
    return $ch;
}

/**
 * Call an endpoint of the OpenAPI.
 * @param string $method                    HTTP Method.
 * @param string $url                       The endpoint.
 * @param object $data                      Data to send via the body.
 * @param object $isRequestIdHeaderRequired When true, include a unique numer to prevent 409 Conflict error when two identical orders are placed within 15 seconds.
 * @return object
 */
function getApiResponse($method, $url, $data = null, $isRequestIdHeaderRequired = false) {
    $ch = configureCurlRequest($method, $url, $data, $isRequestIdHeaderRequired);
    $response = curl_exec($ch);
    if ($response === false) {
        // Something bad happened (couldn't reach the server). No internet conection?
        return logErrorAndDie('Error connecting to ' . $method . ' ' . $url . ': ' . curl_error($ch));
    }
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);  // As of cURL 7.10.8, this is a legacy alias of CURLINFO_RESPONSE_CODE
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    // Separate response header from body
    $headers = explode("\n", substr($response, 0, $header_size));
    $body = substr($response, $header_size);
    logRequest($method, $url, $data, $httpCode, $headers);
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
            return logErrorAndDie('Error: ' . processErrorResponse($responseJson));
        }
        return $responseJson;
    } else {
        // Something bad happened, no JSON in response.
        return logErrorAndDie('Error parsing JSON response of request ' . $method . ' ' . $url . ': ' . $body);
    }
}

/**
 * Trigger 401 Unauthorized.
 */
function trigger401Unauthorized() {
    global $accessToken;
    echo PHP_EOL . 'Trigger 401 Unauthorized:' . PHP_EOL;
    // Replace access token with empty one.
    $accessTokenBackup = $accessToken;
    $accessToken = '';
    getApiResponse('GET', '/ref/v1/instruments/details/21/FxStock');
    // And restore for further requests.
    $accessToken = $accessTokenBackup;
}

/**
 * Trigger 404 Not Found.
 */
function trigger404NotFound() {
    echo PHP_EOL . 'Trigger 404 NotFound:' . PHP_EOL;
    // The regular 404 ('GET', '/ref/v1/invalid', null) returns HTML - this shouldn't be handled in the API.
    getApiResponse('GET', '/ref/v1/instruments/details/123456789/Stock');
}

/**
 * Trigger 400 Bad Request.
 */
function trigger400BadRequest() {
    echo PHP_EOL . 'Trigger 400 BadRequest:' . PHP_EOL;
    getApiResponse('GET', '/ref/v1/instruments?SectorId=Vastgoed&IncludeNonTradable=Ja&CanParticipateInMultiLegOrder=Mag+wel&Uics=N.V.T.&AssetTypes=Aandelen&Tags=Vastgoed&AccountKey=IBAN');
}

/**
 * Trigger 429 TooManyRequests.
 */
function trigger429TooManyRequests() {
    $result = null;
    $requestCount = 0;
    // Ref data can be requested 60 times per minute. Request until this limit is reached.
    echo PHP_EOL . 'Trigger 429 TooManyRequests:' . PHP_EOL;
    do {
        $requestCount += 1;
        echo 'Requesting AEX ref data (request ' . $requestCount. ')..' . PHP_EOL;
        $result = getApiResponse('GET', '/ref/v1/instruments?$top=1&$skip=0&Keywords=aex');
    } while ($result !== null);
}

if ($accessToken === '') {
    // Only for demonstration purposes:
    die('You must add an access (bearer) token first. Get your 24-hour token here https://www.developer.saxo/openapi/token/current, or create an app and request one.');
}
trigger401Unauthorized();
trigger404NotFound();
trigger400BadRequest();
trigger429TooManyRequests();
