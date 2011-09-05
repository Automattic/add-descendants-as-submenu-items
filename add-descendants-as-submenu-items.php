<?php /*

**************************************************************************

Plugin Name:  Add Descendants As Submenu Items
Plugin URI:   http://www.viper007bond.com/wordpress-plugins/add-descendants-as-submenu-items/
Description:  Automatically all of a nav menu item's descendants as submenu items. Designed for pages but will work with any hierarchical post type.
Version:      1.0.0
Author:       Alex Mills (Viper007Bond)
Author URI:   http://www.viper007bond.com/

**************************************************************************

Copyright (C) 2011 Alex Mills (Viper007Bond)

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.

**************************************************************************/

class Add_Descendants_As_Submenu_Items {

	/**
	 * Hidden input field ID/name for tracking what values to check on POST process.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $form_idtracker       = 'adasi-idtracker';

	/**
	 * ID/name prefix for the checkbox inputs.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $form_checkbox_prefix = 'adasi-child-checkbox-';

	/**
	 * admin-ajax.php action name for some server-side checks.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $ajax_action          = 'adasi_checkbox_helper';

	/**
	 * Meta key name for storing enabled status.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $meta_key             = '_adasi_enabled';

	/**
	 * Stores submenu items that have been added by this plugin.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	public $added = array();


	/**
	 * Sets up the plugin by registering its hooks
	 *
	 * @since 1.0.0
	 */
	function __construct() {
		if ( is_admin() ) {
			load_plugin_textdomain( 'add-descendants-as-submenu-items', false, dirname( plugin_basename( __FILE__ ) ) . '/localization/' );

			add_action( 'admin_enqueue_scripts',         array( &$this, 'maybe_enqueue_script' ) );
			add_action( 'wp_ajax_' . $this->ajax_action, array( &$this, 'ajax_get_menu_status' ) );
			add_action( 'wp_update_nav_menu',            array( &$this, 'save_nav_menu_custom_fields' ) );
		} else {
			add_filter( 'wp_get_nav_menu_items',         array( &$this, 'add_children_to_menu' ) );
		}
	}

	/**
	 * If on the nav menu configuration page, this enqueues this plugin's Javascript file and it's dynamic parameters.
	 *
	 * @since 1.0.0
	 */
	function maybe_enqueue_script( $hook_suffix ) {
		if ( 'nav-menus.php' != $hook_suffix )
			return;

		$script_slug = 'adasi-checkboxes';

		wp_enqueue_script( $script_slug, plugins_url( 'checkboxes.js', __FILE__ ), array( 'jquery' ), '1.0.0' );

		// Pass dynamic values to the Javascript file
		$params = array(
			'idtracker'      => $this->form_idtracker,
			'ajaxaction'     => $this->ajax_action,
			'checkboxprefix' => $this->form_checkbox_prefix,
			'checkboxdesc'   => __( 'Automatically add all descendants as submenu items', 'add-descendants-as-submenu-items' ),
		);

		wp_localize_script( $script_slug, 'ADASIParams', $params );
	}

	/**
	 * AJAX POST handler that outputs JSON saying whether to add a checkbox or not and if that checkbox should be checked.
	 * This is called by admin-ajax.php.
	 *
	 * @since 1.0.0
	 */
	function ajax_get_menu_status() {
		$response = array(
			'add' => 0,
		);

		if ( ! current_user_can( 'edit_theme_options' ) )
			exit( json_encode( $response ) );

		if ( empty( $_POST['id'] ) || ! $id = (int) $_POST['id'] )
			exit( json_encode( $response ) );

		if ( 'post_type' != get_post_meta( $id, '_menu_item_type', true ) || ! is_post_type_hierarchical( get_post_meta( $id, '_menu_item_object', true ) ) )
			exit( json_encode( $response ) );

		$response['add'] = 1;
		$response['checked'] = ( get_post_meta( $id, $this->meta_key, true ) ) ? 1 : 0;

		exit( json_encode( $response ) );
	}

