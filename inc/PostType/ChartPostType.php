<?php
/**
 * Chart custom post type.
 *
 * @package EasyCharts
 */

namespace EasyCharts\PostType;

use EasyCharts\Service;

/**
 * Registers the `easy_charts` post type that stores reusable charts.
 */
final class ChartPostType implements Service {

	const POST_TYPE = 'easy_charts';

	/**
	 * Attach hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'init', array( $this, 'register_post_type' ) );

		// Temporary: charts are edited with the 1.x classic metaboxes until the block-based editor lands.
		add_filter( 'use_block_editor_for_post_type', array( $this, 'disable_block_editor' ), 10, 2 );
	}

	/**
	 * Register the post type. Arguments match 1.x so existing charts and capabilities behave the same.
	 *
	 * @return void
	 */
	public function register_post_type() {
		$labels = array(
			'name'               => _x( 'Easy Charts', 'Post type general name', 'easy-charts' ),
			'singular_name'      => _x( 'Chart', 'Post type singular name', 'easy-charts' ),
			'menu_name'          => _x( 'Easy Charts', 'Admin menu', 'easy-charts' ),
			'name_admin_bar'     => _x( 'Chart', 'Add new chart on admin bar', 'easy-charts' ),
			'add_new'            => _x( 'Add New', 'Add new chart', 'easy-charts' ),
			'add_new_item'       => __( 'Add New Chart', 'easy-charts' ),
			'new_item'           => __( 'New Chart', 'easy-charts' ),
			'edit_item'          => __( 'Edit Chart', 'easy-charts' ),
			'view_item'          => __( 'View Chart', 'easy-charts' ),
			'all_items'          => __( 'All Charts', 'easy-charts' ),
			'search_items'       => __( 'Search Charts', 'easy-charts' ),
			'parent_item_colon'  => __( 'Parent Charts:', 'easy-charts' ),
			'not_found'          => __( 'No Charts found.', 'easy-charts' ),
			'not_found_in_trash' => __( 'No Charts found in Trash.', 'easy-charts' ),
		);

		register_post_type(
			self::POST_TYPE,
			array(
				'labels'          => $labels,
				'public'          => false,
				'show_ui'         => true,
				'capability_type' => 'page',
				'hierarchical'    => true,
				'menu_icon'       => 'dashicons-chart-bar',
				'rewrite'         => false,
				'query_var'       => 'easy_charts',
				'show_in_rest'    => true,
				'rest_base'       => 'easy-charts',
				// custom-fields is required for registered meta to appear in REST responses.
				'supports'        => array( 'title', 'custom-fields' ),
			)
		);
	}

	/**
	 * Keep charts on the classic editor for now.
	 *
	 * @param bool   $use_block_editor Whether the post type can be edited with the block editor.
	 * @param string $post_type        Post type being checked.
	 * @return bool
	 */
	public function disable_block_editor( $use_block_editor, $post_type ) {
		return self::POST_TYPE === $post_type ? false : $use_block_editor;
	}
}
