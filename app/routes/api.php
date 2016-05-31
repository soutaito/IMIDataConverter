<?php
//api
$app->group('/api', function () use ($app) {

	$app->group('/project', function () use ($app) {

		$app->get('/json', function () use ($app) {
			if(empty($_SESSION['project'])){
				return false;
			}
			//format for js
			if (!empty($_SESSION['project']['eg:property'])) {
				$properties = $_SESSION['project']['eg:property'];
				unset($_SESSION['project']['eg:property']);
				$temp = array();
				foreach ($properties as $key1 => $value1) {
					$value1['level'] = 1;
					$temp[] = $value1;
					if (!empty($value1['eg:additional'])) {
						foreach ($value1['eg:additional'] as $key2 => $value2) {
							$value2['level'] = 2;
							$temp[] = $value2;
							if (!empty($value2['eg:additional'])) {
								foreach ($value2['eg:additional'] as $key3 => $value3) {
									$value3['level'] = 3;
									$temp[] = $value3;
								}
							}
						}
					}
				}
				$_SESSION['project']['eg:property'] = $temp;
			}
			$app->response->headers->set('Content-Type', 'application/json');
			$app->response->write(json_encode($_SESSION['project']));
		});

		$app->get('/sheetData', function () use ($app) {
			if(isset($_SESSION['sheetData'])){
				$sheetData = $_SESSION['sheetData'];
				array_splice($sheetData, $app->config('show_row_num'));
				$app->response->headers->set('Content-Type', 'application/json');
				$app->response->write(json_encode($sheetData));
			}else{
				$app->error();
			}
		});

	});

	$app->group('/vocabulary', function () use ($app) {

		$app->get('/:vocabulary/complexType/:complexTypeName', function ($vocabulary = null, $complexTypeName = null) use ($app) {
			if(!$vocabulary || !array_key_exists($vocabulary, $app->config('prefix'))){
				return false;
			}
			$prefix = $app->config('prefix')[$vocabulary];
			$app->response->headers->set('Content-Type', 'application/json');
			$results = getChildElementByComplexTypeName($complexTypeName, $vocabulary, $prefix);
			$app->response->write(json_encode($results));
		});

		$app->get('/:vocabulary/element/:ic', function ($vocabulary = null, $ic = null) use ($app) {
			if(!$vocabulary || !array_key_exists($vocabulary, $app->config('prefix'))){
				return false;
			}
			$prefix = $app->config('prefix')[$vocabulary];
			$filePath = $app->config('schemaPath') . $vocabulary . '.' . $prefix['extension'];
			$xml = file_get_contents($filePath);
			$xmlObject = simplexml_load_string($xml);
			$children = $xmlObject->children('xsd', true);
			$elementName = str_replace($vocabulary . ":", "", $ic);
			$elementType = null;
			foreach ($children->element as $value) {
				if ((string)$value->attributes()->name != $elementName) {
					continue;
				}
				$elementType = $value->attributes()->type;
			}
			$complexTypeName = str_replace($vocabulary . ":", "", $elementType);

			$results = getChildElementByComplexTypeName($complexTypeName, $vocabulary, $prefix);
			$app->response->headers->set('Content-Type', 'application/json');
			$app->response->write(json_encode($results));
		});

		$app->get('/:vocabulary', function ($vocabulary = null) use ($app) {
			if(!$vocabulary || !array_key_exists($vocabulary, $app->config('prefix'))){
				return false;
			}
			$prefix = $app->config('prefix')[$vocabulary];
			$filePath = $app->config('schemaPath') . $vocabulary . '.' . $prefix['extension'];
			$results = array();
			if($prefix['extension'] == 'xsd'){
				$xml = file_get_contents($filePath);
				$xmlObject = simplexml_load_string($xml);
				$namespace = 'xsd';
				if($vocabulary == 'xsd'){
					$namespace = 'xs';
				}
				$children = $xmlObject->children($namespace, true);
				foreach ($children->complexType as $value) {
					$results[] = $vocabulary . ':' . (string)$value->attributes()->name;
				}
			}elseif($prefix['extension'] == 'rdf' || $prefix['extension'] == 'html' || $prefix['extension'] == 'ttl' ){
				$graph = new EasyRdf_Graph();
				$graph->parseFile($filePath);
				$rdfPhp = $graph->toRdfPhp();
				foreach($rdfPhp as $key => $resource){
					if(isset($resource["http://www.w3.org/1999/02/22-rdf-syntax-ns#type"])){
						$type = $resource["http://www.w3.org/1999/02/22-rdf-syntax-ns#type"];
						if(is_array($type)){
							foreach($type as $t){
								if(
									($t['value'] == 'http://www.w3.org/2000/01/rdf-schema#Class' || $t['value'] == 'http://www.w3.org/2002/07/owl#Class')
									&& strpos($key, $prefix['namespace']) === 0
								){
									$key = str_replace($prefix['namespace'], $vocabulary . ':', $key);
									$results[] = $key;
								}
							}
						}
					}
				}
			}else{
				return false;
			}
			$app->response->headers->set('Content-Type', 'application/json');
			$app->response->write(json_encode($results));
		});

		$app->get('/searchlist', function () use ($app) {
			$prefix = $app->config('prefix');
			$results = array();
			foreach($prefix as $vocabulary => $value){
				if($vocabulary == 'ic'){
					$filePath = $app->config('schemaPath') . $vocabulary . '.' . $value['extension'];
					if($value['extension'] == 'xsd'){
						$xml = file_get_contents($filePath);
						$xmlObject = simplexml_load_string($xml);
						$namespace = 'xsd';
						if($vocabulary == 'xsd'){
							$namespace = 'xs';
						}
						$children = $xmlObject->children($namespace, true);
						foreach ($children->complexType as $v) {
							$results[] = $vocabulary . ':' . (string)$v->attributes()->name;
						}
						foreach ($children->simpleType as $v) {
							$results[] = $vocabulary . ':' . (string)$v->attributes()->name;
						}
					}elseif($value['extension'] == 'rdf' || $value['extension'] == 'html'){
						$graph = new EasyRdf_Graph();
						$graph->parseFile($filePath);
						$rdfPhp = $graph->toRdfPhp();
						foreach($rdfPhp as $key => $resource){
							if(strpos($key, $value['namespace']) === 0){
								$key = str_replace($value['namespace'], $vocabulary . ':', $key);
								$results[] = $key;
							}
						}
					}
				}
			}
			$app->response->headers->set('Content-Type', 'application/json');
			$app->response->write(json_encode($results));
		});

	});

});

