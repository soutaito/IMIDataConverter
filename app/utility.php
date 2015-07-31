<?php

function getYmd($MongoDate){
	if(isset($MongoDate['sec'])){
		return date('Y/m/d', $MongoDate['sec']);
	}else{
		return false;
	}
}

function getSalt($id, $fixedsalt){
	return $id . pack('H*', $fixedsalt);
}

function getPasswordHash($id, $password, $fixedsalt){
	$salt = getSalt($id, $fixedsalt);
	$hash = '';
	//ストレッチング
	for ($i = 0; $i < 1000; $i++){
		$hash = hash('sha256', $hash . $password . $salt);
	}
	return $hash;
}

function publishToken() {
	return session_id();
}

function &arrayMergeRecursiveDistinct(array &$array1, &$array2 = null){
	$merged = $array1;
	if (is_array($array2))
		foreach ($array2 as $key => $val){
			if (is_array($array2[$key])){
				$merged[$key] = isset($merged[$key]) && is_array($merged[$key]) ? arrayMergeRecursiveDistinct($merged[$key], $array2[$key]) : $array2[$key];
			}else{
				$merged[$key] = $val;
			}
		}
	return $merged;
}

function pagination( $total , $path , $perpage) {

	$app = \Slim\Slim::getInstance();
	$static_path = $app->config('static_path');

	$links = 5;
	$last = ceil( $total / $perpage );
	if($total == 0 || $last == 1){
		return false;
	}

	$get = $_GET;

	if(!empty($get['page'])){
		$current = $get['page'];
	}else{
		$current = 1;
	}

	$params = '';
	if(!empty($get)){
		unset($get['page']);
		if(!empty($get)){
			$params = '&' . http_build_query($get);
		}
	}

	$start = ( ( $current - $links ) > 0 ) ? $current - $links : 1;
	$end   = ( ( $current + $links ) < $last ) ? $current + $links : $last;

	$html = '<div class="pagination_wrap"><ul class="pagination">';

	$class = ( $current == 1 ) ? "disabled" : "";
	$html .= '<li class="' . $class . '"><a href="' . $static_path . '?page=' . ( $current - 1 ) . $params . '">prev</a></li>';

	if ( $start > 1 ) {
		$html .= '<li><a href="' . $static_path . '?page=1' . $params . '">1</a></li>';
		$html .= '<li class="disabled"><span>...</span></li>';
	}

	for ( $i = $start; $i <= $end; $i ++ ) {
		$class = ( $current == $i ) ? "active" : "";
		$html .= '<li class="' . $class . '"><a href="' . $static_path . '?page=' . $i . $params . '">' . $i . '</a></li>';
	}

	if ( $end < $last ) {
		$html .= '<li class="disabled"><span>...</span></li>';
		$html .= '<li><a href="' . $static_path . '?page=' . $last .  $params . '">' . $last . '</a></li>';
	}

	$class = ( $current == $last ) ? "disabled" : "";
	$html .= '<li class="' . $class . '"><a href="' . $static_path . '?page=' . ( $current + 1 ) .  $params . '">next</a></li>';

	$html .= '</ul></div>';

	echo $html;
}

function convertURI($value){
	$app = \Slim\Slim::getInstance();
	$prefix = $app->config('prefix');
	$value = explode(':', $value);
	foreach($prefix as $key => $val){
		if($key == $value[0]){
			$value[0] = $val['url'];
			return implode('/', $value);
		}
	}
}

function shorten($text, $width = null) {
	if (mb_strlen($text) < $width) {
		return $text;
	} else {
		return mb_substr($text, 0, $width) . '...';
	}
}

function getCCicons($ccstring){
	$ret = "";
	$ccicons = array(
		'CC BY 4.0' => '<i class="cc cc-cc"></i><i class="cc cc-by"></i>',
		'CC BY-SA 4.0' => '<i class="cc cc-cc"></i><i class="cc cc-by"></i><i class="cc cc-sa"></i>',
		'CC0' => '<i class="cc cc-pd"></i>'
	);
	if (array_key_exists($ccstring,$ccicons)){
		$ret = $ccicons[$ccstring];
	}
	return $ret;
}

