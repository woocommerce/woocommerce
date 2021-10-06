<?php
/**
 * Task and TaskList Traits
 */

namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks;

defined( 'ABSPATH' ) || exit;

/**
 * TaskTraits class.
 */
trait TaskTraits {
	/**
	 * Prefix event for backwards compatibility with tracks event naming.
	 *
	 * @param string $event_name Event name.
	 * @return string
	 */
	public function prefix_event( $event_name ) {
		$id = self::get_list_id();

		if ( 'setup' === $id ) {
			return 'tasklist_' . $event_name;
		}

		return $id . '_tasklist_' . $event_name;
	}

	/**
	 * Record a tracks event with the prefixed event name.
	 *
	 * @param string $event_name Event name.
	 * @param array  $args Array of tracks arguments.
	 */
	public function record_tracks_event( $event_name, $args = array() ) {
		if ( ! $this->get_list_id() ) {
			return;
		}

		wc_admin_record_tracks_event(
			$this->prefix_event( $event_name ),
			$args
		);
	}

	/**
	 * Get the task list ID.
	 *
	 * @return string
	 */
	public function get_list_id() {
		$namespaced_class = get_class( $this );
		$short_class      = substr( $namespaced_class, strrpos( $namespaced_class, '\\' ) + 1 );
		return 'Task' === $short_class ? $this->parent_id : $this->id;
	}
}
