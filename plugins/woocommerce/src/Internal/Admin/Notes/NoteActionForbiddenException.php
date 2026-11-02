<?php
/**
 * Note Action Forbidden Exception.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Notes;

defined( 'ABSPATH' ) || exit;

/**
 * Thrown by internal WooCommerce handlers on the `woocommerce_note_action[_*]`
 * hooks to signal that the current user lacks the per-action capability the
 * handler enforces.
 *
 * `Automattic\WooCommerce\Admin\API\NoteActions::trigger_note_action()` catches
 * this exception and converts it to a 403 REST response. Any other exception
 * type bubbles uncaught so genuine server faults are not masked as auth errors.
 *
 * Hook abort behavior: when a handler throws, `WP_Hook::do_action()` does not
 * catch the exception, so any lower-priority callbacks registered on the same
 * `woocommerce_note_action_<name>` hook are silently skipped, and
 * `Notes::trigger_note_action()` does not run its post-hook
 * `$note->set_status()`/`$note->save()` step — keeping the note actionable in
 * the inbox.
 *
 * This is an internal contract used by first-party note-action handlers in the
 * `Internal\\Admin\\Notes\\` namespace. Third-party extensions hooking into
 * `woocommerce_note_action[_*]` should not rely on this class — its API and
 * existence may change.
 *
 * @internal
 * @since 10.9.0
 */
class NoteActionForbiddenException extends \Exception {}
