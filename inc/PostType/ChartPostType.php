<?php
/**
 * Chart custom post type.
 *
 * @package EasyCharts
 */

namespace EasyCharts\PostType;

use EasyCharts\Rest\ChartPostsController;
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

		// Charts can only be edited in the block editor, so keep them there even when the
		// Classic Editor plugin (which filters at priority 100) switches other posts to classic.
		add_filter( 'use_block_editor_for_post_type', array( $this, 'force_block_editor_for_type' ), 200, 2 );
		add_filter( 'use_block_editor_for_post', array( $this, 'force_block_editor_for_post' ), 200, 2 );
	}

	/**
	 * Register the post type. Arguments match 1.x so existing charts and capabilities behave the same.
	 *
	 * @return void
	 */
	public function register_post_type() {
		$labels = array(
			'name'                     => _x( 'Easy Charts', 'Post type general name', 'easy-charts' ),
			'singular_name'            => _x( 'Chart', 'Post type singular name', 'easy-charts' ),
			'menu_name'                => _x( 'Easy Charts', 'Admin menu', 'easy-charts' ),
			'name_admin_bar'           => _x( 'Chart', 'Add new chart on admin bar', 'easy-charts' ),
			'add_new'                  => _x( 'Add New', 'Add new chart', 'easy-charts' ),
			'add_new_item'             => __( 'Add New Chart', 'easy-charts' ),
			'new_item'                 => __( 'New Chart', 'easy-charts' ),
			'edit_item'                => __( 'Edit Chart', 'easy-charts' ),
			'view_item'                => __( 'View Chart', 'easy-charts' ),
			'all_items'                => __( 'All Charts', 'easy-charts' ),
			'search_items'             => __( 'Search Charts', 'easy-charts' ),
			'parent_item_colon'        => __( 'Parent Charts:', 'easy-charts' ),
			'not_found'                => __( 'No Charts found.', 'easy-charts' ),
			'not_found_in_trash'       => __( 'No Charts found in Trash.', 'easy-charts' ),
			// Block editor notices; charts would otherwise say "Page updated." (page capabilities).
			'item_published'           => __( 'Chart published.', 'easy-charts' ),
			'item_published_privately' => __( 'Chart published privately.', 'easy-charts' ),
			'item_reverted_to_draft'   => __( 'Chart reverted to draft.', 'easy-charts' ),
			'item_scheduled'           => __( 'Chart scheduled.', 'easy-charts' ),
			'item_updated'             => __( 'Chart updated.', 'easy-charts' ),
			'item_trashed'             => __( 'Chart trashed.', 'easy-charts' ),
		);

		register_post_type(
			self::POST_TYPE,
			array(
				'labels'                => $labels,
				'public'                => false,
				'show_ui'               => true,
				'capability_type'       => 'page',
				'hierarchical'          => true,
				'menu_icon'             => 'dashicons-chart-bar',
				'rewrite'               => false,
				'query_var'             => 'easy_charts',
				'show_in_rest'          => true,
				'rest_base'             => 'easy-charts',
				// Reading charts over REST needs edit_posts (ChartPostsController).
				'rest_controller_class' => ChartPostsController::class,
				// custom-fields is required for registered meta to appear in REST responses.
				'supports'              => array( 'title', 'editor', 'custom-fields' ),
				// One locked chart block, bound to the chart's _easy_charts_chart meta.
				'template'              => array( array( 'easy-charts/chart' ) ),
				'template_lock'         => 'all',
			)
		);
	}

	/**
	 * Always use the block editor for the chart post type.
	 *
	 * @param bool   $use_block_editor Whether the post type can be edited with the block editor.
	 * @param string $post_type        Post type being checked.
	 * @return bool
	 */
	public function force_block_editor_for_type( $use_block_editor, $post_type ) {
		return self::POST_TYPE === $post_type ? true : $use_block_editor;
	}

	/**
	 * Always use the block editor for chart posts.
	 *
	 * @param bool         $use_block_editor Whether the post can be edited with the block editor.
	 * @param \WP_Post|int $post             Post being checked.
	 * @return bool
	 */
	public function force_block_editor_for_post( $use_block_editor, $post ) {
		return self::POST_TYPE === get_post_type( $post ) ? true : $use_block_editor;
	}
}
