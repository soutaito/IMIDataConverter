<?php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/config.php';
require __DIR__ . '/../app/db.php';

$app = new \Slim\Slim($config);

$mongoCollection = getMongoCollection('user');

try{
	$result = $mongoCollection->find();
	echo "username, email \n";
	foreach ($result as $doc) {
		echo $doc['username'] . ', ';
		echo $doc['email'];
		echo "\n";
	}
} catch (RuntimeException $e) {
	echo $e->getMessage() . PHP_EOL;
	return;
}

