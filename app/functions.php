<?php
function authenticateUser() {
	$app = \Slim\Slim::getInstance();
	if(empty($_SESSION['user'])){
		$app->flash('error', 'ログインが必要な領域です。');
		$app->redirect($app->config('static_path') . 'login');
	}else if($_SESSION['expires'] < time()){
		session_destroy();
		session_regenerate_id(true);
		session_start();
		$app->flash('error', 'ログインの有効期限が切れました');
		$app->redirect($app->config('static_path') . 'login');
	}else{
		$_SESSION['expires'] = time() + (int)$app->config('timeout');
	}
};

function checkToken($post, $app){
	if(empty($post['_token']) || session_id() !== $post['_token']){
		$app->flash('error', '何か不正なフローがありました。もう一度始めからお試し下さい。');
		$app->redirect($app->config('static_path') . '');
	}
}

function downloadZip($format = null){

	$app = \Slim\Slim::getInstance();
	$extension = array(
		'xml' => 'xml',
		'rdfxml' => 'xml',
		'jsonld' => 'json',
		'turtle' => 'ttl',
		'ntriples' => 'nt'
	);
	if(!array_key_exists($format, $extension)){
		$app->flash('error', '指定された出力形式が不正です。');
		throw new RuntimeException('指定された出力形式が不正です。');
	}
	$zip = new ZipArchive();
	$zipFileName = 'data.zip';
	$zipFilePath = $app->config( 'uploadPath' ) ;

	$check = $zip->open($zipFilePath.$zipFileName, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE);
	if ($check !== true) {
		$app->flash('error', 'zipファイルの作成に失敗しました');
		throw new RuntimeException('zipファイルの作成に失敗しました');
	}

	if($format == 'xml'){
		$zip->addFromString('schema.xsd' , getSchema());
		$xml = new XMLConverter();
		$output = $xml->getXML();
	}else{
		$rdf = new RDFConverter();
		$rdf->parseGraph($format);
		$output = $rdf->output();
	}
	$zip->addFromString('convertedData.'  . $extension[$format] , $output);
	$zip->addFromString('header.xml' , dmdHeaderXML());
	$zip->addFromString('header.ttl' , dmdHeaderRDF());
	$zip->addFromString('mapping.json' , getMapping());

	$zip->close();

	$app->response->headers->set( 'Content-Type', 'application/zip; name="' . $zipFileName . '"' );
	$app->response->headers->set( 'Content-Disposition', 'attachment; filename="' . $zipFileName . '"' );
	$app->response->headers->set( 'Content-Length', filesize($zipFilePath.$zipFileName) );
	$app->response->write( file_get_contents($zipFilePath.$zipFileName));

	unlink($zipFilePath.$zipFileName);
}

