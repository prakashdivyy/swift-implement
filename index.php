<?php
require 'vendor/autoload.php';

use \Curl\Curl;

define('SWIFT_USERNAME','grup2:swift');
define('SWIFT_KEY','kXzTDbsG2eq810Hhm6PyU7SpHoUPmeXOHObMoQpg');

$app = new \Slim\Slim();

$curl = new Curl();
$curl->setHeader('X-Auth-User', SWIFT_USERNAME);
$curl->setHeader('X-Auth-Key', SWIFT_KEY);
$curl->get('https://grup2-ceph-04.sisdis.ui.ac.id/auth');

$storageURL = $curl->responseHeaders['X-Storage-Url'];
$storageURL = preg_replace("/^http:/i", "https:", $storageURL);
$storageURL = preg_replace('/:[0-9]+/', '', $storageURL);

$token = $curl->responseHeaders['X-Storage-Token'];

$app->get('/', function () use ($token, $storageURL) {
    $curl = new Curl();
    $curl->setHeader('X-Auth-Token', $token);
    $curl->get($storageURL, array(
        'format' => 'json'
    ));
    echo json_encode($curl->response);
});

$app->get('/uploaded', function () use ($token, $storageURL) {
    $curl = new Curl();
    $curl->setHeader('X-Auth-Token', $token);
    $curl->get($storageURL . '/my-new-bucket', array(
        'format' => 'json'
    ));
    echo json_encode($curl->response);
});

$app->run();
