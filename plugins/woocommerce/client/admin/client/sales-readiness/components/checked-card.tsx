/**
 * External dependencies
 */
import { Card, Text } from '@wordpress/ui';
import { Button, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import type { ReadinessView } from '../types';
import { RunStatus } from './run-status';

interface CheckedCardProps {
	view: ReadinessView;
	running: boolean;
	onRun: () => void;
}

/**
 * When the store was last checked, with Check again. Before the first check it says Not checked
 * yet and offers Check your store. While a check runs it shows a spinner and Checking your store,
 * with Check again turned off.
 */
export const CheckedCard = ( { view, running, onRun }: CheckedCardProps ) => {
	const checking = ( view.run.pending && ! view.run.stalled ) || running;
	return (
		<Card.Root className="wc-sales-readiness-card">
			<Card.Content>
				<div className="wc-sales-readiness-checked">
					{ checking ? (
						<div
							className="wc-sales-readiness-checked__running"
							role="status"
						>
							<Spinner />
							<Text variant="body-md">
								{ view.run.message ||
									__( 'Checking your store', 'woocommerce' ) }
							</Text>
						</div>
					) : (
						<div>
							<Text variant="body-md">{ view.last_checked }</Text>
							{ view.checked_note && (
								<Text
									variant="body-sm"
									className="wc-sales-readiness__muted"
								>
									{ view.checked_note }
								</Text>
							) }
						</div>
					) }
					<Button
						variant="secondary"
						size="compact"
						disabled={ view.run.pending || running }
						onClick={ onRun }
					>
						{ checking
							? __( 'Check again', 'woocommerce' )
							: view.run.label }
					</Button>
				</div>
				{ ! checking && <RunStatus run={ view.run } /> }
			</Card.Content>
		</Card.Root>
	);
};
