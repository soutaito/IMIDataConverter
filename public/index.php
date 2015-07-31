<?php
//------------------------------------------------------------------------------------
// 設定
//------------------------------------------------------------------------------------

mb_internal_encoding("UTF-8");
date_default_timezone_set('Asia/Tokyo');

ini_set('session.cookie_secure', true);
ini_set('session.cookie_httponly', true);
session_cache_limiter(false);
session_start();

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/functions.php';
require_once __DIR__ . '/../app/utility.php';

$app = new \Slim\Slim($config);

if($app->config('debug') === true){
	//開発
	error_reporting(E_ALL);
	ini_set('display_errors', 1);
	$app->get('/mongo', function () use ($app) {
		$mongoCollection = getMongoCollection('project');
		$result = $mongoCollection->find();
		foreach ($result as $doc) {
			print_r($doc);
		}

		$mongoCollection = getMongoCollection('user');
		$result = $mongoCollection->find();
		foreach ($result as $doc) {
			print_r($doc);
		}

		$mongoCollection = getMongoCollection('tag');
		$result = $mongoCollection->find();
		foreach ($result as $doc) {
			echo print_r($doc);
		}
	});

}else{
	//本番
	error_reporting(E_ALL ^ E_NOTICE ^ E_DEPRECATED);
	ini_set('display_errors', 0);
}

$app->hook('slim.before', function () use ($app) {
	$app->response->headers->set('Cache-Control', 'private, no-store, no-cache, must-revalidate');
	$app->response->headers->set('Pragma', 'no-cache');

	$app->view->appendData(array(
		'product_path' => $app->config('product_path'),
		'static_path' => $app->config('static_path')
	));
});

//------------------------------------------------------------------------------------
// ルーティング　＋　コントローラー
//------------------------------------------------------------------------------------
$app->group(rtrim($app->config('product_path'), '/'), function() use ($app) {
	$app->get('/', function () use ($app) {
		unset($_SESSION['project']);
		unset($_SESSION['sheetData']);
		//connect to mongo
		$mongoCollection = getMongoCollection('project');

		$get = $app->request->get();

		//list
		$list['creator'] = $mongoCollection->distinct('dct:creator');
		$list['tag'] = $mongoCollection->distinct('eg:tag');
		$list['license'] = $mongoCollection->distinct('dct:license');

		//count
		foreach ($list['creator'] as $key => $value) {
			if(is_string($value)){
				$list['creator_count'][$key] = $mongoCollection->count(array('dct:creator' => $value));
			}
		}
		foreach ($list['tag'] as $key => $value) {
			if(is_string($value)){
				$list['tag_count'][$key] = $mongoCollection->count(array('eg:tag' => $value));
			}
		}
		foreach ($list['license'] as $key => $value) {
			if(is_string($value)){
				$list['license_count'][$key] = $mongoCollection->count(array('dct:license' => $value));
			}
		}

		//search
		$conditions = array();

		$creator = '';
		if (isset($get['creator']) && is_string($get['creator']) && in_array($get['creator'], $list['creator']) ) {
			$creator = $get['creator'];
			$conditions['$and'][] = array('dct:creator' => $creator);
		}

		$tag = '';
		if (isset($get['tag']) && is_string($get['tag']) && in_array($get['tag'], $list['tag'])) {
			$tag = $get['tag'];
			$conditions['$and'][] = array('eg:tag' => $tag);
		}

		$license = '';
		if (isset($get['license']) && is_string($get['license']) && array_key_exists($get['license'], $app->config('license'))) {
			$license = $get['license'];
			$conditions['$and'][] = array('dct:license' => $license);
		}

		$keyword = '';
		if (isset($get['keyword']) && is_string($get['keyword'])) {
			$keyword = $get['keyword'];
			$regex = new MongoRegex('/' . preg_quote($keyword) . '/');
			$conditions['$and'][] = array('$or' => array(
				array('eg:keyword' => $regex),
				array('dct:description' => $regex),
				array('rdfs:label' => $regex)));
		}

		$cursor = $mongoCollection->find($conditions);
		$cursor->sort(array('dct:created' => -1));

		//paging
		$count = $cursor->count();
		$cursor->limit($app->config('perpage'));
		if (isset($get['page']) && (int)$get['page'] > 0) {
			$cursor->skip($app->config('perpage') * ((int)$get['page'] - 1));
		}

		$results = array();
		foreach ($cursor as $document) {
			$results[] = $document;
		}

		$path = $app->request()->getPath();
		$perpage = $app->config('perpage');
		$app->render('index.php', compact('list', 'results', 'count', 'creator', 'tag', 'license', 'keyword',  'path', 'perpage'));
	});

	$app->get('/complete', function () use ($app) {
		$app->render('complete.php');
	});

	$app->get('/login', function () use ($app) {
		$app->render('login.php');
	});

	$app->post('/login', function () use ($app) {
		try {
			$post = $app->request->post();
			if(!(isset($post['email']) && is_string($post['email'])
			     && isset($post['password']) && is_string($post['password']))
			){
				throw new RuntimeException('メールアドレスまたはパスワードに誤りがあります');
			}


			$mongoCollection = getMongoCollection('user');

			foreach($post as $key => $value){
				$encode = mb_detect_encoding( $value, array( 'UTF-8' ) );
				if($encode !== 'UTF-8'){
					throw new RuntimeException( 'メールアドレスまたはパスワードに誤りがあります' );
				}
			}

			$result = $mongoCollection->find(
				array(
					'email' => $post['email'],
					'password' => getPasswordHash($post['email'], $post['password'], $app->config('salt'))
				)
			);
			if ($result->count()) {
				session_regenerate_id(true);
				$user = $result->next();
				$_SESSION['user'] = $user;
				$_SESSION['expires'] = time() + (int)$app->config('timeout');
				$app->flash('info', 'ログインしました。');
				$app->getLog()->info('ユーザー名「' . $user['username'] . '」（メールアドレス"' . $user['email'] . '"）がログインしました。');
				$app->redirect($app->config('static_path'));
			}else{
				throw new RuntimeException('メールアドレスまたはパスワードに誤りがあります');
			}
		} catch (RuntimeException $e) {
			$app->flash('error', $e->getMessage());
			$app->redirect($app->config('static_path') . 'login');
		}
	});

	$app->map('/logout', function () use ($app) {
		if(isset($_SESSION['user'])){
			$app->getLog()->info('ユーザー名「' . $_SESSION['user']['username'] . '」（メールアドレス"' . $_SESSION['user']['email'] . '"）がログアウトしました。');
		}
		session_destroy();
		session_regenerate_id(true);
		session_start();
		$app->flash('info', 'ログアウトしました。');
		$app->redirect($app->config('static_path'));
	})->via('GET', 'POST');

	require_once __DIR__ . '/../app/routes/project.php';
	require_once __DIR__ . '/../app/routes/api.php';
});


$app->notFound(function () use ($app) {
        $app->log->debug('Result(404):REQUEST_URI=' . $_SERVER['REQUEST_URI']);
	$app->render('404.php');
});

$app->error(function () use ($app) {
        $app->log->debug('Error(500):REQUEST_URI=' . $_SERVER['REQUEST_URI']);
	$app->render('error.php');
});

$app->run();
