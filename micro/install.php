<?php

// direct installation
define('DIRECT_INSTALLATION', true);

// loading installer
fwrite(STDOUT, 'Starting download process..... Checking system requirements....' . PHP_EOL);

// load repo to install
define('REPO_TO_INSTALL', [
	'moorexa/system' 	=> 'moorexaCore',
	'moorexa/micro' 	=> 'moorexaMicroService',
	'moorexa/src' 		=> 'moorexaSource',
	'moorexa/package' 	=> 'moorexaPackager'
]);

try
{
	// try download the installer file
	file_put_contents(__DIR__ . '/installer.php', file_get_contents('http://moorexa.com/raw-installer'));	

	// try download the moorexa file
	file_put_contents(__DIR__ . '/moorexa', file_get_contents('http://moorexa.com/raw-moorexa'));

	// try download the completeInstallProcess file
	file_put_contents(__DIR__ . '/completeInstallProcess.php', file_get_contents('http://moorexa.com/raw-install-function'));
}
catch(Throwable $e){}

// continue if installer, moorexa files exists
if (!file_exists('installer.php') || !file_exists('moorexa') || !file_exists('completeInstallProcess.php')) return fwrite(STDOUT, PHP_EOL . 'Operation ended. Missing required files. ' . PHP_EOL);

// create tmp directory
if (!is_dir(__DIR__ . '/tmp/')) mkdir(__DIR__ . '/tmp/');

// load installer
include_once 'installer.php';

// start extraction
fwrite(STDOUT, PHP_EOL . 'Starting extraction process....' . PHP_EOL);

// run moorexa
pclose(popen('php moorexa create -micro --direct-installation', "w"));

// delete moorexa
unlink(__DIR__ . '/moorexa');

// include the complete process file
include_once 'completeInstallProcess.php';

// get handler
$handler = completeInstallProcess();

// make request
pclose(popen('php src/install.php --standardInput', 'w'));

// sleep for a while
sleep(3);

// get install response body file
$responseFile = __DIR__ . '/src/installResponse.txt';

// get output
if (file_exists($responseFile)) :
	
	// load handler
	$handler(json_decode(file_get_contents($responseFile)), 'init.php');

	// delete response body
	unlink($responseFile);

endif;

// generate all secret keys
fwrite(STDOUT, PHP_EOL . 'Running php assist init for keys and security.' . PHP_EOL);

// run query
pclose(popen('php assist init', 'w'));

// generate open ssl certificate
fwrite(STDOUT, PHP_EOL . 'To generate a certificate for your app please run "php assist generate certificate".' . PHP_EOL);

// all done
fwrite(STDOUT, PHP_EOL . 'Installation Complete. What next? visit www.moorexa.com or run "php assist serve" to start the development server' . PHP_EOL);

// delete all files in the tmp
$tmp = defined('GLOB_BRACE') ? glob(__DIR__ .'/tmp/{,.}*', GLOB_BRACE) : glob(__DIR__ .'/tmp/{,.}*');

// load
if (count($tmp) > 0) :

	// circle through
	foreach ($tmp as $content) :

		// check
		if (basename($content) != '.' && basename($content) != '..') :

			// is file?
			if (is_file($content)) unlink($content);
			if (is_dir($content)) rmdir($content);

		endif;

	endforeach;

	// delete tmp folder
	rmdir(__DIR__ . '/tmp/');

endif;

// delete this file
unlink('install.php');