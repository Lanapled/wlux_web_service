<?php 
require 'config_files.php';
require 'int_debug.php';
require 'account_get.php';
require 'account_post.php';
require 'account_put.php';
require 'account_delete.php';

$response = '';

$link = mysqli_connect($DB_SERVER, $DB_USER, $DB_PASS, $DB_DATABASE_NAME);
if (!$link) {
	// can't open DB so return an error
	// this line only works on PHP > 5.4.0, which not everyone seems to have.
	//   http_response_code(500);
	// this works on PHP > 4.3 (or so)
	$errData['status'] = 500;
	$errData['module'] = __FILE__;
	$errData['message'] = 'Can\'t connect to database server: '.$DB_SERVER.' as: '.$DB_USER;
	$response['error'] = $errData;
} else {
	if ($_SERVER['REQUEST_METHOD'] == 'GET') {
		// if the data is not in the the post form, try the query string		
		if (empty($postData)) {
			$postData = $_GET;
		} 		
		$response = _account_get($link, $postData);
	} else if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		// get the request data
		if (!empty($HTTP_RAW_POST_DATA)) {
			$postData = json_decode($HTTP_RAW_POST_DATA,true);
		}		
		// if the data is not in the raw post data, try the post form
		if (empty($postData)) {
			$postData = $_POST;
		}
		if (empty($postData)) {
			$postData = $_GET;
		} 
		$response = _account_post($link, $postData);
	} else if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
		$postData = json_decode(file_get_contents('php://input'), true);
		// if the data is not in the raw post data, try the post form
		if (empty($postData)) {
			$postData = $_GET;
		}
		$response = _account_put($link, $postData);
	} else if ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
		// get the request data
		$postData = json_decode(file_get_contents('php://input'), true);
		// if the data is not in the raw post data, try the post form
		if (empty($postData)) {
			$postData = $_POST;
		}
		if (empty($postData)) {
			$postData = $_GET;
		} 
		$response = _account_delete($link, $postData);
	} else {
		// method not supported
		$errData['status'] = 405;
		$errData['module'] = __FILE__;
		$errData['message'] = 'HTTP method not allowed.';
		$response['error'] = $errData;
	}
	mysqli_close($link);
}

if (!headers_sent()) {
	header('content-type: application/json');
	header('X-PHP-Response-Code: 200', true, 200);
}
$thisParam = "callback";
if (array_key_exists($thisParam, $_GET)) {
	$jsonpTag = $_GET[$thisParam]; // set by jquery ajax call when using jsonp data type
}

if (!empty($jsonpTag)) { 
	// format and send output
	// no error information is returned in the JSONP response!
	$fnResponse = $jsonpTag . '(' . json_encode($response['data']) . ')';
} else {
	// no callback param name so return an error
	// this line only works on PHP > 5.4.0, which not everyone seems to have.
	//   http_response_code(500);
	// this works on PHP > 4.3 (or so)
	$fnResponse = json_encode($response);
} 
print ($fnResponse);
?>