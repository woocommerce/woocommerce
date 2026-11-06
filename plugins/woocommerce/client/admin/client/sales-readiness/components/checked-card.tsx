/**
 * External dependencies
 */
import { Card, Text } from '@wordpress/ui';
import { Button } from '@wordpress/components';

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
 * When the store was last checked, with Check again. Before the first check it says what a
 * check shows and offers Run check.
 */
export const CheckedCard = ( { view, running, onRun }: CheckedCardProps ) => (
	<Card.Root className="wc-sales-readiness-card">
		<Card.Content>
			{ view.intro && (
				<Text
					variant="body-md"
					className="wc-sales-readiness-checked__intro"
				>
					{ view.intro }
				</Text>
			) }
			<div className="wc-sales-readiness-checked">
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
				<Button
					variant="secondary"
					disabled={ view.run.pending || running }
					isBusy={ running }
					onClick={ onRun }
				>
					{ view.run.label }
				</Button>
			</div>
			<RunStatus run={ view.run } />
		</Card.Content>
	</Card.Root>
);
