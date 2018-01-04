<?php
/*
Plugin Name: ZnWP WebService Plugin
Plugin URI:  https://github.com/zionsg/znwp-webservice-plugin
Description: This plugin provides a settings page and 2 simple methods for accessing a generic web service.
Author:      Zion Ng
Author URI:  https://intzone.com/
Version:     1.0.0
*/

require_once 'ZnWP_WebService.php'; // PSR-1 states files should not declare classes AND execute code

// init must be run after theme setup to allow functions.php in theme to add action hook
$znwp_webservice_plugin = new ZnWP_WebService(plugin_basename(__FILE__));
add_action('after_setup_theme', array($znwp_webservice_plugin, 'init'));
