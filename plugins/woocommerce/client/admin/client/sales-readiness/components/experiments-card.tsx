/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { Button } from '@wordpress/components';
import { useState } from '@wordpress/element';

import { Badge, Card, Text } from '@wordpress/ui';

/**
 * Internal dependencies
 */
import type { ExperimentsCardView } from '../types';
import { ErrorNotice } from './error-notice';

export const EXPERIMENTS_PATH =
	'/wc/v3/agent-readiness/ai-assistant-experiments';

/**
 * The AI tools Overview card for Features for AI assistants, laid out like a checklist row: the title
 * and description, with a button beside them that turns them on or off. The Features page has the
 * same setting.
 */
export const ExperimentsCard = ( { card }: { card: ExperimentsCardView } ) => {
	const [ enabled, setEnabled ] = useState( card.enabled );
	const [ saving, setSaving ] = useState( false );
	// The error for the change that failed, cleared on the next try.
	const [ error, setError ] = useState< string | null >( null );
	const state = enabled ? card.on : card.off;

	const toggle = async () => {
		const next = ! enabled;
		setSaving( true );
		setError( null );
		try {
			const response = await apiFetch< { enabled: boolean } >( {
				path: EXPERIMENTS_PATH,
				method: 'POST',
				data: { enabled: next },
			} );
			if ( response?.enabled !== next ) {
				throw new Error( 'not changed' );
			}
			setEnabled( next );
		} catch {
			setError( state.error );
		} finally {
			setSaving( false );
		}
	};

	return (
		<Card.Root className="wc-sales-readiness-card wc-sales-readiness-experiments">
			<Card.Content>
				<div className="wc-sales-readiness-experiments__row">
					<div className="wc-sales-readiness-experiments__body">
						<div className="wc-sales-readiness-experiments__title">
							<Card.Title className="wc-sales-readiness-card__title">
								{ card.title }
							</Card.Title>
							<Badge intent={ card.badge.intent }>
								{ card.badge.text }
							</Badge>
						</div>
						<Text
							variant="body-md"
							className="wc-sales-readiness__muted"
						>
							{ state.text }
						</Text>
					</div>
					<Button
						variant="secondary"
						size="compact"
						disabled={ saving }
						isBusy={ saving }
						onClick={ () => void toggle() }
					>
						{ state.button }
					</Button>
				</div>
				{ error && <ErrorNotice message={ error } reload={ false } /> }
			</Card.Content>
		</Card.Root>
	);
};
