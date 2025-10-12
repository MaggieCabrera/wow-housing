<?php
// Simple configuration for site-wide secret protection.
// Edit the value of $SITE_SECRET to the short secret you want to require.
// Warning: Choose a secret that is not publicly guessable. This is a simple protection
// intended for small, internal sites. For production use, consider stronger auth.

// You can set this value to any short string, e.g. 'mysecret123'.
$SITE_SECRET = 'SECRET_GOES_HERE';

// Cookie name used for authenticated sessions
$SITE_COOKIE_NAME = 'housing_auth';

// Cookie lifetime (seconds). 0 means session cookie.
$SITE_COOKIE_LIFETIME = 0; // session cookie

// HMAC salt (used to sign the cookie). Leave as-is unless you know what you're doing.
$SITE_COOKIE_SALT = 'housing_cookie_salt_v1';

// Optional: set to true to require secure cookies (HTTPS). On local HTTP dev, keep false.
$SITE_COOKIE_SECURE = false;

// Optional: restrict path for cookie
$SITE_COOKIE_PATH = '/';

?>