<?php

/**
 * Class ActionScheduler_wpPostStore_PostTypeRegistrar
 * @codeCoverageIgnore
 */
class ActionScheduler_wpPostStore_PostTypeRegistrar {
	public function register() {
		register_post_type( ActionScheduler_wpPostStore::POST_TYPE, $this->post_type_args() );
	}

	/**
	 * Build the args array for the post type definition
	 *
	 * @return array
	 */
	protected function post_type_args() {
		$args = array(
			'label' => __( 'Scheduled Actions', 'action-scheduler' ),
			'description' => __( 'Scheduled actions are hooks triggered on a cetain date and time.', 'action-scheduler' ),
			'public' => false,
			'map_meta_cap' => true,
			'hierarchical' => false,
			'supports' => array('title', 'editor','comments'),
			'rewrite' => false,
			'query_var' => false,
			'can_export' => true,
			'ep_mask' => EP_NONE,
			'labels' => array(
				'name' => __( 'Scheduled Actions', 'action-scheduler' ),
				'singular_name' => __( 'Scheduled Action', 'action-scheduler' ),
				'menu_name' => _x( 'Scheduled Actions', 'Admin menu name', 'action-scheduler' ),
				'add_new' => __( 'Add', 'action-scheduler' ),
				'add_new_item' => __( 'Add New Scheduled Action', 'action-scheduler' ),
				'edit' => __( 'Edit', 'action-scheduler' ),
				'edit_item' => __( 'Edit Scheduled Action', 'action-scheduler' ),
				'new_item' => __( 'New Scheduled Action', 'action-scheduler' ),
				'view' => __( 'View Action', 'action-scheduler' ),
				'view_item' => __( 'View Action', 'action-scheduler' ),
				'search_items' => __( 'Search Scheduled Actions', 'action-scheduler' ),
				'not_found' => __( 'No actions found', 'action-scheduler' ),
				'not_found_in_trash' => __( 'No actions found in trash', 'action-scheduler' ),
			),
		);

		$args = apply_filters('action_scheduler_post_type_args', $args);
		return $args;
	}
}
 