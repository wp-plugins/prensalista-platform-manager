<?php
/*
Plugin Name: Prensalista Platform Manager
Version: 1.0.0
Plugin URI: https://prensalista.com
Description: This plugin helps managing the instances created on prensalista. 
Author: Prensalista
Author URI: https://prensalista.com
*/

if ( ! function_exists( 'add_filter' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

if ( ! defined( 'PRENSALISTA_FILENAME' ) ) {
	define( 'PRENSALISTA_FILENAME', __FILE__ );
}

// Load the Prensalista Posts Manager plugin
require_once( dirname( __FILE__ ) . '/prensalista-manager-main.php' );