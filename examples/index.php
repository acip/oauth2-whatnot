<?php

require __DIR__ . '/../vendor/autoload.php';

use Acip\OAuth2\Client\Provider\Whatnot;
use Acip\OAuth2\Client\Provider\WhatnotMode;

session_start();
header('Content-Type: text/plain');

$clientId = '{whatnot-client-id}';
$clientSecret = '{whatnot-client-secret}';
$redirectUri = 'https://example.com/callback-url';
$mode = WhatnotMode::STAGE;

$provider = new Whatnot(
    [
        'clientId'     => $clientId,
        'clientSecret' => $clientSecret,
        'redirectUri'  => $redirectUri,
    ],
    mode: $mode
);


if (empty($_GET['code'])) {
    // Step 1. redirect to the authorization URL
    $options = [
        // use the 'scope' key to specify the desired scopes
        // see https://developers.whatnot.com/docs/getting-started/authentication#available-scopes
        'scope' => ['read:inventory', 'write:inventory'],
    ];

    $authorizationUrl = $provider->getAuthorizationUrl($options);
    $_SESSION['oauth2state'] = $provider->getState();
    header('Location: ' . $authorizationUrl);
    exit;
} elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {
    // State is invalid, possible CSRF attack in progress
    unset($_SESSION['oauth2state']);
    exit('Invalid state');
} elseif (isset($_GET['code'])) {
    // Step 2. retrieve access and refresh tokens based on the authorization code

    // Try to get an access token (using the authorization code grant)
    $token = $provider->getAccessToken('authorization_code', [
        'code' => $_GET['code'],
        'client_id' => $clientId,
    ]);

    // Optional: Now you have a token you can look up a users profile data
    try {
        // We got an access token, let's now get the user's details
        $resourceOwner = $provider->getResourceOwner($token);

        // Use these details to create a new profile
        printf('Hello %s!', $resourceOwner->getId());
    } catch (Exception $e) {
        // Failed to get user details
        exit('Oh dear... ' . $e->getMessage());
    }
}