	/**
	 * Saves the status of the checkboxes that were added to the nav menu configuration panel.
	 * Called when a nav menu is saved.
	 *
	 * @since 1.0.0
	 */
	function save_nav_menu_custom_fields() {
		if ( empty( $_POST[$this->form_idtracker] ) )
			return;

		$ids = array_map( 'intval', explode( ',', ltrim( $_POST[$this->form_idtracker], ',' ) ) );

		foreach ( $ids as $id ) {
			if ( ! $id )
				continue;

			if ( isset( $_POST[$this->form_checkbox_prefix . $id] ) )
				update_post_meta( $id, $this->meta_key, true );
			else
				delete_post_meta( $id, $this->meta_key );
		}

		// This gets called twice for some reason
		remove_action( 'wp_update_nav_menu', array( &$this, __FUNCTION__ ) );
	}

	/**
	 * Loop through all nav menu items checking whether the functionality has been enabled or not for them.
	 * If enabled, add in submenu items for all of their descendants
	 *
	 * @since 1.0.0
	 *
	 * @param array $items Array of nav menu items.
	 * @return array Potentially modified array of nav menu items.
	 */
	function add_children_to_menu( $items ) {
		$menu_order = count( $items ) + 1000;
		$filter_added = false;

		foreach ( $items as $item ) {
			if ( 'post_type' != $item->type || ! is_post_type_hierarchical( $item->object ) || ! get_post_meta( $item->db_id, $this->meta_key, true ) )
				continue;

			// Get all descendants
			// Using get_pages() instead of get_posts() because we want ALL descendants
			$children = get_pages( array(
				'child_of'    => $item->object_id,
				'post_type'   => $item->object,
				'sort_column' => 'menu_order, post_title',
			) );

			if ( ! $children )
				continue;

			// Menu items are being added, so later fix the "current" values for highlighting
			if ( ! $filter_added )
				add_filter( 'wp_nav_menu_objects',  array( &$this, 'fix_menu_current_item' ) );

			// Add each child to the menu
			foreach ( $children as $child ) {
				$this->added[$child->ID] = true; // We'll need this later

				$child = wp_setup_nav_menu_item( $child );
				$child->db_id = $child->ID;

				// Set the parent menu item.
				// When adding items as children of existing menu items, their IDs won't match up
				// which means that the post_parent value can't always be used.
				if ( $child->post_parent == $item->object_id ) {
					$child->menu_item_parent = $item->ID; // Children
				} else {
					$child->menu_item_parent = $child->post_parent; // Grandchildren, etc.
				}

				// The menu_order has to be unique, so make up new ones
				// The items are already sorted due to the get_pages()
				$menu_order++;
				$child->menu_order = $menu_order;

				$items[] = $child;
			}
		}

		return $items;
	}

	/**
	 * Fixes the attributes of all ancestors of all menu items added by this plugin.
	 * This is to ensure that the selected functionality works.
	 *
	 * @since 1.0.0
	 *
	 * @param array $items Array of nav menu items.
	 * @return array Potentially modified array of nav menu items.
	 */
	function fix_menu_current_item( $items ) {
		global $wp_query;

		$queried_object = $wp_query->get_queried_object();
		$queried_object_id = (int) $wp_query->queried_object_id;

		// Only need to fix items added by this plugin
		if ( empty( $this->added[$queried_object_id] ) )
			return $items;

		_get_post_ancestors( $queried_object );

		foreach ( $items as $item ) {
			if ( ! in_array( $item->object_id, $queried_object->ancestors ) )
				continue;

			$item->current_item_ancestor = true;
			$item->classes[] = 'current-menu-ancestor';
			$item->classes[] = 'current_page_ancestor';

			// If menu item is direct parent of current page
			if ( $item->object_id == $queried_object->post_parent ) {
				$item->current_item_parent = true;
				$item->classes[] = 'current-menu-parent';
				$item->classes[] = 'current_page_parent';
			}
		}

		return $items;
	}
}

// Initialize the plugin
$add_descendants_as_submenu_items = new Add_Descendants_As_Submenu_Items();

?>