/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { createInterpolateElement, useState } from '@wordpress/element';
/* eslint-disable @wordpress/use-recommended-components -- The page uses these @wordpress/ui components; this chunk bundles the package. */
import { Link, Notice, Text } from '@wordpress/ui';
/* eslint-enable @wordpress/use-recommended-components */

/**
 * Internal dependencies
 */
import { safeHref } from '../safe-href';
import type { DeclaredExperiments } from '../types';
import { ErrorNotice } from './error-notice';

export const EXPERIMENTS_PATH =
	'/wc/v3/agent-readiness/ai-assistant-experiments';

export type ExperimentsRun = {
	/** Runs the same check as Check again. */
	onRun: () => void;
	/** Whether the check is starting or running. */
	busy: boolean;
};

/**
 * The AI assistant experiments notice at the top of Extra details for AI assistants: the offer to
 * turn them on, then a prompt to run the check once they're on. ExperimentsOn takes over after that.
 */
export const ExperimentsNotice = ( {
	experiments,
	turnedOn,
	onTurnedOn,
	run,
}: {
	experiments: DeclaredExperiments;
	/** True after the offer's button succeeded, until the next check finishes. */
	turnedOn: boolean;
	onTurnedOn: () => void;
	run?: ExperimentsRun;
} ) => {
	const [ saving, setSaving ] = useState( false );
	const [ failed, setFailed ] = useState( false );

	if ( turnedOn ) {
		return (
			<Notice.Root key="turned-on" intent="success">
				<Notice.Description>
					{ experiments.turned_on.text }
				</Notice.Description>
				{ run && (
					<Notice.Actions>
						<Notice.ActionButton
							variant="outline"
							className="wc-ai-shopping__notice-action wc-sales-readiness-ui-button"
							disabled={ run.busy }
							onClick={ run.onRun }
						>
							{ experiments.turned_on.button }
						</Notice.ActionButton>
					</Notice.Actions>
				) }
			</Notice.Root>
		);
	}

	if ( experiments.enabled ) {
		return null;
	}

	if ( ! experiments.offer ) {
		return null;
	}

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
			onTurnedOn();
		} catch {
			setFailed( true );
		} finally {
			setSaving( false );
		}
	};

	const offerHref = safeHref( experiments.offer.href );

	return (
		<>
			{ failed && (
				<ErrorNotice message={ experiments.error } reload={ false } />
			) }
			<Notice.Root key="offer" intent="info">
				<Notice.Title>{ experiments.offer.title }</Notice.Title>
				<Notice.Description>
					{ createInterpolateElement( experiments.offer.text, {
						link: offerHref ? (
							<Link href={ offerHref } />
						) : (
							<span />
						),
					} ) }
				</Notice.Description>
				<Notice.Actions>
					<Notice.ActionButton
						variant="outline"
						className="wc-ai-shopping__notice-action wc-sales-readiness-ui-button"
						disabled={ saving }
						onClick={ () => void turnOn() }
					>
						{ experiments.offer.button }
					</Notice.ActionButton>
				</Notice.Actions>
			</Notice.Root>
		</>
	);
};

/**
 * The line under the card's description once AI assistant experiments are on, linking to Features
 * to turn them off.
 */
export const ExperimentsOn = ( {
	experiments,
}: {
	experiments: DeclaredExperiments;
} ) => {
	const href = safeHref( experiments.on.href );
	return (
		<Text
			variant="body-md"
			className="wc-sales-readiness__muted wc-sales-readiness-experiments-on"
		>
			{ createInterpolateElement( experiments.on.text, {
				link: href ? <Link href={ href } /> : <span />,
			} ) }
		</Text>
	);
};
