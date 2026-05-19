<?php
/**
 * Note Action Forbidden Exception.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Notes;

defined( 'ABSPATH' ) || exit;

/**
 * Thrown by `woocommerce_note_action[_*]` hook handlers to signal that the
 * current user lacks the per-action capability the handler enforces.
 *
 * `Automattic\WooCommerce\Admin\API\NoteActions::trigger_note_action()` catches
 * this exception and converts it to a 403 REST response. Any other exception
 * type bubbles uncaught so genuine server faults are not masked as auth errors.
 *
 * @internal
 * @since 10.9.0
 */
class NoteActionForbiddenException extends \Exception {}
