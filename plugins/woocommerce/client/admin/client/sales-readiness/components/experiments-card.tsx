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
 * The AI tools Overview card for AI assistant experiments: a button to turn them on, then a line
 * linking to the Features page, where they're turned off.
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
			<Card.Header>
				<div className="wc-sales-readiness-card__head">
					<Card.Title className="wc-sales-readiness-card__title">
						{ card.title }
					</Card.Title>
					<Badge intent={ card.badge.intent }>
						{ card.badge.text }
					</Badge>
				</div>
			</Card.Header>
			<Card.Content>
				<Text variant="body-md" className="wc-sales-readiness__muted">
					{ card.text }
				</Text>
				{ failed && (
					<ErrorNotice message={ card.error } reload={ false } />
				) }
				{ enabled ? (
					<Text
						variant="body-md"
						className="wc-sales-readiness__muted wc-sales-readiness-experiments__on"
					>
						{ createInterpolateElement( card.on.text, {
							link: featuresHref ? (
								<Link href={ featuresHref } />
							) : (
								<span />
							),
						} ) }
					</Text>
				) : (
					<div className="wc-sales-readiness-card__actions">
						<Button
							variant="secondary"
							size="compact"
							disabled={ saving }
							isBusy={ saving }
							onClick={ () => void turnOn() }
						>
							{ card.button }
						</Button>
					</div>
				) }
			</Card.Content>
		</Card.Root>
	);
};