//------------------------------------------------------------------------------------
// ic関連
//------------------------------------------------------------------------------------
function getChildElementByComplexTypeName($complexTypeName, $vocabulary, $prefix){
	$app = \Slim\Slim::getInstance();

	$filePath = $app->config('schemaPath') . $vocabulary . '.' . $prefix['extension'];
	$xml       = file_get_contents( $filePath );
	$xmlObject = simplexml_load_string( $xml );
	$namespace = 'xsd';
	if($vocabulary == 'xsd'){
		$namespace = 'xs';
	}
	$children  = $xmlObject->children( $namespace, true );
	$propertyElements = $children->element;
	$temp = array();
	$temp['element'] = array();
	foreach ( $children->complexType as $value ) {
		if ( (string) $value->attributes()->name != $complexTypeName ) {
			continue;
		}
		$temp['name'] = (string) $value->attributes()->name;
		if(isset($value->complexContent->extension)){
			$extensionName = $value->complexContent->extension->attributes()->base;
			$extensionChildElements = getChildElementByComplexTypeName(str_replace($vocabulary . ":", "", $extensionName), $vocabulary, $prefix);
			if(!empty($extensionChildElements['element'])){
				$temp['element'] = array_merge($temp['element'],$extensionChildElements['element']);
			}
		}
		if ( ! empty( $value->complexContent ) && isset($value->complexContent->extension->sequence->group)) {
			$groupRef = str_replace('ic:', '', $value->complexContent->extension->sequence->group->attributes()->ref);
			$elements = null;
			foreach ( $children->group as $group ) {
				if ( (string) $group->attributes()->name === $groupRef ) {
					$elements = $group->sequence->element;
					break;
				}
			}
			if(is_object($elements)){
				foreach ( (object) $elements as $element ) {
					$ref                       = (string) $element->attributes()->ref;
					$type = null;
					foreach ( $propertyElements as $propertyElement ) {
						$name = str_replace($vocabulary . ":", '', $ref);
						if((string) $propertyElement->attributes()->name == $name){
							$type = (string) $propertyElement->attributes()->type;
							break;
						}
					}
					if(strpos($type, '単純型')){
						foreach ( $children->simpleType as $simpleType ) {
							if ( (string) $simpleType->attributes()->name === str_replace('ic:', '', $type) ) {
								$type = (string) $simpleType->restriction->attributes()->base;
								break;
							}
						}
					}
					$temp['element'][] = array(
						'name' => $ref,
						'type' => $type
					);
				}
			}
		} elseif ( ! empty( $value->simpleContent ) ) {
			foreach ( (object) $value->simpleContent->extension->attribute as $attribute ) {
				$type                      = (string) $attribute->attributes()->type;
				$temp['element'][] = $type;
			}
		}
	}
	return $temp;
}

function convertRdfPHP($myArray, $value){
	if (empty($myArray)){
		return $value;
	}
	$firstValue = array_shift( $myArray );
	$firstValue = convertURI($firstValue);
	if(count($myArray) > 0) {
		$a          = array(
			$firstValue        => '_:' . $firstValue,
			'_:' . $firstValue => convertRdfPHP( $myArray, $value )
		);
	}else{
		$a          = array(
			$firstValue        => convertRdfPHP( $myArray, $value )
		);
	}
	return $a;
}

class XMLConverter {

	public $count = 0;
	public $headerIndex = -1;
	public $items = array();
	public $property = array();
	public $definitionType = '';
	public $project = null;
	public $apiComponent = array();
	public $apiRequestCount = 0;
	public $app = null;

	public function __construct() {
		require_once __DIR__ . '/array2xml.php';
		$this->app            = \Slim\Slim::getInstance();
		$this->api_component  = $this->app->config( 'api_component' );
		$this->project        = $_SESSION['project'];
		$this->items          = $_SESSION['sheetData'];
		$this->definitionType = $this->project['eg:definitionType'];
		$this->property       = $this->project['eg:property'];
		$this->headerIndex = $this->getHeaderIndex();
	}

	public function internalAPI( $prop, $val ) {
		$key = $prop['eg:APIComponent'];
		if(isset($this->api_component[ $key ])){
			$api = $this->api_component[ $key ];
			if ( $api['type'] == 'internal' ) {
				$result = $api['call']( $val );
			} else {
				$url = $api['url'] . $api['format'] . $val;;
				$result = json_decode( file_get_contents( $url ), true );
			}
			if ( empty( $result ) ) {
				$result = array();
			}
			return $result;
		}else{
			return false;
		}
	}

	public function externalAPI( $prop, $val ) {
		$this->apiRequestCount++;
		if($this->apiRequestCount > $this->app->config( 'api_request_limit' )){
			$this->app->flash('error', '外部APIのリクエストは一度に'. $this->app->config( 'api_request_limit' ) . '回までです');
			$this->app->redirect($this->app->config('static_path'));
		}
		if (filter_var($prop['eg:APIComponentURL'], FILTER_VALIDATE_URL) === false) {
			$this->app->flash('error', '外部APIのリクエスト先がURLとして正しくない形式です。');
			$this->app->redirect($this->app->config('static_path'));
		}
		$request = $prop['eg:APIComponentURL'] . $val;
		$result = json_decode( file_get_contents( $request ), true );
		if(
			isset($result['result']) && $result['result'] == 'error'
			&& !empty($result['message'])
		){
			$this->app->flash('error', '「' . $val . '」の外部コンポーネント連携において、"' . $result['message'] . '" のエラーが返却されました。'. "\n");
		}
		if ( empty( $result ) ) {
			$result = array();
		}
		return $result;
	}

