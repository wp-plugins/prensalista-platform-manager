<?php

/**
 * @package Main
 */

if ( ! function_exists( 'add_filter' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

/**
 * @internal Nobody should be able to overrule the real version number as this can cause serious issues
 * with the options, so no if ( ! defined() )
 */
define( 'PRENSALISTA_VERSION', '1.0.0' );

if ( ! defined( 'PRENSALISTA_BASEPATH' ) ) {
	define( 'PRENSALISTA_BASEPATH', plugin_dir_path( PRENSALISTA_FILENAME ) );
}
if ( ! defined( 'PRENSALISTA_BASENAME' ) ) {
	define( 'PRENSALISTA_BASENAME', plugin_basename( PRENSALISTA_FILENAME ) );
}
if ( ! defined( 'PRENSALISTA_DIRNAME' ) ) {
	define('PRENSALISTA_DIRNAME', str_replace(basename(__FILE__), '', plugin_basename(__FILE__)));
}
if ( ! defined( 'PRENSALISTA_DEFAULT_SETTINGS_KEY' ) ) {
	define('PRENSALISTA_DEFAULT_SETTINGS_KEY', 'prensalista_settings');
}
if ( ! defined( 'PRENSALISTA_MU' ) ) {
	define('PRENSALISTA_MU', (function_exists('is_multisite') && is_multisite()));
}
if ( ! defined( 'PRENSALISTA_EMAIL' ) ) {
	define('PRENSALISTA_EMAIL', 'info@prensalista.com');
}
if ( ! defined( 'PUBLISHED_PRODUCTION_URL' ) ) {
	define('PUBLISHED_PRODUCTION_URL', 'https://pslt.co/API/WordPress/PostPublished/1/');
}
if ( ! defined( 'PUBLISHED_STAGING_URL' ) ) {
	define('PUBLISHED_STAGING_URL', 'https://pslt.co/API/WordPress/PostPublished/0/');
}
if ( ! defined( 'VISITED_PRODUCTION_URL' ) ) {
	define('VISITED_PRODUCTION_URL', 'https://pslt.co/API/WordPress/PostVisited/1/');
}
if ( ! defined( 'VISITED_STAGING_URL' ) ) {
	define('VISITED_STAGING_URL', 'https://pslt.co/API/WordPress/PostVisited/0/');
}




/* ***************************** ENABLE XML-RPC *************************** */

/**
 * Enable XML-RPC posting
 */
function prensalista_activate_xmlrpc()
{
	update_option('enable_xmlrpc', 1);
}
register_activation_hook(PRENSALISTA_FILENAME, 'prensalista_activate_xmlrpc');


/* ***************************** BOOTSTRAP / HOOK INTO WP *************************** */

function prensalista_bootstrap($mods)
{
	foreach($mods as $mod) 
		require_once(PRENSALISTA_BASEPATH . '/components/' . $mod);
}
prensalista_bootstrap(
	array(
		'sessions.php',
		'options.php',
		'image.php',
		'post.php',
		'short-url.php',
		'analytics.php',
		'download-image.php',
		'user.php',
		'xmlrpc.php',
		'xmlrpc-preview.php',
		'user-image.php',
		'wpml.php'
	)
);