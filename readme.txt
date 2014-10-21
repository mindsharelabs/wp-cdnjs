=== WP cdnjs ===
Contributors: mindshare, geetjacobs, patkirts
Donate link: http://mind.sh/are/donate/
Tags: cdnjs, cloudflare, js, css, scripts, cdn, libraries
Requires at least: 3.8
Tested up to: 4.0
Stable tag: 0.1.3
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Effortlessly include any CSS or JavaScript Library hosted at http://cdnjs.com on your WordPress site.

== Description ==

An extremely elegant plugin that allows you to search all http://cdnjs.com libraries and include them on your site.

* Builtin cdnjs.com search
* Reorder included files with an intuitive drag-and-drop interface
* Integrates seamlessly with WordPress (no developer up-sells or donation requests)
* Choose the secondary assets you want to include
* Selects the minified version or non-minified version
* Optionally select any additional assets you'd like
* Specify where to include files (header / footer)
* Options to globally or individually enable and disable included libraries
* SSL support

== Installation ==

1. Automatically install using the builtin WordPress Plugin installer or...
1. Upload entire `wp-cdnjs` folder to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.

== Frequently Asked Questions ==

= How can I change what action is used to enqueue the CDNJS scripts? =

You can override the default action ('init') that enqueues the CDNJS script like so:
`
add_filter('wp_cdnjs_init_action', 'my_cdnjs_init_action');
function my_cdnjs_init_action() {
	return 'get_sidebar'; // the action tag you wish to use
}
`

= Is it possible to have all enabled scripts load in the WordPress Admin area? =

Yes. You can use this filter:
`
add_filter('wp_cdnjs_allow_in_admin', 'my_cdnjs_allow_in_admin');
function my_cdnjs_allow_in_admin() {
	return TRUE;
}
`
== Screenshots ==

1. wp-cdnjs settings page

2. wp-cdnjs settings page

== Changelog ==

= 0.1.3 =
* Update for 4.0
*


= 0.1.2 =
* Bugfix for header / footer setting being reversed

= 0.1.1 =
* Added .pot file for translation
* Fixed logo and WP version

= 0.1 =
*   Initial release