	public function setPropertyRecursive( $item, $property ) {
		$output = array();

		foreach ( $property as $key => $val ) {
			if ( isset( $val["eg:additional"] ) ) {
				$output[ $val["eg:predicate"] ]
					= $this->setPropertyRecursive( $item, $val["eg:additional"] );
			} elseif ( isset( $val['eg:targetType'] ) ) {
				switch ( $val['eg:targetType'] ) {
					case 'row':
						$index                          = $this->getIndex( $val['eg:targetCells'],
							'r' );
						$output[ $val["eg:predicate"] ] = $item[ $index ];
						break;
					case 'column':
						$index                          = $this->getIndex( $val['eg:targetCells'],
							'c' );
						$output[ $val["eg:predicate"] ] = $item[ $index ];
						break;
					case 'constant':
					case 'uri':
						$output[ $val["eg:predicate"] ] = $val['eg:targetTypeText'];
						break;
					case 'increment':
						$output[ $val["eg:predicate"] ] = $this->count;
						break;
					default:
						break;
				}

				if ( ! empty( $val['eg:APIComponent'] ) ) {
					if ( array_key_exists( $val['eg:APIComponent'], $this->api_component )) {
						$result = $this->internalAPI( $val, $output[ $val["eg:predicate"] ] );
						$output[ $val["eg:predicate"] ] = $result;
					}elseif($val['eg:APIComponent'] == 'external'){
						$result = $this->externalAPI( $val, $output[ $val["eg:predicate"] ] );
						$output[ $val["eg:predicate"] ] = $result;
					}
				}
			}
		}

		return $output;
	}

	public function getSubject( $item ) {
		switch ( $this->project['eg:subject']['eg:namingRule'] ) {
			case 'column':
			case 'row':
				if(isset($this->project['eg:subject']['eg:targetCells'])){
					$index = $this->getIndex( $this->project['eg:subject']['eg:targetCells'], 'c' );
				}else{
					$index = 0;
				}
				$subject = $item[ $index ];
				break;
			case 'constant':
				$subject = $this->project['eg:subject']['eg:constant'];
				break;
			case 'increment':
				$subject = $this->count;
				break;
			default:
				$subject = '';
				break;
		}

		return $subject;
	}

	public function getIndex( $val, $prefix ) {
		return (int) str_replace( $prefix, '', $val );
	}

	public function getHeaderIndex() {
		if(isset($this->project['eg:headerIndex'])){
			$headerIndex = $this->project['eg:headerIndex'];
		}else{
			$headerIndex = -1;
		}
		switch($this->project['eg:definitionType']) {
			case 'row':
				$headerIndex = (int)str_replace('r', '', $headerIndex);
				break;
			case 'column':
				$headerIndex = (int)str_replace('c', '', $headerIndex);
				break;
		}
		return $headerIndex;
	}

	public function checkHeaderSkip(){
		$this->count ++;
		return ( ($this->headerIndex + 1) >= $this->count ) ;
	}

	public function getXML() {
		$array2xml = new Array2xml();
		$array2xml->setRootName('items');
		$array2xml->setNumericElement('item');
		$array2xml->setSkipNumeric(false);
		$namespaces = array(
			'xmlns' => "http://example.com/ns/#",
			'xmlns:ic' => "http://imi.ipa.go.jp/ns/core/2",
			'xmlns:dct' => "http://purl.org/dc/terms/",
			'xmlns:xsi' => "http://www.w3.org/2001/XMLSchema-instance",
			'xsi:noNamespaceSchemaLocation' => "./schema.xsd",
		);
		$id = array();
		$output = array();
		foreach ( $this->items as $item ) {
			if ( $this->checkHeaderSkip() ) {
				continue;
			}
			$not_empty = false;
			foreach($item as $cell){
				if(!empty($cell)){
					$not_empty = true;
					break;
				}
			}
			if(!$not_empty){
				continue;
			}
			$id[] = array('id' => $this->project['eg:vocabulary']['eg:uri'] . $this->getSubject($item));
			$output[] = $this->setPropertyRecursive( $item, $this->property );
		}
		$array2xml->setElementsAttrs($id);
		$array2xml->setRootAttrs($namespaces);
		return $array2xml->convert($output);
	}
}

