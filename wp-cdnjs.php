<?php
/*
Plugin Name: WP cdnjs
Plugin URI: http://wordpress.org/plugins/wp-cdnjs/
Description: Effortlessly include any CSS or JavaScript Library hosted at cdnjs.com on your WordPress site.
Version: 0.1.3
Author: Mindshare Labs / ANAGR.AM
Author URI: https://mindsharelabs.com/
License: GNU General Public License
License URI: license.txt
Text Domain: wp-cdnjs
Domain Path: /lang
*/

/**
 *
 * Copyright 2014  Mindshare Studios, Inc. (http://mind.sh/are/)
 *
 * Plugin template was forked from the WP Settings Framework by Gilbert Pellegrom http://dev7studios.com
 * and the WordPress Plugin Boilerplate by Christopher Lamm http://www.theantichris.com
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 3, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 */

// deny direct access
if(!function_exists('add_action')) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit();
}

if(!defined('WP_CDNJS_VERSION')) {
	define('WP_CDNJS_VERSION', '0.1');
}

if(!defined('WP_CDNJS_MIN_WP_VERSION')) {
	define('WP_CDNJS_MIN_WP_VERSION', '3.9');
}

if(!defined('WP_CDNJS_PLUGIN_NAME')) {
	define('WP_CDNJS_PLUGIN_NAME', 'WP cdnjs');
}

if(!defined('WP_CDNJS_PLUGIN_SLUG')) {
	define('WP_CDNJS_PLUGIN_SLUG', dirname(plugin_basename(__FILE__))); // plugin-slug
}

if(!defined('WP_CDNJS_DIR_PATH')) {
	define('WP_CDNJS_DIR_PATH', plugin_dir_path(__FILE__));
}

if(!defined('WP_CDNJS_DIR_URL')) {
	define('WP_CDNJS_DIR_URL', trailingslashit(plugins_url(NULL, __FILE__)));
}

if(!defined('WP_CDNJS_OPTIONS')) {
	define('WP_CDNJS_OPTIONS', 'cdnjs');
}

if(!defined('WP_CDNJS_TEMPLATE_PATH')) {
	define('WP_CDNJS_TEMPLATE_PATH', trailingslashit(get_template_directory()).trailingslashit(WP_CDNJS_PLUGIN_SLUG));
	// e.g. /wp-content/themes/__ACTIVE_THEME__/plugin-slug
}

// check WordPress version
global $wp_version;
if(version_compare($wp_version, WP_CDNJS_MIN_WP_VERSION, "<")) {
	exit(WP_CDNJS_PLUGIN_NAME.' requires WordPress '.WP_CDNJS_MIN_WP_VERSION.' or newer.');
}

