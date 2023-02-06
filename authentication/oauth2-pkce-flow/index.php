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
 *     http://localhost/openapi-samples-php/authentication/oauth2-pkce-flow/index.php
 *  6. Navigate to this file, sign in and get data from the API.
 *
 *  What this file does:
 *  1. It generates a CSRF token and a Verifier token and stores these in the session.
 *  2. It uses the verifier to create the challenge.
 *  3. It constructs the OAuth2 URL including the challenge.
 *  4. It initiates a '302 Found' redirect to this URL.
 *
 */

// Load the file with the app settings:
require __DIR__ . '/server-config.php';

/**
 * Generate a random string, using a cryptographically secure pseudorandom number generator (random_int)
 * A CSRF (Cross Site Request Forgery) Token is a secret, unique and unpredictable value an application generates in order to protect CSRF vulnerable resources.
 *
 * For PHP 7, random_int is a PHP core function
 * For PHP 5.x, depends on https://github.com/paragonie/random_compat
 *
 * @param string $keyspace A string of all possible characters to select from.
 * @param int $length      How many characters do we want?
 * @return string
 */
function generateRandomToken($keyspace, $length) {
    if ($keyspace === '') {
        return bin2hex(random_bytes($length));
    }
    $pieces = [];
    $max = strlen($keyspace) - 1;
    for ($i = 0; $i < $length; ++$i) {
        $pieces []= $keyspace[random_int(0, $max)];
    }
    return implode('', $pieces);
}

/**
 * The CSRF token is saved in the session, so it can be compared with the CSRF token coming from the state after a redirect.
 * @param string $codeVerifier The code verifier to use to hash the code.
 * @param string $csrfToken    The token to verify the redirect origin.
 */
function addVerifiersToSession($codeVerifier, $csrfToken) {
    // Store the new CSRF van Verification tokens in the session, so it can be used after the redirect.
    // https://www.php.net/manual/en/session.configuration.php
    session_start(['use_strict_mode' => true, 'cookie_httponly' => true]);  // The CSRF token is stored in the session.
    $_SESSION['csrf'] = $csrfToken;
    $_SESSION['verifier'] = $codeVerifier;
}

/**
 * Encode the verifier so it can be used in the URL.
 * @param string $text The plain text to encode.
 * @return string
 */
function base64url_encode($text) {
    $base64 = trim(base64_encode($text), "=");
    return strtr($base64, '+/', '-_');
}

/**
 * Construct the URL for a new login.
 * @param string $codeVerifier The verifier used to create the challenge.
 * @param string $csrfToken    The token to verify the redirect origin.
 * @param string $data         Some data to submit, where $_SESSION can be used as well - added for demonstration purposes.
 * @return string
 */
function generateUrl($codeVerifier, $csrfToken, $data) {
    global $configuration;
    // The CSRF token is part of the state and passed as base64 encoded string.
    // https://auth0.com/docs/protocols/oauth2/oauth-state
    $state = base64_encode(json_encode(array(
        'data' => $data,
        'csrf' => $csrfToken
    )));
    $codeChallenge = base64url_encode(pack('H*', hash('sha256', $codeVerifier)));
    // The link differs per session. You can create a permalink using a redirect to this variable link.
    return $configuration->authEndpoint . '?client_id=' . $configuration->appKey . '&response_type=code&state=' . urlencode($state) . '&redirect_uri=' . urlencode($configuration->redirectUri) . '&code_challenge_method=S256&code_challenge=' . $codeChallenge;
}

/**
 * Redirect the page to the login page of SaxoBank.
 * @param string $url The destination URL.
 */
function initiateRedirect($url) {
    header('Location: ' . $url);
}

// High-entropy cryptographic random STRING using the unreserved characters [A-Z] / [a-z] / [0-9] / "-" / "." / "_" / "~",
// with a minimum length of 43 characters and a maximum length of 128 characters.
$newCodeVerifier = generateRandomToken('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-._~', 100);
$newCsrfToken = generateRandomToken('', 24);
addVerifiersToSession($newCodeVerifier, $newCsrfToken);
$urlForNewLogin = generateUrl($newCodeVerifier, $newCsrfToken, '[Something to remember]');
initiateRedirect($urlForNewLogin);