class RDFConverter extends XMLConverter{

	protected $graph;
	public $items = array();
	public $property = array();
	public $definitionType = '';
	public $format = '';

	public $project = null;

	public function __construct(){
		parent::__construct();
		$this->property = array();
		$this->project = $_SESSION['project'];
		$this->items = $_SESSION['sheetData'];
		$this->definitionType = $this->project['eg:definitionType'];
		$property = $this->project['eg:property'];
		if(is_array($property) && $property !== array()){
			$namespaces = array();
			foreach($property as $key => $value){
				if(!empty($value['eg:additional'])){
					foreach($value['eg:additional'] as $a){
						if(!empty($a["eg:targetType"])){
							$tree = explode('-', $a['eg:tree']);
							$nest_property = convertRdfPHP($tree, $a);
							if($this->property == array()){
								$this->property = $nest_property;
							}else{
								$this->property = arrayMergeRecursiveDistinct($nest_property, $this->property);
							}
						}
					}
				}elseif(!empty($value["eg:targetType"])){
					$this->property[convertURI($value['eg:predicate'])] = $value;
					$namespaces[] = explode(':',$value['eg:predicate'])[0];
				}
			}
			$prefix = $this->app->config('prefix');
			foreach($namespaces as $n){
				if(array_key_exists($n, $prefix)){
					EasyRdf_Namespace::set($n, $prefix[$n]['namespace']);
				}
			}
			EasyRdf_Namespace::set('ic', 'http://imi.ipa.go.jp/ns/core/2/');
			$this->graph = new EasyRdf_Graph();
		}
	}

	public function setPropertyRecursive($item, $property, $isBlank = false){
		$baseuri = $this->project['eg:vocabulary']['eg:uri'];
		$subject = $baseuri . $this->getSubject($item);
		$output = array();
		if(is_array($property) && $property !== array()){
			foreach($property as $key => $val){
				if(strpos($key, '_:') === 0){
					$output[$key] = $this->setPropertyRecursive($item, $val, true);
				}elseif(is_string($val) && strpos($val, '_:') === 0) {
					$output[$subject][$key][] = array(
						'type' => 'bnode',
						'value' => $val
					);
				}elseif ( isset( $val['eg:targetType'] ) ) {
					if($isBlank){
						$o = &$output[$key];
					}else{
						$o = &$output[$subject][$key];
					}
					switch($val['eg:targetType']){
						case 'row':
							$index = $this->getIndex($val['eg:targetCells'], 'r');
							$o[] = array(
								'type' => 'literal',
								'lang' => 'ja',
								'value' => $item[$index]
							);
							break;
						case 'column':
							$index = $this->getIndex($val['eg:targetCells'], 'c');
							$o[] = array(
								'type' => 'literal',
								'lang' => 'ja',
								'value' => $item[$index]
							);
							break;
						case 'constant':
							$o[] = array(
								'type' => 'literal',
								'lang' => 'ja',
								'value' => $val['eg:targetTypeText']
							);
							break;
						case 'uri':
							$o[] = array(
								'type' => 'uri',
								'value' => $val['eg:targetTypeText']
							);
							break;
						case 'increment':
							$o[] = array(
								'type' => 'literal',
								'lang' => 'ja',
								'value' => $this->count
							);
							break;
						default:
							break;
					}
				}
			}
		}

		return $output;
	}

	public function parseGraph($format = "ttl"){
		$this->format = $format;
		if(is_array($this->items) && $this->items !== array()){
			foreach($this->items as $item):
				if ( $this->checkHeaderSkip() ) {
					continue;
				}
				$node = $this->setPropertyRecursive($item, $this->property);
				$this->graph->parse($node, 'guess');
			endforeach;
		}
	}

