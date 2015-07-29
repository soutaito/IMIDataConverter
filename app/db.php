<?php
function getMongoCollection($collectionName) {
	$app = \Slim\Slim::getInstance();
	try {
		//connect to mongo
		$username = $app->config( 'mongo_username' );
		$password = $app->config( 'mongo_password' );
		$db       = $app->config( 'mongo_db' );
		$mongoClient = new MongoClient( "mongodb://${username}:${password}@localhost/${db}" );
		$mongoDB         = $mongoClient->$db;
		$mongoCollection = $mongoDB->$collectionName;
		return $mongoCollection;
	} catch ( MongoException $e){
		$app->getLog()->error($e->getMessage());
		$app->flashNow('error', 'エラーが発生しました');
		throw new ErrorException();
	}
}

function  getProjectOr404($id){
	$project        = getProjectByID($id);
	if($project){
		return $project;
	}else{
		$app = \Slim\Slim::getInstance();
		$app->flash('error', '存在しないか、取得できないプロジェクトです');
		$app->notFound();
		return false;
	}
}

function getProjectByID($id) {
	$mongoCollection = getMongoCollection('project');
	$project        = $mongoCollection->findOne( array( '$and' => array( array ( '_id' => new MongoId( $id ) ) ) ) );
	return $project;
}

function saveProject($post){
	if(!is_array($post) || !isset($_SESSION['project']) || !is_array( $_SESSION['project'] ) ){
		return false;
	}

	$app = \Slim\Slim::getInstance();
	$has_error = false;
	$error_message = array();
	foreach($post as $p => $v){
		switch($p){
			case 'eg:uri':
				if (filter_var($v, FILTER_VALIDATE_URL) === true) {
					$_SESSION['project']['eg:uri'] = $v ;
				} else {
					$has_error = true;
					$error_message[] = 'URLとして形式が正しくありません。';
				}
				break;
			case 'dct:created':
				$_SESSION['project']['dct:created'] = $v ;
				break;
			case 'eg:definitionType':
				if(in_array($v, array('row', 'column', 'constant', 'increment'))){
					$_SESSION['project']['eg:definitionType'] = explode( ',', $v ) ;
				}else{
					$has_error = true;
					$error_message[] = '不正な値が送信されました';
				}
				break;
			case 'eg:subject':
				if(is_string($v)) {
					$has_error = true;
					$error_message[] = 'タイトルに不正な値が送信されました';
				}else if(mb_strlen($v) >= 100){
					$has_error = true;
					$error_message[] = 'タイトルが長すぎます';
				}else if(preg_match('/(\<|\>|\&|\"|\')/',$v) === 1){
					$has_error = true;
					$error_message[] = 'タイトルに<,>,&,\',",は使用できません';
				}else{
					$_SESSION['project']['eg:subject'] = $v ;
				}
				break;
			case 'eg:property':
				$_SESSION['project']['eg:property'] = $v ;
				break;
			case 'dct:description':
				if(!is_string($v)) {
					$has_error = true;
					$error_message[] = '説明文に不正な値が送信されました';
				}else if(mb_strlen($v) >= 500){
					$has_error = true;
					$error_message[] = '説明文が長すぎます';
				}else if(preg_match('/(\<|\>|\&|\"|\')/',$v) === 1){
					$has_error = true;
					$error_message[] = '説明文に<,>,&,\',",は使用できません';
				}else{
					$_SESSION['project']['dct:description'] = $v;
				}
				break;
			case 'eg:keyword':
				if(!empty($v)){
					$_SESSION['project']['eg:keyword'] = explode( ',', $v) ;
				}
				break;
			case 'eg:tag':
				if(is_array($v)){
					$_SESSION['project']['eg:tag'] = array();
					foreach($v as $tag){
						if(in_array($tag, $app->config('tag'))){
							$_SESSION['project']['eg:tag'][] = $tag ;
						}else{
							$has_error = true;
							$error_message[] = 'タグに不正な値が送信されました';
						}
					}
				}else{
					$has_error = true;
					$error_message[] = 'タグに不正な値が送信されました';
				}
				break;
			case 'dct:license':
				if(array_key_exists($v, $app->config('license'))){
					$_SESSION['project']['dct:license'] = $v ;
				}else{
					$has_error = true;
					$error_message[] = 'ライセンスに不正な値が送信されました';
				}
				break;
		}
	}
	if($has_error){
		$app->flash('error', implode($error_message, "\n"));
		$app->redirect($app->config('static_path') . 'project/input');
		return false;
	}

	$_SESSION['project']['dct:creator'] = $_SESSION['user']['username'];
	$mongoCollection = getMongoCollection('project');
	unset($_SESSION['project']['_id']);
	if(isset($_SESSION['action']) && $_SESSION['action'] == 'edit'){
		$id = $_SESSION['id'];
		$mongoCollection->update(array(
			'_id' => new MongoId($id)
		), $_SESSION['project'] );
	}else{
		$mongoCollection->save( $_SESSION['project'] );
	}
}

function handleUserProject($id){
	$app = \Slim\Slim::getInstance();
	$project = getProjectOr404($id);
	if(!empty($_SESSION['user']) && $project['dct:creator'] == $_SESSION['user']['username']){
		removeUserProject($id);
	}else{
		$app->flash('error', 'あなたの作成したプロジェクトではありません');
		$app->redirect($app->config('static_path') . '');
	}
}

function removeUserProject($id){
	$mongoCollection = getMongoCollection('project');
	$result = $mongoCollection->remove(array(
		'_id' => new MongoId($id)
	));
	$app = \Slim\Slim::getInstance();
	if(!$result){
		$app->flash('error', '削除できませんでした');
	}else{
		$app->flash('info', 'プロジェクトを削除しました');
	}
	$app->redirect($app->config('static_path') . '');
}

