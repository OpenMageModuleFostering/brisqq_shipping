<?php

/**
 * @author     Brisqq Ltd.
 * @package    Brisqq
 * @copyright  GNU General Public License (GPL)
 *
 * Brisqq harnesses the power of the crowd to enable seamless local delivery on demand.
 * http://www.brisqq.com
 */

require_once (Mage::getBaseDir() . '/var/brisqq-assets/ChromePhp.php');
require_once(Mage::getBaseDir() . '/var/brisqq-assets/custom-php-brisqq.php');

/**
 * Production - Staging Control
 * Change $production variable to true if you want to run this plugin on the production server
 * When the $production value is false, the plugin is targeting staging server.
 * Give this variable true value if you want to use this plugin for your production website.
 *
 * @param void
 *
 * @return bool
 */
function production() {

	$production = true;

	return $production;
}

/**
 * Custom code location on the Brisqq servers
 * Name of the folder on the Brisqq server of the current integration.
 *
 * @param void
 *
 * @return string
 */
function integration_name() {
	$integration_name = 'default_production';

	return $integration_name;
}

/**
 * Chrome PHP log on/off - Autoloader logs
 *
 * @param void
 *
 * @return bool
 */
function chromePhpLogsAutoloader() {
	$chromePhpLogsAutoloader = false;

	return $chromePhpLogsAutoloader;
}

/**
 * Chrome PHP log on/off - Carrier logs
 *
 * @param void
 *
 * @return bool
 */
function chromePhpLogsCarrier() {
	$chromePhpLogsCarrier = false;

	return $chromePhpLogsCarrier;
}

/**
 * Create folder in case it doesnt exist already
 */
if (!is_dir(Mage::getBaseDir() . '/var/brisqq-assets')) {

	mkdir(Mage::getBaseDir() . '/var/brisqq-assets');
}

/**
 * Helper function for getting the name of the current file
 *
 * @param string $fileN
 *
 * @return string
 */
function fileName ($fileN) {
	$link = explode(("/"), $fileN);
	return end($link);
}

/**
 * PHP settings checker function
 * Checking are curl, file_get_contents or file_put_contents enabled/disabled
 *
 * @param string $function_name
 *
 * @return bool
 */
function functionChecker($function_name) {

	$checker = function_exists($function_name);
	if (!$checker) {
		Mage::log($function_name . ' is disabled.');
	}

	if (chromePhpLogsAutoloader()) {
		ChromePhp::log(fileName(__FILE__) . '/' . __LINE__ . '- function checker fired');
		ChromePhp::log(fileName(__FILE__) . '/' . __LINE__ . '- ' . $function_name . ' - function availability is in the next line: ');
		ChromePhp::log($checker);
	}

	return $checker;

}

/**
 * Output remote php file in a variable as a string -- CURL METHOD
 *
 * @param string $url
 *
 * @return string
 */
function curlMethod($url) {

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 1);
	$data = curl_exec($ch);
	$info = curl_getinfo($ch);
	curl_close($ch);

	if (chromePhpLogsAutoloader()) {
		ChromePhp::log(fileName(__FILE__) . '/' . __LINE__ . '- curlMethod function fired!');
		ChromePhp::log(fileName(__FILE__) . '/' . __LINE__ . '- URL: ' . $url);
		ChromePhp::log(fileName(__FILE__) . '/' . __LINE__ . '- curlMethod timeout time ' . $info['total_time']);
		ChromePhp::log(fileName(__FILE__) . '/' . __LINE__ . ' Server response in next line: ');
		if (!$data) {
		 	ChromePhp::log($data);
		 	ChromePhp::log('CURL is enabled, but failed. Trying via File Get Contents.');
		} else {
		 	ChromePhp::log($info['http_code']);
		}
	}

	if ($info['http_code'] == 200) {
		return $data;
	} else {
		return false;
	}

}

/**
 * Output remote php file in a variable as a string -- FILE GET CONTENTS METHOD
 *
 * @param string $url
 *
 * @return string
 */
