/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { Button } from '@wordpress/components';
import { createInterpolateElement, useState } from '@wordpress/element';

import { Badge, Card, Link, Text } from '@wordpress/ui';

/**
 * Internal dependencies
 */
import { safeHref } from '../safe-href';
import type { ExperimentsCardView } from '../types';
import { ErrorNotice } from './error-notice';

export const EXPERIMENTS_PATH =
	'/wc/v3/agent-readiness/ai-assistant-experiments';

/**
 * The AI tools Overview card for Features for AI assistants, laid out like a checklist row: the title
 * and description, with a button to turn them on beside them. Once they're on, the description says so
 * and links to the Features page, where they're turned off.
 */
export const ExperimentsCard = ( { card }: { card: ExperimentsCardView } ) => {
	const [ enabled, setEnabled ] = useState( card.enabled );
	const [ saving, setSaving ] = useState( false );
	const [ failed, setFailed ] = useState( false );
	const featuresHref = safeHref( card.on.href );

	const turnOn = async () => {
		setSaving( true );
		setFailed( false );
		try {
			const response = await apiFetch< { enabled: boolean } >( {
				path: EXPERIMENTS_PATH,
				method: 'POST',
			} );
			if ( ! response?.enabled ) {
				throw new Error( 'not enabled' );
			}
			setEnabled( true );
		} catch {
			setFailed( true );
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
							{ enabled
								? createInterpolateElement( card.on.text, {
										link: featuresHref ? (
											<Link href={ featuresHref } />
										) : (
											<span />
										),
									} )
								: card.text }
						</Text>
					</div>
					{ ! enabled && (
						<Button
							variant="secondary"
							size="compact"
							disabled={ saving }
							isBusy={ saving }
							onClick={ () => void turnOn() }
						>
							{ card.button }
						</Button>
					) }
				</div>
				{ failed && (
					<ErrorNotice message={ card.error } reload={ false } />
				) }
			</Card.Content>
		</Card.Root>
	);
};