	public function output(){
		$data = $this->graph->serialise($this->format);
		return $data;
	}

}

function judgeOriginalRecursive($property, $namespaces, $userOriginal){
	foreach($property as $p){
		if(isset($p['eg:additional']) && is_array($p['eg:additional'])){
			judgeOriginalRecursive($p['eg:additional'], $namespaces, $userOriginal);
		}elseif(isset($p['eg:predicate'])){
			$prefix = explode(':', $p['eg:predicate']);
			if(
				isset($prefix[0]) && !array_key_exists($prefix[0], $namespaces)
				|| !isset($prefix[0])
			){
				array_push($userOriginal, $p);
				return $userOriginal;
			}
		}
	}
}

function getLincenseURI($val, $license){
	if(isset($license[$val])){
		$license = $license[$val];
	}else{
		$license = '';
	}
	return $license;
}

function iterateProperty($val, $property){
	if(is_array($val)):
		foreach($val as $v):
			?>
			<<?php echo $property;?>><?php echo  htmlspecialchars($v); ?></<?php echo $property;?>>
				<?
		endforeach;
	endif;
}

function getSchema(){
	$app = \Slim\Slim::getInstance();
	$namespaces = $app->config('prefix');
	$project = $_SESSION['project'];
	$property = $project['eg:property'];
	$created = getYmd($project['dct:created']);
	if(!empty($project['dct:license'])){
		$license = getLincenseURI($project['dct:license'], $app->config('license'));
	}else{
		$license = '';
	}
	$userOriginal = array();
	$userOriginal = judgeOriginalRecursive($property, $namespaces, $userOriginal);
	ob_start();
	echo '<?xml version="1.0" encoding="utf-8"?>';
	?>
<xsd:schema xmlns:xsd="http://www.w3.org/2001/XMLSchema"
            targetNamespace="http://example.org/imins/<?php echo $project['_id']; ?>"
            xmlns:dct="http://purl.org/dc/terms/"
            xmlns:ex="http://example.org/imins/<?php echo $project['_id']; ?>"
            xmlns:ic="http://imi.ipa.go.jp/ns/core/2">

	<xsd:annotation>
		<xsd:documentation xml:lang="ja"><?php echo  htmlspecialchars($project['rdfs:label']); ?></xsd:documentation>
		<xsd:appinfo>
			<dct:title><?php echo htmlspecialchars($project['rdfs:label']); ?></dct:title>
			<dct:creator><?php echo htmlspecialchars($project['dct:creator']); ?></dct:creator>
			<dct:license><?php echo $license; ?></dct:license>
			<dct:created><?php echo $created; ?></dct:created>
		</xsd:appinfo>
	</xsd:annotation>

	<xsd:complexType name="itemsType">
		<xsd:sequence>
			<xsd:element ref="ex:item" minOccurs="0" maxOccurs="unbounded" />
		</xsd:sequence>
	</xsd:complexType>

	<xsd:element name="item" type="ex:itemType" />

	<xsd:complexType name="itemType">
		<xsd:complexContent>
			<xsd:extension base="<?php echo $project['eg:class']; ?>">
				<xsd:sequence>
					<?php
					if ($userOriginal):
						foreach($userOriginal as $idx => $value):
							?>
							<xsd:element name="<?php echo htmlspecialchars($value['eg:predicate']); ?>" type="<?php echo htmlspecialchars($value['eg:type']); ?>" minOccurs="0" maxOccurs="unbounded" />
							<?php
						endforeach;
					endif;
					?>
				</xsd:sequence>
			</xsd:extension>
		</xsd:complexContent>
	</xsd:complexType>
</xsd:schema>
	<?php
	$xsd = ob_get_contents();
	ob_end_clean();
	return $xsd;
}

