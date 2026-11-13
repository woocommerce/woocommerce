/**
 * External dependencies
 */
import { Text, VisuallyHidden } from '@wordpress/ui';

/**
 * Internal dependencies
 */
import type { ReadinessView } from '../types';

/**
 * The check's status under Run check: while it runs, a message for screen readers only (the
 * disabled button shows it's running), and the message itself when the check is stuck.
 */
export const RunStatus = ( { run }: { run: ReadinessView[ 'run' ] } ) => {
	if ( run.pending && ! run.stalled ) {
		return (
			<div role="status">
				<VisuallyHidden>{ run.message }</VisuallyHidden>
			</div>
		);
	}
	return run.message ? (
		<Text
			variant="body-sm"
			className="wc-sales-readiness__muted"
			role="status"
		>
			{ run.message }
		</Text>
	) : null;
};
