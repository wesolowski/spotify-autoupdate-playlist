<?php

require_once __DIR__ . '/../vendor/autoload.php';

$config = include __DIR__ . '/../config.php';


$session = new SpotifyWebAPI\Session(
    $config['clientId'],
    $config['clientSecret'],
    $config['redirectUri']
);

$api = new SpotifyWebAPI\SpotifyWebAPI();

if (isset($_GET['code'])) {
    $playlistName = trim($config['playlist_name']);
    $playlistId = false;
    $session->requestAccessToken($_GET['code']);

    file_put_contents(__DIR__ . '/../token.txt', $session->getRefreshToken());
} else {
    $options = [
        'scope' => [
            'playlist-modify-public',
        ],
    ];

    header('Location: ' . $session->getAuthorizeUrl($options));
    die();
}