function dmdHeaderXML(){
	$app = Slim\Slim::getInstance();
	ob_start();
	$project = $_SESSION['project'];
	$created = getYmd($project['dct:created']);
	if(!empty($project['dct:license'])){
		$license_uri = getLincenseURI($project['dct:license'], $app->config('license'));
		$license_name = $project['dct:license'];
	}else{
		$license_uri = '';
		$license_name = '';
	}
	echo '<?xml version="1.0" encoding="utf-8"?>';
	?>
<DMD xmlns="http://imi.ipa.go.jp/dmd/ns" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://imi.ipa.go.jp/dmd/ns">
	<URI>http://example.org/imins/<?php echo $project['_id']; ?></URI>
	<Name xml:lang="ja"><?php echo htmlspecialchars($project['rdfs:label']); ?></Name>
	<CreationDate><?php echo $created; ?></CreationDate>
	<Description xml:lang="ja"><?php if(!empty($project['dct:description'])){ echo htmlspecialchars($project['dct:description']); }?></Description>
	<Publisher>
		<Name xml:lang="ja"><?php echo htmlspecialchars($project['dct:creator']); ?></Name>
	</Publisher>
	<License>
		<URI><?php echo $license_uri; ?></URI>
		<Name><?php echo $license_name; ?></Name>
	</License>
	<DefaultCharset>UTF-8</DefaultCharset>
</DMD>
	<?php
	$xml = ob_get_contents();
	ob_end_clean();
	return $xml;
}

function dmdHeaderRDF(){
	$app = Slim\Slim::getInstance();
	ob_start();
	$project = $_SESSION['project'];
	$format = $_SESSION['format'];
	$mimeType = array(
		'xml' => 'text/xml',
		'rdfxml' => 'application/rdf+xml',
		'jsonld' => 'application/ld+json',
		'turtle' => 'text/turtle',
		'ntriples' => 'application/n-triples'
	);
	if(!array_key_exists($format, $mimeType)){
		$app->flash('error', '指定された出力形式が不正です。');
		throw new RuntimeException('指定された出力形式が不正です。');
	}

	$created = getYmd($project['dct:created']);
	if(!empty($project['dct:license'])){
		$license_uri = getLincenseURI($project['dct:license'], $app->config('license'));
	}else{
		$license_uri = '';
	}
	$dataName = 'convertedData.' . $format;
	$dataType = $mimeType[$format];
	?>
@prefix owl: <http://www.w3.org/2002/07/owl#>.
@prefix rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>.
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#>.
@prefix xsd: <http://www.w3.org/2001/XMLSchema#>.
@prefix dcterms: <http://purl.org/dc/terms/>.
@prefix adms: <http://www.w3.org/ns/adms#>.
@prefix dcat: <http://www.w3.org/ns/dcat#>.
@prefix dmd: <http://imi.ipa.go.jp/ns/dmd#>.

<http://example.org/imins/<?php echo $project['_id']; ?>> a adms:Asset ;
dcterms:type dmd:DataModelDescription ;
dcterms:issued "<?php echo $created; ?>"^^xsd:date ;
dcterms:description "<?php if(!empty($project['dct:description'])){ echo htmlspecialchars($project['dct:description']); }?>"@ja ;
dcterms:publisher "<?php echo htmlspecialchars($project['dct:creator']); ?>"@ja ;
dcterms:title "<?php echo htmlspecialchars($project['rdfs:label']); ?>"@ja ;

dcterms:license <<?php echo $license_uri; ?>> ;
dcat:distribution <header.ttl> ;
dcat:distribution <header.xml> ;
dcat:distribution <schema.xsd> ;
dcat:distribution <mapping.json> ;
dcat:distribution <<?php echo $dataName; ?>> .

<header.ttl> a adms:AssetDistribution ;
	dcat:mediaType "text/turtle" .

<header.xml> a adms:AssetDistribution ;
	dcat:mediaType "text/xml" .

<schema.xsd> a adms:AssetDistribution ;
	dcat:mediaType "text/xml" .

<mapping.json> a adms:AssetDistribution ;
	dcat:mediaType "application.json" .

<<?php echo $dataName; ?>> a adms:AssetDistribution ;
	dcat:mediaType "<?php echo $dataType; ?>" .

	<?php
	$rdf = ob_get_contents();
	ob_end_clean();
	return $rdf;
}

