<?php
/**
 * uninstall.php
 *
 * @created   8/5/14 9:19 PM
 * @author    Mindshare Studios, Inc.
 * @copyright Copyright (c) 2014
 * @link      http://www.mindsharelabs.com/documentation/
 *
 */

//if uninstall not called from WordPress exit
if(!defined('WP_UNINSTALL_PLUGIN')) {
	exit();
}

if(!defined('WP_CDNJS_OPTIONS')) {
	define('WP_CDNJS_OPTIONS', 'cdnjs');
}

$option_name = WP_CDNJS_OPTIONS;
delete_option($option_name);
