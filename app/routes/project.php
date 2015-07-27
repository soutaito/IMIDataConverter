<?php
$app->group('/project', function () use ($app) {
	$app->get('/:id', function ($id) use ($app) {
		$project = getProjectOr404($id);
		if(!empty($_SESSION['user']) && $project['dct:creator'] == $_SESSION['user']['username']){
			$is_my_project =  true;
		}else{
			$is_my_project =  false;
		}
		$app->render('project_single.php', compact('project', 'is_my_project'));
	})->conditions(array('id' => '[a-f0-9]{24}'));

	$app->post('/', function () use ($app) {
		$post = $app->request->post();
		if($_SESSION['action'] !== 'express'){
			authenticateUser();
			checkToken($post, $app);
			saveProject($post);
		}
		if(isset($post['format'])){
			$_SESSION['format'] = $post['format'];
		}else{
			$_SESSION['format'] = null;
		}
		if($_SESSION['action'] == 'edit'){
			$app->flash('info', 'プロジェクトの編集を完了しました');
			$app->redirect($app->config('static_path'));
		}else{
			$app->redirect($app->config('static_path') . 'complete');
		}
	});

	$app->get('/excerpt/:id', function ($id) use ($app) {
		$project = getProjectOr404($id);
		$on_new_project =  true;
		$layout = false;
		$app->render('elements/project_preview.php', compact('project', 'on_new_project', 'layout'));
	})->conditions(array('id' => '[a-f0-9]{24}'));;

	$app->get('/new', 'authenticateUser', function () use ($app) {
		if ( isset( $_SESSION['sheetData'][0] ) ) {
			$data = array();
			$data['show_row_num'] = $app->config('show_row_num');
			$tempHeader       = $_SESSION['sheetData'][0];
			if(!is_array($tempHeader)){
				return false;
			}
			$mongoCollection  = getMongoCollection( 'project' );
			$ops              = array(
				array(
					'$match' => array(
						'eg:headerLabel' => array( '$in' => $tempHeader )
					)
				),
				array( '$unwind' => '$eg:headerLabel' ),
				array(
					'$match' => array(
						'eg:headerLabel' => array( '$in' => $tempHeader )
					)
				),
				array(
					'$group' => array(
						'_id'   => array(
							'$_id',
							'rdfs:label' => '$rdfs:label'
						),
						'count' => array( '$sum' => 1 )
					),
				),
				array(
					'$match' => array( 'count' => array( '$gte' => 2 ) ),
				),
				array(
					'$sort' => array( "count" => - 1 ),
				),
				array(
					'$limit' => 10,
				)
			);
			$result           = $mongoCollection->aggregate( $ops )['result'];
			$data['projects'] = $result;
			$data['_token'] = publishToken();
			$data = array_merge($data, $_SESSION);
			$app->render('project.php', $data);
		}else{
			$app->flash('error', '不正なリクエストです');
			$app->redirect($app->config('static_path'));
		}
	});

	$app->get('/fork', 'authenticateUser', function () use ($app) {
		if(isset($_SESSION['id'])){
			$data = array();
			$data['show_row_num'] = $app->config('show_row_num');
			$data['project'] = getProjectOr404($_SESSION['id']);
			$data = array_merge($data, $_SESSION);
			$data['_token'] = publishToken();
			$app->render('project.php', $data);
		}else{
			$app->flash('error', '不正なリクエストです');
			$app->redirect($app->config('static_path'));
		}
	});

	$app->get('/edit', 'authenticateUser', function () use ($app) {
		if(isset($_SESSION['id'])){
			$data = array();
			$data['show_row_num'] = $app->config('show_row_num');
			$data['project'] = getProjectOr404($_SESSION['id']);
			$data = array_merge($data, $_SESSION);
			$data['_token'] = publishToken();
			$app->render('project.php', $data);
		}else{
			$app->flash('error', '不正なリクエストです');
			$app->redirect($app->config('static_path'));
		}
	});

	$app->get('/remove/:id', 'authenticateUser', function ($id) use ($app) {
		handleUserProject($id);
		$app->flash('info', 'プロジェクトを削除しました。');
		$app->redirect($app->config('static_path'));
	})->conditions(array('id' => '[a-f0-9]{24}'));

	$app->get('/input', 'authenticateUser', function () use ($app) {
		if(isset($_SESSION['project'])){
			$data = array();
			$mongoCollection = getMongoCollection('tag');
			$cursor = $mongoCollection->find();
			foreach ($cursor as $document) {
				$data['tag'] = $document;
			}
			$data['license'] = $app->config('license');
			$data['_token'] = publishToken();
			$data = array_merge($_SESSION, $data);
			$app->render('project_input.php', $data);
		}else{
			$app->notFound();
		}
	});

	$app->post('/mapping','authenticateUser', function () use ($app) {
		$post = $app->request->post();
		checkToken($post, $app);
		$is_error = false;
		if (empty($post['rdfs:label'])) {
			$is_error = true;
			$app->flash('error', 'プロジェクトタイトルは必須です');
		}else if(!is_string($post['rdfs:label'])){
			$is_error = true;
			$app->flash('error', '不正な値が送信されました。');
		}else if(mb_strlen($post['rdfs:label']) > 100){
			$is_error = true;
			$app->flash('error', 'プロジェクトタイトルが長すぎます');
		}else if(preg_match('/(\<|\>|\&|\"|\')/',$post['rdfs:label']) === 1){
			$is_error = true;
			$app->flash('error', '不正な文字が使用されています');
		}
		if($is_error){
			$app->redirect($app->config('static_path') . 'project/' . $_SESSION['action']);
		}
		switch($_SESSION['action']){
			case 'fork':
				$_SESSION['project'] = getProjectOr404($_SESSION['id']);
				$_SESSION['project']['eg:headerLabel'] = array();
				$_SESSION['project']['dct:created'] = new MongoDate(time());
				break;
			case 'edit':
				$_SESSION['project'] = getProjectOr404($_SESSION['id']);
				$_SESSION['project']['dct:modified'] = new MongoDate(time());
				break;
			case 'new':
			default:
				if(isset($post['fork'])){
					$_SESSION['project'] = getProjectOr404($post['fork']);
				}else{
					$_SESSION['project'] = array();
					$_SESSION['project']['eg:uri'] = "http://example.com/";
					$_SESSION['project']['eg:definitionType'] = 'row';
					$_SESSION['project']['eg:subject'] = array();
					$_SESSION['project']['eg:subject']['eg:namingRule'] = 'column';
					$_SESSION['project']['eg:vocabulary'] = array();
					$_SESSION['project']['eg:vocabulary']['eg:uri'] = 'http://example.com/';
					$_SESSION['project']['eg:headerLabel'] = array();
					$_SESSION['project']['dct:created'] = new MongoDate(time());
				}
				break;
		}
		$_SESSION['project']['rdfs:label'] = $post['rdfs:label'];
		$data = array(
			'api_component' => $app->config('api_component'),
			'prefix' => $app->config('prefix')
		);
		$data['_token'] = publishToken();
		$app->render('mapping.php', $data);
	});

	$app->post('/mapping/save', 'authenticateUser', function () use ($app) {
		$post = $app->request->post();
		checkToken($post, $app);
		if(
			! (
				isset($post['project']) && is_array($post['project'])
				&& isset($post['mapping']) && is_array($post['mapping'])
			)
		){
			$app->response()->setStatus(400);
			$app->response()->write('マッピング情報を設定してください。');
			$app->response()->headers->set('Content-Type', 'text/plain');
			$app->response()->finalize();return;
		}

		foreach ($post['mapping'] as $key => $value) {
			if (
				isset($value['eg:APIComponent']) && $value['eg:APIComponent'] === 'external' &&
				isset($value['eg:APIComponentURL']) && $value['eg:APIComponentURL'] != '' &&
				filter_var($value['eg:APIComponentURL'], FILTER_VALIDATE_URL) === false
			) {
				$app->response()->setStatus(400);
				$app->response()->write('外部APIの値がURIとして正しくない形式です。');
				$app->response()->headers->set('Content-Type', 'text/plain');
				$app->response()->finalize();return;
			}
		}

		$_SESSION['project'] = $post['project'];

		$property = array();
		$l1_key = 0;
		$l2_key = 0;
		$l3_key = 0;
		foreach ($post['mapping'] as $key => $value) {
			if ($value['level'] == 1) {
				$property[$l1_key] = $value;
				if (!empty($post['mapping'][$key + 1]) && $post['mapping'][$key + 1]['level'] == 1) {
					$l1_key++;
				}
			}
			if ($value['level'] == 2) {
				$property[$l1_key]['eg:additional'][$l2_key] = $value;
				if (!empty($post['mapping'][$key + 1]) && $post['mapping'][$key + 1]['level'] == 1) {
					$l1_key++;
					$l2_key = 0;
				}
				if (!empty($post['mapping'][$key + 1]) && $post['mapping'][$key + 1]['level'] == 2) {
					$l2_key++;
					$l3_key = 0;
				}
			}
			if ($value['level'] == 3) {
				$property[$l1_key]['eg:additional'][$l2_key]['eg:additional'][$l3_key] = $value;
				if (!empty($post['mapping'][$key + 1]) && $post['mapping'][$key + 1]['level'] == 1) {
					$l1_key++;
					$l2_key = 0;
					$l3_key = 0;
				}
				if (!empty($post['mapping'][$key + 1]) && $post['mapping'][$key + 1]['level'] == 2) {
					$l2_key++;
					$l3_key = 0;
				}
				if (!empty($post['mapping'][$key + 1]) && $post['mapping'][$key + 1]['level'] == 3) {
					$l3_key++;
				}
			}
		}
		$_SESSION['project']['eg:property'] = $property;

	});

	$app->get('/download/:id', function ($id) use ($app) {
		$project = getProjectOr404($id);
		if (isset($project['eg:headerLabel']) && is_array($project['eg:headerLabel'])) {
			if (isset($_GET['type']) && $_GET['type'] == 'xlsx') {
				$column = 0;
				$objPHPExcel = new PHPExcel();
				$objPHPExcel->setActiveSheetIndex(0);
				foreach ($project['eg:headerLabel'] as $label) {
					$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($column, 1, $label);
					$column++;
				}
				$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
				$app->response->headers->set( 'Content-Type',  'application/vnd.ms-excel' );
				$app->response->headers->set( 'Content-Disposition', 'attachment; filename="form.xlsx"' );
				$objWriter->save('php://output');
				return;
			} else {
				$label = array();
				foreach($project['eg:headerLabel'] as $l){
					$label[] = mb_convert_encoding($l, 'SJIS-win', 'UTF-8');
				}
				$app->response->headers->set( 'Content-Type',  'application/octet-stream' );
				$app->response->headers->set( 'Content-Disposition', 'attachment; filename="form.csv"' );
				fputcsv(fopen("php://output", 'w'), $label);
				return;
			}
		} else {
			$app->flash('error', 'ヘッダーラベルが登録されていないプロジェクトです');
			$app->redirect($app->config('static_path'));
		}
	})->conditions(array('id' => '[a-f0-9]{24}'));
});