function getMapping(){
	$mapping = $_SESSION['project']['eg:property'];

	$removeLevel = function($array) use(&$removeLevel) {
		$result = array();
		foreach ($array as $key => $value) {
			if (is_array($value)) {
				if(isset($value['level'])){
					unset($value['level']);
				}
				$result[$key] = $removeLevel($value);
			}else{
				$result[$key] = $value;
			}
		}
		return $result;
	};

	$result = $removeLevel($mapping);
	return json_encode($result);
}

function uploadTablefile() {
	$app = \Slim\Slim::getInstance();
	if (
		!isset($_FILES['tableFile']['error']) ||
		!is_int($_FILES['tableFile']['error'])
	) {
		$app->flash('error', 'パラメータが不正です');
		throw new RuntimeException('パラメータが不正です');
	}

	switch ($_FILES['tableFile']['error']) {
		case UPLOAD_ERR_OK:
			break;
		case UPLOAD_ERR_NO_FILE:
			$app->flash('error', 'ファイルが選択されていません');
			throw new RuntimeException('ファイルが選択されていません');
		case UPLOAD_ERR_INI_SIZE:
		case UPLOAD_ERR_FORM_SIZE:
			$app->flash('error', 'ファイルサイズが大きすぎます');
			throw new RuntimeException('ファイルサイズが大きすぎます');
		default:
			$app->flash('error', 'その他のエラーが発生しました');
			throw new RuntimeException('その他のエラーが発生しました');
	}

	$valid_extension = array(
		'xls' => array('application/vnd.ms-excel', 'application/vnd.ms-office'),
		'xlsx' => array('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
		'csv' => array('text/plain')
	);

	$extension = explode('.', $_FILES['tableFile']['name']);
	$extension = array_pop($extension);
	$extension = strtolower($extension);
	if(!array_key_exists($extension, $valid_extension)){
		$app->flash('error', 'ファイル拡張子を確認して下さい');
		throw new RuntimeException('ファイル拡張子を確認して下さい');
	}

	$finfo = new finfo(FILEINFO_MIME_TYPE);
	$mime_found = false;
	$file_mime = $finfo->file($_FILES['tableFile']['tmp_name']);
	foreach($valid_extension as $k => $v){
		$mime_found = array_search(
			$file_mime,
			$v,
			true
		);
		if($mime_found !== false){
			break;
		}
	}
	if ($mime_found === false) {
		$app->flash('error', 'ファイル形式が不正です');
		throw new RuntimeException('ファイル形式が不正です');
	}

	$filePath = $_FILES['tableFile']["tmp_name"];

	try{
		PHPExcel_Settings::setCacheStorageMethod(PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp, array('memoryCacheSize' => '200MB'));

		if($extension === 'csv'){
			$encoding = mb_detect_encoding(file_get_contents($filePath, null, null, 0 , 100), array('ASCII', 'JIS', 'UTF-8', 'CP51932', 'SJIS-win'));
			$objReader = PHPExcel_IOFactory::createReader('CSV');
			$objReader->setInputEncoding($encoding);

			$phpExcelObj = $objReader->load( $filePath );
		}else if($extension === 'xls' || $extension === 'xlsx'){
			$phpExcelObj = PHPExcel_IOFactory::load( $filePath );
		}

		unset( $_SESSION['sheetData'] );
		$sheet = $phpExcelObj->setActiveSheetIndex( 0 );
		foreach ( $sheet->getRowIterator() as $row ) {
			$tmp          = array();
			$cellIterator = $row->getCellIterator();
			$cellIterator->setIterateOnlyExistingCells( false );
			foreach ( $cellIterator as $cell ) {
				$tmp[] = $cell->getFormattedValue();
			}
			$_SESSION['sheetData'][] = $tmp;
		}
	}catch(ErrorException $e){
		$app->getLog()->error($e->getMessage());
		$app->flash('error', 'ファイルを正しく読み込めませんでした。');
		throw new RuntimeException('ファイルを正しく読み込めませんでした。');
	}

}
