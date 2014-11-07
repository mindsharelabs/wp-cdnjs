<?php
/*
Plugin Name: WP cdnjs
Plugin URI: http://wordpress.org/plugins/wp-cdnjs/
Description: Effortlessly include any CSS or JavaScript Library hosted at cdnjs.com on your WordPress site.
Version: 0.1.4 DEV
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
	define('WP_CDNJS_VERSION', '0.1.4');
}

if(!defined('WP_CDNJS_MIN_WP_VERSION')) {
	define('WP_CDNJS_MIN_WP_VERSION', '4.0');
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
		 * CDNJS API base URL
		 *
		 * @var string
		 */
		private $cdnjs_api_uri = '//api.cdnjs.com/';

		/**
		 * Version of Select2 to use for the admin screens.
		 *
		 * @var string
		 */
		private $select2_version = '3.5.2';

        /**
		 * Version of Font Awesome to use for the admin screens.
		 *
		 * @var string
		 */
		private $fa_version = '4.2.0';

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
			register_activation_hook(__FILE__, array($this, 'activate'));
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
		 * Returns the plugin URL
		 *
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
		 *
		 * Adds default option to enable scripts if settings are not present in DB
		 *
		 */
		public function activate() {

			$settings = $this->get_settings(WP_CDNJS_OPTIONS);
			if(!$settings) {
				$settings = update_option(WP_CDNJS_OPTIONS, array(
					'cdnjs_settings_enable_scripts' => TRUE
				));
			}
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
			$settings = $this->get_settings(WP_CDNJS_OPTIONS);
			echo '<pre>'.var_export($settings, TRUE).'</pre>';
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
		 * Programmatically inserts a setting into the options array.
		 *
		 * @param $option_group
		 * @param $section_id
		 * @param $field_id
		 * @param $value
		 *
		 * @return bool
		 */
		public function apply_setting($option_group, $section_id, $field_id, $value) {
			$options = get_option($option_group);
			if($options) {
				$options[$option_group.'_'.$section_id.'_'.$field_id] = $value;
				update_option($option_group, $options);
			}

			return FALSE;
		}

		/**
		 * Simple API function for adding CDN scripts via theme or plugin files.
		 *
		 * @param $scripts array An Array of script to enqueue. Once added the script will show up on the WP cdnjs admin screen.
		 * @param $scripts array [string]slug  CDN slug
		 * @param $scripts array [array]details  Array of settings for this library
		 * @param $details [string]name Name of the library to include.
		 * @param $details [string]version Library version.
		 * @param $details [array]assets Array of additional assets to include.
		 * @param $details [bool]location Boolean to include script in header or footer.
		 * @param $details [bool]enabled Should the script be enabled or disabled.
		 *
		 * @return bool
		 */
		public function register_script($scripts) {
			$registered = $this->get_setting(WP_CDNJS_OPTIONS, 'settings', 'scripts', $scripts);
			if(!is_array($registered)) {
				$registered = array();
			}
			if(is_array($scripts)) {
				$scripts = array_merge($scripts, $registered);
				$this->apply_setting(WP_CDNJS_OPTIONS, 'settings', 'scripts', $scripts);
			}

			return FALSE;
		}

		/**
		 *
		 * Checks the options array to see if a specific CDN library is already enqueued. Does NOT check the enabled/disabled state, just checks to see if the user has a library added on the admin screen.
		 *
		 * @param string $script_slug Slug of the library to check.
		 *
		 * @return bool
		 */
		public function is_script_registered($script_slug) {
			$script_slug = sanitize_key($script_slug);
			if(isset($script_slug)) {
				$search = $this->get_setting(WP_CDNJS_OPTIONS, 'settings', 'scripts');

				if(is_array($search) && array_key_exists($script_slug, $search)) {
					return TRUE;
				} else {
					return FALSE;
				}
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

		public function lookup_cdnjs_library($search) {

			$transient_key = WP_CDNJS_PLUGIN_SLUG.'_'.sanitize_key($search);

			// Check for transient, if none, grab remote HTML file
			if(FALSE === ($html = get_transient($transient_key))) {

				// Get remote HTML file
				//$protocol = is_SSL() ? 'https://' : 'http://';
				$response = wp_remote_get('https:'.$this->cdnjs_api_uri.'libraries?search='.$search.'&fields=version,filename,description,assets');

				// Check for error
				if(is_wp_error($response)) {
					//var_export($response);
					return FALSE;
				}

				// Parse remote HTML file
				$data = wp_remote_retrieve_body($response);

				// Check for error
				if(is_wp_error($data)) {
					return FALSE;
				} else {
					$data = json_decode($data, TRUE);

					// just grab the results sub array
					$data = $data['results'][0];

					// modify the assets array to only include the latest version files
					if(array_key_exists('assets', $data)) {
						$data['assets'] = $data['assets'][0]['files'];

						// Loop through assets to see if .min version is available and use that
						$final_assets = array();
						foreach($data['assets'] as $key => $asset) {
							// more cleanup
							unset($data['assets'][$key]['size']);
							//$data['assets'][$key]['name'] = $data['assets'][$key];

							if(strpos($asset['name'], '.min.') !== FALSE) {
								$final_assets[] = $asset['name'];
							}
						}
						if(!empty($final_assets)) {
							$data['assets'] = $final_assets;
						}
						$data['location'] = 0;
						$data['enabled'] = 1;
					}

					// remove extra data from the array
					unset($data['description']);
					unset($data['latest']);
					unset($data['filename']);
				}

				set_transient($transient_key, $data, apply_filters('wp_cdnjs_update_interval', 7 * 24 * HOUR_IN_SECONDS));
			}

			// wrap the library data in a new array with the slug as the index
			$data = array(sanitize_key($data['name']) => $data);

			return $data;
		}

		/**
		 *
		 * Add a settings link to plugins page.
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
			wp_enqueue_style('cdnjs-select2', $this->cdnjs_uri.'select2/'.$this->select2_version.'/select2.min.css');
			wp_enqueue_style('cdnjs-select2-bootstrap', $this->cdnjs_uri.'select2/'.$this->select2_version.'/select2-bootstrap.min.css');
			wp_enqueue_style('cdnjs-font-awesome', $this->cdnjs_uri.'font-awesome/'.$this->fa_version.'/css/font-awesome.min.css');
			wp_enqueue_style('cdnjs-styles', WP_CDNJS_DIR_URL.'/assets/css/cdnjs-styles.min.css');
		}

		/**
		 * CDNJS scripts
		 *
		 */
		public function cdnjs_scripts() {

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
/*
$my_scripts = $wp_cdnjs->lookup_cdnjs_library('django.js');

if(!$wp_cdnjs->is_script_registered('django.js')) {
	if($my_scripts) {
		$wp_cdnjs->register_script($my_scripts);
	}
}
*/
