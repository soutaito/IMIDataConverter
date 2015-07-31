<?php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/config.php';
require __DIR__ . '/../app/db.php';

$app = new \Slim\Slim($config);

$mongoCollection = getMongoCollection('tag');
$mongoCollection->drop();
$tags = $app->config('tag');

try{
	$mongoCollection->save(array('tag' => $tags));
} catch (RuntimeException $e) {
	echo $e->getMessage() . PHP_EOL;
	return;
}

echo 'Tag updated.' . PHP_EOL;
