<?php
require_once __DIR__ . '/../vendor/autoload.php';


$config = include __DIR__ . '/../config.php';

$refrshTocken = file_get_contents(__DIR__ . '/../token.txt');;

$playlistName = trim($config['playlist_name']);
$playlistId = false;

$session = new SpotifyWebAPI\Session(
    $config['clientId'],
    $config['clientSecret'],
    $config['redirectUri']
);

$api = new SpotifyWebAPI\SpotifyWebAPI();

$session->refreshAccessToken($refrshTocken);
$api->setAccessToken($session->getAccessToken());

$playLists = $api->getUserPlaylists($config['user']);

if(isset($playLists->items ) && !empty($playLists->items) ) {
    $items = (array)$playLists->items;
    foreach ($items as $item) {

        if(trim($item->name) === $playlistName) {
            $playlistId = $item->id;
            break;
        }
    }
}

if($playlistId === false) {
    throw new RuntimeException('Playlist '.$playlistName.' not found');
}

$playlistInfo = $api->getUserPlaylist($config['user'],$playlistId);

$desc = $playlistInfo->description;
$posForUpdate = strpos($desc, 'Update:');
if($posForUpdate) {
    $desc = substr($desc, 0, $posForUpdate);
}
$desc .= ' Update: ' . date('d.m.Y H:i');
$api->updateUserPlaylist($config['user'],$playlistId, [
    'description' => $desc
]);

$playlistSongs = $api->getUserPlaylistTracks($config['user'],$playlistId )->items;
$songToDelete = [];
foreach ($playlistInfo->tracks->items as $song) {
    $songToDelete[]['id'] = $song->track->id;
}

if(!empty($songToDelete)) {
    $api->deleteUserPlaylistTracks($config['user'],$playlistId, $songToDelete);
}

/* Use internal libxml errors -- turn on in production, off for debugging */
libxml_use_internal_errors(true);

/* Createa a new DomDocument object */
$dom = new DomDocument;
/* Load the HTML */
$dom->loadHTMLFile("https://www.eska.pl/2xgoraca20");
/* Create a new XPath object */
$xpath = new DomXPath($dom);
/* Query all <td> nodes containing specified class name */
$nodes = $xpath->query('//div[@class="single-hit__info"]');
/* Set HTTP response header to plain text for debugging output */
/* Traverse the DOMNodeList object to output each DomNode's nodeValue */

class Audio
{
    public $song;
    public $artist;
}

$infos = [];
foreach ($nodes as $node) {
    $info = new Audio();
    $info->song = trim($node->getElementsByTagName('a')[0]->nodeValue);
    $info->artist = trim($node->getElementsByTagName('a')[1]->nodeValue);
    $infos[] = $info;
    dump($info);
}


$tractIds = [];
foreach ($infos as $info) {
    $searchResult = $api->search(sprintf('track:%s artist:%s', $info->song, $info->artist), ['track']);
    if (isset($searchResult->tracks, $searchResult->tracks->items) && !empty($searchResult->tracks->items)) {
        $tractIds[] = $searchResult->tracks->items[0]->id;
    }
}

$api->addUserPlaylistTracks($config['user'], $playlistId, $tractIds);



