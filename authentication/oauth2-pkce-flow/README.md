# Sample for authentication using the PKCE Flow

The Authorization Code Flow with Proof Key for Code Exchange (PKCE) is the OAuth 2.0 grant that native apps use in order to access an API.

More on this flow: <https://www.developer.saxo/openapi/learn/oauth-authorization-code-grant-pkce>

> **Warning**
> For PHP, it is not recommended to use this flow in most setups. Use the [Code Flow](../oauth2-code-flow/) instead. PKCE is just here for demonstration purposes, when you have no other options.

## Steps
1. Get an OpenAPI Developer Account here: <https://www.developer.saxo/accounts/sim/signup>.
2. Navigate to App Management in the Developer Portal: <https://www.developer.saxo/openapi/appmanagement>.
3. Create a PKCE Flow app with a redirect to <http://localhost/openapi-samples-php/authentication/oauth2-pkce-flow/demonstrate-pkce-flow.php>.
4. Modify the server-config file, so it contains your app details.
5. Copy the files in this folder to your webserver (running PHP) and make sure your redirect is active.
6. Navigate to the index.php, sign in and get data from the API.

Rate limits are logged, as well as the X-Correlation response header.

This sample contains the following files:

Filename | Description
---: | ---
server&#x2011;config.php | This is the configuration with the hosts of SIM and Live, and your app details. Add your app details before trying the sample!
index.php | This is the landing file. The file creates the URL, saves the CSRF and verification token in the session and initiates a redirect to the OAuth2 server of Saxo Bank.
demonstrate&#x2011;code&#x2011;flow.php | After the OAuth2, the user is redirected to this file. This file detects errors, if any, it verifies the request using the CSRF token and af all is good, it requests an access token. This access token is used for a request to the API and afterwards, a new access token is requested.
cacert.pem | This is a bundle of X.509 certificates of public Certificate Authorities (CA) required to verify the SSL endpoints. Latest version: <https://curl.haxx.se/docs/caextract.html>

A good tutorial on this grant type: <https://auth0.com/docs/api-auth/tutorials/authorization-code-grant-pkce>
