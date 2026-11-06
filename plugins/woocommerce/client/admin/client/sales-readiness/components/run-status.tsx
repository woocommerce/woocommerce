/**
 * External dependencies
 */
import { Text, VisuallyHidden } from '@wordpress/ui';
import { Spinner } from '@wordpress/components';

/**
 * Internal dependencies
 */
import type { ReadinessView } from '../types';

/**
 * The check's status under Run check: a spinner while it runs (its message is for screen
 * readers only), and the message itself when the check is stuck.
 */
export const RunStatus = ( { run }: { run: ReadinessView[ 'run' ] } ) => {
	if ( run.pending && ! run.stalled ) {
		return (
			<div className="wc-sales-readiness-checked__status" role="status">
				<Spinner />
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
