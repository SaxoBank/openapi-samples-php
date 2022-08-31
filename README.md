# OpenAPI PHP Sample Repository

This repository contains sample files demonstrating OpenAPI interactions in PHP.

Since this is only backend, we recommend to take a look at the more detailed (and also web based) samples of the [JavaScript repository](https://saxobank.github.io/openapi-samples-js/).

This repository contains sample code for developers working with [Saxo's OpenAPI](https://www.home.saxo/platforms/api) using PHP.

To get started, make sure you:

1. [Create a (free) developer account](https://www.developer.saxo/accounts/sim/signup) on the Developer Portal.
2. Check out the [Reference Documentation](https://www.developer.saxo/openapi/referencedocs) and [Learn](https://www.developer.saxo/openapi/learn) sections.
3. Create an app in [Application Management](https://www.developer.saxo/openapi/appmanagement), or obtain a [24-hour access token](https://www.developer.saxo/openapi/token/current) when required.

## Requirements

Besides the developer account:
- cURL is used for the samples. GZip and HTTP/2 when supported.
- Supported PHP versions: PHP 7 and higher. [A version of PHP with active support is strongly advised](https://www.php.net/supported-versions.php).

## Table of Contents

1. Authentication
   - [OAuth2 Code Flow for websites](authentication/oauth2-code-flow/)
   - [OAuth2 PKCE Flow for single page apps](authentication/oauth2-pkce-flow/) (only when Code Flow is not possible)
   - [OAuth2 Certificate Based Flow](authentication/oauth2-certificate-flow/) (only for certain Saxo partners)
2. API requests
   - [Stock Orders](orders/)
3. Basics
   - [Token Info (get Lifetime, UserKey, ClientKey)](token-info/)
   - [Error Handling](error-handling/)

Suggestions? Comments? Reach us via Github or [openapisupport@saxobank.com](mailto:openapisupport@saxobank.com).
