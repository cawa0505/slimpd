<?php
/* Copyright (C) 2015-2016 othmar52 <othmar52@users.noreply.github.com>
 *
 * This file is part of sliMpd - a php based mpd web client
 *
 * This program is free software: you can redistribute it and/or modify it
 * under the terms of the GNU Affero General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.  See the GNU Affero General Public License
 * for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

// DIC configuration

$container = $app->getContainer();

// -----------------------------------------------------------------------------
// Service providers
// -----------------------------------------------------------------------------

// Flash messages
$container['flash'] = function () {
    return new Slim\Flash\Messages;
};

// Config loader
$container['conf'] = function () {
	// set cache parameter
	$noCache = false;
	if(isset($_REQUEST['noCache']) === true) {
		$noCache = true;
	}
	// TODO: force clearCache when we access route:systemcheck
	#if(\Slim\Environment::getInstance()->offsetGet("PATH_INFO") === "/systemcheck") {
	#	$noCache = true;
	#}
	
	$configLoader = new \Slimpd\Modules\configloader_ini\ConfigLoaderINI(APP_ROOT . 'core/config/');
	$config = $configLoader->loadConfig('master.ini', NULL, $noCache);
	\Slimpd\Modules\localization\Localization::setLocaleByLangKey($config['config']['langkey']);
	return $config;
};

// Twig
$container['view'] = function ($cont) {
    $conf = $cont->get('conf');
	
    #$view = new Slim\Views\Twig($settings['view']['template_path'], $settings['view']['twig']);
	$view = new Slim\Views\Twig('core/templates', [
 #'cache' => 'localdata/cache',
 'debug' => true,
 #'auto_reload' => true,
 ]);

	#$view->parserExtensions = array(
    #new \Slim\Views\TwigExtension(),
    
    // Add extensions
    $view->addExtension(new Twig_Extension_Debug());
    $view->addExtension(new \Slim\Views\TwigExtension(
    	$cont->get('router'),
    	$cont->get('request')->getUri())
	);
	$view->addExtension(new \Slimpd\libs\twig\SlimpdTwigExtension\SlimpdTwigExtension($cont));
	
	// TODO: is this the right place for adding global template variables?
	$globalTwigVars = [
		'playerMode' => (($cont->cookie->get('playerMode') === 'mpd') ? 'mpd' : 'local'),
		'nosurrounding' => isset($_REQUEST['nosurrounding']),
		'root' => $conf['config']['absRefPrefix'],
		'fileroot' => $conf['config']['absFilePrefix'],
		'config' => $conf,
		'flash' => $cont['flash']
	];
	foreach($globalTwigVars as $varName => $value) {
		$view->getEnvironment()->addGlobal($varName, $value);
	}
    

    return $view;
};

$container['db'] = function ($cont) { 
	$settings = $cont->get('conf');
	#var_dump($settings['database']);die;
	#var_dump($settings); die();
	try {
		mysqli_report(MYSQLI_REPORT_STRICT);
		$dbh = new \mysqli(
			$settings['database']['dbhost'],
			$settings['database']['dbusername'],
			$settings['database']['dbpassword'],
			$settings['database']['dbdatabase']
		);
	} catch (\Exception $e) {
		$app = \Slim\Slim::getInstance();
		if(PHP_SAPI === 'cli') {
			cliLog($app->ll->str('database.connect'), 1, 'red');
			$app->stop();
		}
		$app->flash('error', $app->ll->str('database.connect'));
		$app->redirect($app->config['root'] . 'systemcheck?dberror');
		$app->stop();
		return;
	}
	return $dbh;
};

// Batcher
$container['batcher'] = function () {
	return new \Slimpd\Modules\database\Batcher();
};


// Filebrowser
$container['filebrowser'] = function ($cont) {
	return new \Slimpd\Modules\filebrowser\filebrowser($cont);
};


// Imageweighter
$container['imageweighter'] = function () {
	return new \Slimpd\Modules\imageweighter\Imageweighter();
};


// Localization
$container['ll'] = function () {
	return new \Slimpd\Modules\localization\Localization();
};

// Cookies
$container['cookie'] = function($cont){
    $request = $cont->get('request');
    return new \Slim\Http\Cookies($request->getCookieParams());
};




// -----------------------------------------------------------------------------
// Service factories
// -----------------------------------------------------------------------------

// monolog
$container['logger'] = function ($c) {
  #$settings = $c->get('settings');
  $logger = new Monolog\Logger('Slimpd');
  $logger->pushProcessor(new Monolog\Processor\UidProcessor());
  $logger->pushHandler(new Monolog\Handler\StreamHandler('localdata/cache/mono.log', Monolog\Logger::DEBUG));
  return $logger;
};


$container['errorHandler'] = function ($cont) {
    return function ($request, $response, $exception) use ($cont) {
    	var_dump($exception->getMessage());
        return $cont['response']->withStatus(500)
                             ->withHeader('Content-Type', 'text/html')
                             ->write('Something went wrong!!!');
    };
	// TODO refacturing of old (slim-v2) implementation
	/*
	$app->error(function(\Exception $e) use ($app, $vars){
	$vars['action'] = 'error';
	$vars['errormessage'] = $e->getMessage();
	$vars['tracestring'] = removeAppRootPrefix(str_replace(array('#', "\n"), array('<div>#', '</div>'), htmlspecialchars($e->getTraceAsString())));
	$vars['url'] = $app->request->getResourceUri();
	$vars['file'] = removeAppRootPrefix($e->getFile());
	$vars['line'] = $e->getLine();
	// delete cached config
	$app->configLoaderINI->loadConfig('master.ini', NULL, '1');
	$app->render('appless.htm', $vars);
});
	*/
};


$container['notFoundHandler'] = function ($cont) { 
	return function ($request, $response) use ($cont) { 
		return $cont['view']->render($response, 'errors/404.twig')->withStatus(404);
	};
	// TODO refacturing of old (slim-v2) implementation
	/*
	// use 404 not found as a search in case we don't have a slash in uri
	$app->notFound(function() use ($app, $vars){
		$uri = ltrim(rawurldecode($app->request->getResourceUri()),'/');
		// check if we do have a slash in uri
		if(stripos($uri, '/') !== FALSE) {
			$vars['action'] = '404';
			$app->render('surrounding.htm', $vars);
		} else {
			// trigger a search
			$app->response->redirect($app->config['root'] . 'searchall/page/1/sort/relevance/desc?q='.rawurlencode($uri) . getNoSurSuffix(), 301);
		}
	});
	*/
};

#$container[Slimpd\Repositories\AlbumRepo::class] = function($cont) {
#	return new Slimpd\Repositories\AlbumRepo($cont);
#};

$container['albumRepo'] = function($cont) {
	return new \Slimpd\Repositories\AlbumRepo($cont);
};
$container['artistRepo'] = function($cont) {
	return new \Slimpd\Repositories\ArtistRepo($cont);
};
$container['genreRepo'] = function($cont) {
	return new \Slimpd\Repositories\GenreRepo($cont);
};
$container['labelRepo'] = function($cont) {
	return new \Slimpd\Repositories\LabelRepo($cont);
};

// -----------------------------------------------------------------------------
// Action factories
// -----------------------------------------------------------------------------

$container[App\Action\HomeAction::class] = function ($c) {
	return new App\Action\HomeAction($c->get('view'), $c->get('logger'));
};