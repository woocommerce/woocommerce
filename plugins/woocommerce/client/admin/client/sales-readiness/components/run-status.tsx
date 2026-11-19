/**
 * External dependencies
 */
import { Text, VisuallyHidden } from '@wordpress/ui';

/**
 * Internal dependencies
 */
import type { ReadinessView } from '../types';
import { RowLink } from './check-row';

/**
 * The check's status under Run check: while it runs, a message for screen readers only (the
 * disabled button shows it's running), and the message itself when the check is stuck, with a
 * button to Scheduled Actions.
 */
export const RunStatus = ( { run }: { run: ReadinessView[ 'run' ] } ) => {
	if ( run.pending && ! run.stalled ) {
		return (
			<div role="status">
				<VisuallyHidden>{ run.message }</VisuallyHidden>
			</div>
		);
	}
	if ( ! run.message ) {
		return null;
	}
	return (
		<div className="wc-sales-readiness-run-status" role="status">
			<Text variant="body-sm" className="wc-sales-readiness__muted">
				{ run.message }
			</Text>
			{ run.link && (
				<div className="wc-sales-readiness-row__action">
					<RowLink link={ run.link } />
				</div>
			) }
		</div>
	);
};