if(!class_exists('WP_CDNJS')) : /**
 * Class wp_cdnjs
 */ {
	class wp_cdnjs {

		/**
		 * @var wp_cdnjs_settings
		 */
		private $settings_framework;

		/**
		 * CDNJS base URL
		 *
		 * @var string
		 */
		private $cdnjs_uri = '//cdnjs.cloudflare.com/ajax/libs/';

		/**
		 * Version of Select2 to use for the admin screens.
		 *
		 * @var string
		 */
		private $select2_version = '3.4.5';

		/**
		 * Version of Select2 to use for the admin screens.
		 *
		 * @var string
		 */
		private $bootstrap_version = '3.1.1';

		/**
		 * Initialize the plugin. Set up actions / filters.
		 *
		 */
		public function __construct() {

			// i8n
			add_action('plugins_loaded', array($this, 'load_textdomain'));

			// Admin scripts
			add_action('admin_enqueue_scripts', array($this, 'register_admin_scripts'));
			add_action('admin_enqueue_scripts', array($this, 'register_admin_styles'));

			// Plugin action links
			add_filter('plugin_action_links', array($this, 'plugin_action_links'), 10, 2);

			// Activation hooks
			//register_activation_hook(__FILE__, array($this, 'activate'));
			//register_deactivation_hook(__FILE__, array($this, 'deactivate'));

			// Uninstall hook
			register_uninstall_hook(WP_CDNJS_DIR_PATH.'uninstall.php', NULL);

			// Settings Framework
			add_action('admin_menu', array($this, 'admin_menu'), 99);
			require_once(WP_CDNJS_DIR_PATH.'lib/settings-framework/settings-framework.php');
			$this->settings_framework = new wp_cdnjs_settings(WP_CDNJS_DIR_PATH.'views/wp-cdnjs-settings.php', WP_CDNJS_OPTIONS);
			// Add an optional settings-framework validation filter (recommended)
			add_filter($this->settings_framework->get_option_group().'_validate', array($this, 'validate_settings'));

			// Enqueue CDNJS libraries
			add_action('init', array($this, 'init'), 0, 0);
		}

		/**
		 * Allows user to override the default action used to enqueue scripts.
		 * See FAQ in readme.txt for usage.
		 *
		 */
		public function init() {
			$init_action = apply_filters('wp_cdnjs_init_action', 'init');
			add_action($init_action, array($this, 'cdnjs_scripts'));
		}

		/**
		 * Returns the class name and version.
		 *
		 * @return string
		 */
		public function __toString() {
			return get_class($this).' '.$this->get_version();
		}

		/**
		 * Returns the plugin version number.
		 *
		 * @return string
		 */
		public function get_version() {
			return WP_CDNJS_VERSION;
		}

		/**
		 * @return string
		 */
		public function get_plugin_url() {
			return WP_CDNJS_DIR_URL;
		}

		/**
		 * @return string
		 */
		public function get_plugin_path() {
			return WP_CDNJS_DIR_PATH;
		}

		/**
		 * Register the plugin text domain for translation
		 *
		 */
		public function load_textdomain() {
			load_plugin_textdomain('wp-cdnjs', FALSE, WP_CDNJS_DIR_PATH.'/lang');
		}

		/**
		 * Activation
		 */
		public function activate() {
		}

		/**
		 * Deactivation
		 */
		public function deactivate() {
		}

		/**
		 * Install
		 */
		public function install() {
		}

		/**
		 * WordPress options page
		 *
		 */
		public function admin_menu() {

			// Settings page
			add_submenu_page('options-general.php', __(WP_CDNJS_PLUGIN_NAME.' Settings', 'wp-cdnjs'), __(WP_CDNJS_PLUGIN_NAME.' Settings', 'wp-cdnjs'), 'manage_options', WP_CDNJS_PLUGIN_SLUG, array(
				$this,
				'settings_page'
			));
		}

		/**
		 *  Settings page
		 *
		 */
		public function settings_page() {

			?>
			<div class="wrap">
				<div id="icon-options-general" class="icon32"></div>
				<h2><?php echo WP_CDNJS_PLUGIN_NAME; ?></h2>
				<?php
				// Output settings-framework form
				$this->settings_framework->settings();
				?>
			</div>
			<?php

			// Get settings
			//$settings = $this->get_settings(WP_CDNJS_OPTIONS);
			//echo '<pre>'.print_r($settings, TRUE).'</pre>';

		}

		/**
		 * Settings validation
		 *
		 * @see $sanitize_callback from http://codex.wordpress.org/Function_Reference/register_setting
		 *
		 * @param $input
		 *
		 * @return mixed
		 */
		public function validate_settings($input) {
			return $input;
		}

		/**
		 * Converts the settings-framework filename to option group id
		 *
		 * @param $settings_file string settings-framework file
		 *
		 * @return string option group id
		 */
		public function get_option_group($settings_file) {
			$option_group = preg_replace("/[^a-z0-9]+/i", "", basename($settings_file, '.php'));

			return $option_group;
		}

		/**
		 * Get the settings from a settings-framework file/option group
		 *
		 * @param $option_group string option group id
		 *
		 * @return array settings
		 */
		public function get_settings($option_group) {
			return get_option($option_group);
		}

		/**
		 * Get a setting from an option group
		 *
		 * @param $option_group string option group id
		 * @param $section_id   string section id
		 * @param $field_id     string field id
		 *
		 * @return mixed setting or false if no setting exists
		 */
		public function get_setting($option_group, $section_id, $field_id) {
			$options = get_option($option_group);
			if(isset($options[$option_group.'_'.$section_id.'_'.$field_id])) {
				return $options[$option_group.'_'.$section_id.'_'.$field_id];
			}

			return FALSE;
		}

		/**
		 * Delete all the saved settings from a settings-framework file/option group
		 *
		 * @param $option_group string option group id
		 */
		public function delete_settings($option_group) {
			delete_option($option_group);
		}

		/**
		 * Deletes a setting from an option group
		 *
		 * @param $option_group string option group id
		 * @param $section_id   string section id
		 * @param $field_id     string field id
		 *
		 * @return mixed setting or false if no setting exists
		 */
		public function delete_setting($option_group, $section_id, $field_id) {
			$options = get_option($option_group);
			if(isset($options[$option_group.'_'.$section_id.'_'.$field_id])) {
				$options[$option_group.'_'.$section_id.'_'.$field_id] = NULL;

				return update_option($option_group, $options);
			}

			return FALSE;
		}

		/**
		 *
		 * Add a settings link to plugins page
		 *
		 * @param $links
		 * @param $file
		 *
		 * @return array
		 */
		public function plugin_action_links($links, $file) {
			if($file == plugin_basename(__FILE__)) {
				$settings_link = '<a href="options-general.php?page='.WP_CDNJS_PLUGIN_SLUG.'" title="'.__(WP_CDNJS_PLUGIN_NAME, 'wp-cdnjs').'">'.__('Settings', 'wp-cdnjs').'</a>';
				array_unshift($links, $settings_link);
			}

			return $links;
		}

		/**
		 * Enqueue and register JavaScript
		 */
		public function register_admin_scripts() {
			// @todo make function for enqueueing CDNJS stuff and use that here instead
			wp_enqueue_script('cdnjs-select2', $this->cdnjs_uri.'select2/'.$this->select2_version.'/select2.min.js', array('jquery'));
			wp_enqueue_script('jquery-ui-sortable');
			//wp_enqueue_script('jquery-ui-core');

			wp_register_script('wp-cdnjs', WP_CDNJS_DIR_URL.'/assets/js/wp-cdnjs.js');
			$translation_array = array(
				'add_assets'         => __('Add Assets', 'wp-cdnjs'),
				'footer'             => __('Footer', 'wp-cdnjs'),
				'header'             => __('Header', 'wp-cdnjs'),
				'inc_assets'         => __('Included Assets', 'wp-cdnjs'),
				'no_addl_assets'     => __('Included Assets', 'wp-cdnjs'),
				'remove'             => __('Remove', 'wp-cdnjs'),
				'search_placeholder' => __('Search cdnjs Libraries', 'wp-cdnjs'),
				'version'            => __('Version', 'wp-cdnjs')
			);
			wp_localize_script('wp-cdnjs', 'cdnjs_text', $translation_array);
			wp_enqueue_script('wp-cdnjs');
		}

		/**
		 * Enqueue and register CSS
		 */
		public function register_admin_styles() {
			// @todo make function for enqueueing CDNJS stuff and use that here instead
			wp_enqueue_style('cdnjs-select2', $this->cdnjs_uri.'select2/'.$this->select2_version.'/select2.min.css');
			wp_enqueue_style('cdnjs-select2-bootstrap', $this->cdnjs_uri.'select2/'.$this->select2_version.'/select2-bootstrap.css'); //@todo add min
			wp_enqueue_style('cdnjs-font-awesome', '//cdnjs.cloudflare.com/ajax/libs/font-awesome/4.1.0/css/font-awesome.min.css');
			wp_enqueue_style('cdnjs-styles', WP_CDNJS_DIR_URL.'/assets/css/cdnjs-styles.css');
		}

		/**
		 * CDNJS scripts
		 *
		 */
		public function cdnjs_scripts() {

			//			var_dump($this->get_setting(WP_CDNJS_OPTIONS, 'settings', 'scripts'));
			//			die;

			$enabled = $this->get_setting(WP_CDNJS_OPTIONS, 'settings', 'enable_scripts');

			// filter to allow script in WordPress Admin area
			$show_in_admin = apply_filters('wp_cdnjs_allow_in_admin', FALSE);

			// check if output is enabled
			if($enabled) {
				// only output on frontend, unless $show_in_admin is also TRUE
				if((is_admin() && $show_in_admin) || !is_admin()) {
					foreach($this->get_setting(WP_CDNJS_OPTIONS, 'settings', 'scripts') as $plugin) {
						//var_dump($plugin['assets']);
						if($plugin['enabled']) {
							foreach($plugin['assets'] as $asset) {
								//var_dump($asset.' '.$this->get_file_extension($asset));

								$asset_name = sanitize_title($asset);
								switch($this->get_file_extension($asset)) {
									case 'js':
										wp_enqueue_script($asset_name.'-wp-cdnjs', $this->cdnjs_uri.$plugin['name'].'/'.$plugin['version'].'/'.$asset, array(), $plugin['version'], (bool) $plugin['location']);
										break;
									case 'css':
										wp_enqueue_style($asset_name.'-wp-cdnjs', $this->cdnjs_uri.$plugin['name'].'/'.$plugin['version'].'/'.$asset, array(), $plugin['version']);
										break;
								}
							}
						}
					}
				}
			}
		}

		/**
		 * Returns the file extension from a given asset.
		 *
		 * @param $file_name
		 *
		 * @return string
		 */
		private function get_file_extension($file_name) {
			return substr(strrchr($file_name, '.'), 1);
		}
	}
}

endif;

$wp_cdnjs = new wp_cdnjs();
