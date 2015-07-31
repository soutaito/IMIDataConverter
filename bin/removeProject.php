<?php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/config.php';
require __DIR__ . '/../app/db.php';

$app = new \Slim\Slim($config);

$mongoCollection = getMongoCollection('project');

try{
	$id = $argv[1];

	$result = $mongoCollection->find(array(
		'_id' => new MongoId($id)
	));

	if (!$result->count()) {
		throw new RuntimeException('存在しないプロジェクトです');
	}

	$result = $mongoCollection->remove(array(
		'_id' => new MongoId($id)
	));
} catch (RuntimeException $e) {
	echo $e->getMessage() . PHP_EOL;
	return;
}
echo 'Project:' . $id . ' removed.' . PHP_EOL;