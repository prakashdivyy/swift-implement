<?php
require 'vendor/autoload.php';

use \HPCloud\Bootstrap;
use \HPCloud\Storage\ObjectStorage;
use \HPCloud\Storage\ObjectStorage\Object;
//use \HPCloud\Storage\ObjectStorage\ACL;

Bootstrap::useAutoloader();

define('SWIFT_USERNAME', 'grup2:swift');
define('SWIFT_KEY', 'kXzTDbsG2eq810Hhm6PyU7SpHoUPmeXOHObMoQpg');
define('SWIFT_AUTH_URL', 'https://grup2-ceph-04.sisdis.ui.ac.id/auth');
define('BUCKET_NAME', 'imp-swift');

$Connection = ObjectStorage::newFromSwiftAuth(SWIFT_USERNAME, SWIFT_KEY, SWIFT_AUTH_URL);
$container = $Connection->container(BUCKET_NAME);


$app = new \Slim\Slim();

$app->get('/', function () use ($app) {
    $app->render('home.php', array('error' => 0));
});

$app->post('/', function () use ($app, $container) {
    $filename = basename($_FILES["fileToUpload"]["name"]);
    $exist = count($container->objectsWithPrefix($filename));
    if($exist){
        $app->render('home.php', array('error' => 1));
    } else{
        $res=  fopen($_FILES["fileToUpload"]["tmp_name"], 'r');
        $localObject = new Object($filename);
        $container->save($localObject, $res);
        $uploadURL = $container->objectsWithPrefix($filename);
        $uploadURL = $uploadURL[0]->url();
        $app->render('success.php', array('url' => $uploadURL, 'filename' => $filename));
    }
});

//$app->get('/setup', function () use ($Connection) {
    //$acl = ACL::makePublic();
    //$Connection->deleteContainer('my-new-bucket');
    //$Connection->createContainer('my-new-bucket', $acl);
//});

$app->get('/gallery', function () use ($app, $container){
    $ConnectionListResponse = $container->objects();
    $app->render('gallery.php',array('Objects' => $ConnectionListResponse));
});
$app->post('/copyFile', function () use ($app, $container, $Connection){
    $filename = $app->request->params('filename');
    $bucket_source = $app->request->params('bucket_source');
    $filename_new = $app->request->params('filename_new');
    $bucket_destination = $app->request->params('bucket_destination');
    $object = $container->remoteObject($filename);
    $copy = $container->copy($object,$filename_new,$bucket_destination);
    if ($copy) {
        $app->redirect('gallery');
    } else {
        $app->redirect('gallery');
    }
});
$app->get('/copy/:filename', function ($filename) use ($app, $container, $Connection){
    $buckets = $Connection->containers();
    $exist = count($container->objectsWithPrefix($filename));
    if ($exist) {
        $app->render('copy.php', array('filename' => $filename,'bucket_source' => BUCKET_NAME, 'Buckets' => $buckets));
    }
});
$app->get('/delete/:filename', function ($filename) use ($app, $container){
    $exist = count($container->objectsWithPrefix($filename));
    if ($exist) {
        $delete = $container->delete($filename);
        $ConnectionListResponse = $container->objects();
        if ($delete) {
            $app->render('gallery.php',array('filename' => $filename,'success' => 1, 'Objects' => $ConnectionListResponse));    
        } else {
            $app->render('gallery.php',array('filename' => $filename,'success' => 0, 'Objects' => $ConnectionListResponse));
        }
    } 
});

$app->run();