function fileGetContentsMethod($url) {

	$ctx = stream_context_create(array('http'=>
	    array(
	        'timeout' => 1,
	    )
	));

	$result = file_get_contents($url, false, $ctx);

	$http_code_init = $http_response_header;

	$http_code_array = explode(' ', $http_code_init[0]);

	foreach ($http_code_array as $key => $value) {
		$testing = filter_var($value, FILTER_VALIDATE_INT, array('options' => array('min_range' => 1)));
		if ($testing) {
			$http_code = $testing;
		}
	}

	if (chromePhpLogsAutoloader()) {
		ChromePhp::log(fileName(__FILE__) . '/' . __LINE__ . ' - Server response in the next line: ');
		ChromePhp::log($http_code_init[0]);
		ChromePhp::log($http_code);
		ChromePhp::log(fileName(__FILE__) . '/' . __LINE__ . ' - fileGetContentsMethod function fired!');
		ChromePhp::log(fileName(__FILE__) . '/' . __LINE__ . ' - URL: ' . $url);
		ChromePhp::log(fileName(__FILE__) . '/' . __LINE__ . ' - Results: ' . isset($result));
	}

	if ($http_code == 200) {
		return $result;
	} else {
		return false;
	}
}

/**
 * Load remote files and save them in the /var/brisqq-assets folder
 *
 * @param string $adress
 *
 * @return void
 */
function remoteFileCallFilter($adress) {

	if ($adress == 'carrier.php') {
		$url = 'http://s3-eu-west-1.amazonaws.com/brisqq-assets/eapi/core_v2/magento_php_core/carrier.php';
	} elseif ($adress == 'observer.php') {
		$url = 'http://s3-eu-west-1.amazonaws.com/brisqq-assets/eapi/core_v2/magento_php_core/observer.php';
	}  elseif ($adress == 'brisqq-price-tiers.js') {
		$url = 'http://s3-eu-west-1.amazonaws.com/brisqq-assets/eapi/core_v2/javascript_core/brisqq-price-tiers.js';
	}  elseif ($adress == 'custom-php-brisqq.php') {
		$url = 'http://s3-eu-west-1.amazonaws.com/brisqq-assets/eapi/magento/' . integration_name() . '/custom-php-brisqq.php';
	}


	if (chromePhpLogsAutoloader()) {
		ChromePhp::log(fileName(__FILE__) . '/' . __LINE__ . ' - remoteFileCallFilter function fired!');
	}
	## if cURL is enabled, call our server with cURL
	if (functionChecker('curl_version')) {
		if (chromePhpLogsAutoloader()) {
			ChromePhp::log(fileName(__FILE__) . '/' . __LINE__ . ' - curl is enabled, getting ready for http request');
		}
		$code = curlMethod($url);
		if (!$code) {
			$code =	fileGetContentsMethod($url);
		}
		## update the file only if it was succesfullt loaded from server, otherwise use the one previously loaded
		if ($code && functionChecker('file_put_contents')) {
			if ($adress == 'brisqq-price-tiers.js') {
				$test = file_put_contents(Mage::getBaseDir() . '/js/' . $adress, $code);
			} else {
				$test = file_put_contents(Mage::getBaseDir() . '/var/brisqq-assets/' . $adress, $code);
			}
			if (chromePhpLogsAutoloader()) {
				ChromePhp::log(fileName(__FILE__) . '/' . __LINE__ . ' - http request (' . $adress . ') made succesfuly and file_PUT_contents is enabled. Next line is the return value of file_PUT_contents.');
				ChromePhp::log(fileName(__FILE__) . '/' . __LINE__ . ' - ' . $test);
			}
		}

	}
	## if cURL is not enabled, load files using FILE GET CONTENTS
	elseif (functionChecker('file_get_contents')) {

		if (chromePhpLogsAutoloader()) {
			ChromePhp::log(fileName(__FILE__) . '/' . __LINE__ . ' - CURL IS DISABLED, getting ready for http request via file_get_contents');
		}

		$code = fileGetContentsMethod($url);
		## update the file only if it was succesfullt loaded from server, otherwise use the one previously loaded
		if ($code && functionChecker('file_put_contents')) {

			$test = file_put_contents(Mage::getBaseDir() . '/var/brisqq-assets/' . $adress, $code);
			if (chromePhpLogsAutoloader()) {
				ChromePhp::log(fileName(__FILE__) . '/' . __LINE__ . ' - http request (' . $adress . ') made succesfuly and file_PUT_contents is enabled. Next line is the return value of file_PUT_contents.');
				ChromePhp::log(fileName(__FILE__) . '/' . __LINE__ . ' - ' . $test);
			}
		}

		}
	## if cURL and file_get_contents are not enabled, write logs
	else {
		Mage::log('CURL AND FILE_GET_CONTENTS ARE DISABLED! We need at least one to operate.');
	}

}


?>