$app->group('/data', function () use ($app) {

	$app->get('/upload/express/:id', function ($id) use ($app) {
		$data = array();
		$_SESSION['action'] = 'express';
		$_SESSION['project'] = getProjectOr404($id);
		$_SESSION['id'] = $id;
		$data['_token'] = publishToken();
		$data = array_merge($data, $_SESSION);
		$app->render('upload.php', $data);
	})->conditions(array('id' => '[a-f0-9]{24}'));

	$app->get('/upload(/:action)(/:id)', 'authenticateUser', function ($action = 'new', $id = null) use ($app) {
		$data = array();
		$_SESSION['action'] = $action;
		switch($action){
			case 'fork' :
				$_SESSION['project'] = getProjectOr404($id);
				$_SESSION['id'] = $id;
				break;
			case 'edit' :
				if(preg_match('/[a-f0-9]{24}/', $id)){
					$_SESSION['project'] = getProjectOr404($id);
					if(!empty($_SESSION['user']) && $_SESSION['project']['dct:creator'] == $_SESSION['user']['username']){
						$_SESSION['id'] = $id;
					}else{
						$app->flash('error', 'あなたの作成したプロジェクトではありません');
						$app->redirect($app->config('static_path'));
					}
				}else{
					$app->flash('error', '不正なプロジェクトIDです');
					$app->redirect($app->config('static_path'));
				}
				break;
			case 'new' :
				unset($_SESSION['project']);
				break;
			default :
				$app->notFound();
		}
		$data['_token'] = publishToken();
		$data = array_merge($data, $_SESSION);
		$app->render('upload.php', $data);
	});

	$app->post('/upload/',  function () use ($app) {
		if(isset($_SESSION['action'])){
			$post = $app->request->post();
			checkToken($post, $app);
			if($_SESSION['action'] != 'express'){
				authenticateUser();
			}
			try {
				uploadTablefile();
			} catch (RuntimeException $e) {
				$app->getLog()->error($e->getMessage());
				$app->redirect($app->config('static_path'));
			}
			switch($_SESSION['action']){
				case 'fork' :
					$project_page = 'project/fork';
					break;
				case 'edit' :
					$project_page = 'project/edit';
					break;
				case 'express' :
					$project_page = 'data/select';
					break;
				case 'new' :
				default:
					$project_page = 'project/new';
					break;
			}
			$app->redirect($app->config('static_path') . $project_page);
		}else{
			$app->notFound();
		}
	});

	$app->get('/select', function () use ($app) {
		if(isset($_SESSION['project'])){
			$data = array();
			$data = array_merge($_SESSION, $data);
			$app->render('project_input.php', $data);
		}else{
			$app->notFound();
		}
	});

	$app->get('/export', function () use ($app) {
		if(isset($_SESSION['project'])){
			$format = $_SESSION['format'];
			try{
				downloadZip($format);
			} catch(RuntimeException $e){
				$app->getLog()->error($e->getMessage());
				$app->redirect($app->config('static_path'));
			}
		}else{
			$app->notFound();
		}
	});

});

