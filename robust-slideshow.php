<?php
/*
Plugin Name: Robust Slideshows
Description: Assists in the creation of robust slideshows, which can include photos, videos and HTML
Version: 1.1
Author: Ten-321 Enterprises
Author URI: http://ten-321.com
License: GPL2
*/

if ( ! class_exists( 'robust_slideshow' ) )
	require_once( plugin_dir_path( __FILE__ ) . 'classes/class-robust-slideshow.php' );

add_action( 'plugins_loaded', 'init_robust_slideshow' );
function init_robust_slideshow() {
	global $robust_slideshow_obj;
	$robust_slideshow_obj = new robust_slideshow();
}