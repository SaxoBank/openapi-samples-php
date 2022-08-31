# Examples on retrieving and refreshing the authentication token

Examples on
   - [OAuth2 Code Flow](oauth2-code-flow/)
   - [OAuth2 PKCE Flow](oauth2-pkce-flow/) (only when Code Flow is not possible)
   - [OAuth2 Certificate Based Flow](oauth2-certificate-flow/) (only for certain Saxo partners)

The token is valid for 20 minutes.

The refresh token is valid for 1 hour. With this token you can request a new token.

This can be repeated for a long time, until:
- Customer decides to sign out from all open sessions. This is an option in SaxoTraderGO.
- Saxo has maintenance in the weekend and systems are down for more than one hour.

> **Warning**
> For PHP, it is not recommended to use the PKCE flow in most setups. Use the [Code Flow](oauth2-code-flow/) instead. PKCE is just here for demonstration purposes, when you have no other options.
