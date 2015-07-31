<?php

$config = array(
		'product_path' => '/',
		'static_path' => '/',
		'debug' => true,
		'cookies.secure' => true,
		'cookies.httponly' => true,
		'view' => '\Slim\LayoutView',
		'templates.path' => __DIR__ . '/../views/',
		'uploadPath' => __DIR__ . '/../uploaded/',
		'schemaPath' => __DIR__ . '/../schema/',
                'mongo_username' => 'imitool',
                'mongo_password' => 'imitool',
		'mongo_db' => 'imitool',
		'log.enabled' => true,
		'log.writer' => new \Slim\Logger\DateTimeFileWriter(array(
			'path' => __DIR__ . '/../logs/',
		)),
		'log.level' => \Slim\Log::DEBUG,
		'salt' => 'e80efe9f2a5af77556bc283db1ca9eae6b158218',
		'timeout' => 60 * 30,
		'tool_name' => 'IMI Data Converter',
		'tool_version' => '1.0',
		'template_version' => '1.0',
		'prefix' => array(
			'ic' => array(
				'name' => '共通語彙基盤 コア語彙 2（バージョン2.2）',
				'namespace' => 'http://imi.ipa.go.jp/ns/core/2',
				'url' => 'http://imi.ipa.go.jp/ns/core/2',
				'accept' => 'application/xml',
				'extension' => 'xsd'
			),
			'rdfs' => array(
				'name' => 'The RDF Schema Vocabulary (RDFS)',
				'namespace' => 'http://www.w3.org/2000/01/rdf-schema#',
				'url' => 'http://www.w3.org/2000/01/rdf-schema#',
				'accept' => 'application/rdf+xml',
				'extension' => 'rdf'
			),
			'dct' => array(
				'name' => 'DCMI Metadata Terms',
				'namespace' => 'http://purl.org/dc/terms/',
				'url' => 'http://purl.org/dc/terms/',
				'accept' => 'application/rdf+xml',
				'extension' => 'rdf'
			),
			'xsd' => array(
				'name' => 'XML Schema',
				'namespace' => 'http://www.w3.org/2001/XMLSchema#',
				'url' => 'http://www.w3.org/2001/XMLSchema#',
				'accept' => 'application/xml',
				'extension' => 'xsd'
			),
			'foaf' => array(
				'name' => 'FOAF',
				'namespace' => 'http://xmlns.com/foaf/0.1/',
				'url' => 'http://xmlns.com/foaf/0.1/',
				'accept' => 'application/rdf+xml',
				'extension' => 'rdf'
			),
			'owl' => array(
				'name' => 'The OWL 2 Schema vocabulary (OWL 2)',
				'namespace' => 'http://www.w3.org/2002/07/owl#',
				'url' => 'http://www.w3.org/2002/07/owl#',
				'accept' => 'application/rdf+xml',
				'extension' => 'rdf'
			),
			'rdf' => array(
				'name' => 'The RDF Concepts Vocabulary (RDF)',
				'namespace' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
				'url' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
				'accept' => 'application/rdf+xml',
				'extension' => 'rdf'
			),
			'skos' => array(
				'name' => 'SKOS Vocabulary',
				'namespace' => 'http://www.w3.org/2004/02/skos/core#',
				'url' => 'http://www.w3.org/2004/02/skos/core#',
				'accept' => 'application/rdf+xml',
				'extension' => 'rdf'
			),
			'schema' => array(
				'name' => 'Schema.org',
				'namespace' => 'http://schema.org/',
				'url' => 'http://schema.org/docs/schema_org_rdfa.html',
				'accept' => 'text/html',
				'extension' => 'html'
			)
		),
		'show_row_num' => 5,
		'perpage' => 10,
		'tag' => array(
			'戸籍・住民票・印鑑登録', 'ごみ・資源', '税金', '健康・医療', '医療費助成', '国民健康保険', '国民年金', '介護保険', '食品・ペット・生活衛生', '文化・スポーツ・生涯学習', '青少年・多文化共生', '相談窓口', '地域活動', '道路・下水・公園', '統計・選挙', '赤ちゃん・子ども・家庭', '高齢者の方', '障害者の方', 'お役立ち情報', '都市計画・まちづくり'
		),
		'license' => array(
			'CC BY 4.0' => 'https://creativecommons.org/licenses/cc-by/4.0/',
			'CC BY-SA 4.0' => 'https://creativecommons.org/licenses/cc-by-sa/4.0/',
			'CC0' => 'http://creativecommons.org/publicdomain/zero/1.0/'
		),
		'api_component' => array(
			'address' => array(
				'name' => '住所整形API',
				'type' => 'internal',
				'call' => function ($address)  {
					require_once __DIR__ . '/api/getImiAddresses.php';
					$imiAddress = new getImiAddresses(__DIR__ . '/api/data/');
					$imiAddress->doAnalyzeAddresses($address);
					return $imiAddress->getOutput();
				},
				'method' => 'get',
			),
		),
		'api_request_limit' => 100,
	);
