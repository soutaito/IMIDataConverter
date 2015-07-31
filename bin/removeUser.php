<?php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/config.php';
require __DIR__ . '/../app/db.php';

$app = new \Slim\Slim($config);

$mongoCollection = getMongoCollection('user');
try{
	$username = $argv[1];
	$result = $mongoCollection->find(array('username' => $argv[1]));

	if (!$result->count()) {
		throw new RuntimeException('存在しないユーザーです');
	}

	$result = $mongoCollection->remove(array(
		'username' => $username
	));
} catch (RuntimeException $e) {
	echo $e->getMessage() . PHP_EOL;
	return;
}
echo 'User:' . $username . ' removed.' . PHP_EOL;