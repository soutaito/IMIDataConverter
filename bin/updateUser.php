<?php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/config.php';
require __DIR__ . '/../app/db.php';
require __DIR__ . '/../app/utility.php';

$app = new \Slim\Slim($config);

$mongoCollection = getMongoCollection('user');

try{
	$username = $argv[1];
	$email = $argv[2];
	$password = $argv[3];

	$result = $mongoCollection->find(array('username' => $username));
	if (!$result->count()) {
		throw new RuntimeException('存在しないユーザーです');
	}

	$result = $mongoCollection->find(array(
		'email' => $email,
		'username' =>  array('$ne' => $username),
	));
	if ($result->count()) {
		throw new RuntimeException('メールアドレスの重複はできません');
	}

	$mongoCollection->update(
		array('username' => $username),
		array(
			'username' => $username,
			'email' => $email,
			'password' => getPasswordHash($email, $password, $app->config('salt'))
		)
	);
} catch (RuntimeException $e) {
	echo $e->getMessage() . PHP_EOL;
	return;
}

echo 'User:' . $username . ' updated.' . PHP_EOL;