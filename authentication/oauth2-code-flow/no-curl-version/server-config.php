<?php

/*
 *
 * Settings file:
 * This is the file config.php, containing the configuration of the API.
 *
 * appKey: The client identification of your app, supplied by Saxo (Client ID)
 * clientSecret: The secret which gives access to the API (Client Secret)
 * tokenEndpoint: The URL of the authentication provider (https://www.developer.saxo/openapi/learn/environments)
 *
 * IMPORTANT NOTICE:
 * The following credentials give access to SIM, when the redirect URL is http://localhost/openapi-samples-js/authentication/oauth2-code-flow/redirect/ (PHP example).
 * If you want to use your own redirect URL, you must create your own Code Flow application:
 * https://www.developer.saxo/openapi/appmanagement.
 * And needless to say, when you have an app for Live, don't publish the credentials on Github!
 *
 */

// Configuration for Simulation (SIM):
$configuration = json_decode('{
    "appKey": "Your app key",
    "appSecret": "Your app key",
    "redirectUri": "http://localhost/openapi-samples-php/authentication/oauth2-code-flow/no-curl-version/demonstrate-code-flow.php",
    "authEndpoint": "https://sim.logonvalidation.net/authorize",
    "tokenEndpoint": "https://sim.logonvalidation.net/token",
    "openApiBaseUrl": "https://gateway.saxobank.com/sim/openapi"
}');

// Configuration for Live:
/*
$configuration = json_decode('{
    "appKey": "Your app key",
    "appSecret": "Your app secret",
    "redirectUri": "http://localhost/openapi-samples-php/authentication/oauth2-code-flow/no-curl-version/demonstrate-code-flow.php",
    "authEndpoint": "https://live.logonvalidation.net/authorize",
    "tokenEndpoint": "https://live.logonvalidation.net/token",
    "openApiBaseUrl": "https://gateway.saxobank.com/openapi"
}');
*/
