<?php

declare(strict_types = 1);

/**
 * Plugin Name: WP Toggle Toolbar
 * Description: Easily toggle the WordPress admin toolbar
 * Author: biohzrdmx
 * Version: 1.0
 * Plugin URI: http://github.com/biohzrdmx/wp-toolbar
 * Author URI: http://github.com/biohzrdmx
 * Text Domain: toolbar
 * Domain Path: /lang/
 */

if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if( ! class_exists('ToggleToolbarPlugin') ) {

	class ToggleToolbarPlugin {

		static function init() {
			global $wp;
			$folder = dirname( plugin_basename(__FILE__) );
			load_plugin_textdomain('toolbar', false, "{$folder}/lang");
			# Register action listeners
			if (! is_admin() ) {
				if ( is_admin_bar_showing() ) {
					add_action('admin_bar_menu', function($admin_bar) use ($wp) {
						$url = add_query_arg( $wp->query_vars, home_url( $wp->request ) );
						$admin_bar->add_menu( array(
							'id'    => 'toolbar',
							'title' => __('Toolbar', 'toolbar'),
							'href'  => $url,
							'meta'  => array(
								'title' => __('Toolbar', 'toolbar'),
							),
						));
						array_unshift($wp->query_vars, ['wp_admin_hide' => 1]);
						$url = add_query_arg( $wp->query_vars, home_url( $wp->request ) );
						$admin_bar->add_menu( array(
							'id'    => 'action-hide',
							'parent' => 'toolbar',
							'title' => __('Hide', 'toolbar'),
							'href'  => $url,
							'meta'  => array(
								'title' => __('Hide', 'toolbar')
							),
						));
					}, 200);
				} else {
					add_action('wp_footer', function() use ($wp) {
						$admin_url = admin_url( '/' );
						$menu_text = __('WordPress', 'toolbar');
						array_unshift($wp->query_vars, ['wp_admin_hide' => 0]);
						$url = add_query_arg( $wp->query_vars, home_url( $wp->request ) );
						$submenu_items = [
							[
								'url' => admin_url('/'),
								'text' => __('Dashboard', 'toolbar'),
							],
							[
								'url' => wp_logout_url( home_url('/') ),
								'text' => __('Log Out', 'toolbar'),
							],
							[
								'url' => $url,
								'text' => __('Show toolbar', 'toolbar'),
							],
						];
						# Add an edit item
						$edit = get_edit_post_link();
						if ($edit && ( is_page() || is_singular() )) {
							array_splice($submenu_items, 1, 0, [
								[
									'url' => $edit,
									'text' => is_page() ? __('Edit page', 'toolbar') : __('Edit post', 'toolbar'),
								]
							]);
						}
						$submenu_items = array_map(function($item) {
							return sprintf('<li><a href="%s">%s</a></li>', $item['url'], $item['text']);
						}, $submenu_items);
						$submenu_items = implode('', $submenu_items);
						printf('<div class="wp-admin-menu"><ul><li><a href="%s">%s</a><ul>%s</ul></li></ul></div>', $admin_url, $menu_text, $submenu_items);
					});
				}

				if ( isset( $_REQUEST['wp_admin_hide'] ) ) {
					$protocol = ( $_SERVER["HTTPS"] ?? '' ) == 'on' ? 'https' : 'http';
					$domain = $_SERVER['HTTP_HOST'];
					$request = $_SERVER['REQUEST_URI'];
					$url = sprintf('%s://%s%s', $protocol, $domain, $request);
					$url = preg_replace('/wp_admin_hide=\d&?/', '', $url);
					update_user_meta( get_current_user_id(), 'show_admin_bar_front', $_REQUEST['wp_admin_hide'] == 1 ? 'false' : 'true' );
					header('Location: ' . $url);
					exit;
				}
			}

			if ( get_current_user_id() ) {
				add_action('wp_head', function() {
					$dir = plugin_dir_url(__FILE__);
					wp_enqueue_style('dashicons');
					wp_enqueue_style('wp-admin-bar', "{$dir}plugin.css", [], '1.0');
				}, 1);
			}
		}

	}

	add_action( 'init', 'ToggleToolbarPlugin::init' );
}