<?php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/config.php';
require __DIR__ . '/../app/db.php';

$app = new \Slim\Slim($config);

$mongoCollection = getMongoCollection('tag');
$mongoCollection->drop();
$tags = $app->config('tag');
$mongoCollection->save(array('tag' => $tags));

$mongoCollection = getMongoCollection('user');
$mongoCollection->drop();

$mongoCollection = getMongoCollection('project');
$mongoCollection->drop();

$prefix = $app->config('prefix');
foreach($prefix as $key => $val) {
	$options = array(
		'http' => array(
			'method'=>'GET',
			'header'=>'Accept: ' . $val['accept']
		)
	);
	$context = stream_context_create( $options );
	try{
		$schema = file_get_contents( $val['url'], FALSE, $context );
		if($schema){
			file_put_contents( $app->config('schemaPath') . $key . '.' . $val['extension'], $schema );
		}
	} catch (RuntimeException $e) {
		echo $e->getMessage() . PHP_EOL;
		return;
	}
}

echo 'initialized.' . PHP_EOL;
