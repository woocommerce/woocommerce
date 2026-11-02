/**
 * External dependencies
 */
import React, { useEffect, useRef } from 'react';
import { _n, __, sprintf } from '@wordpress/i18n';
import { Button, Notice, ProgressBar } from '@wordpress/components';
import { Card, Stack, Text } from '@wordpress/ui';

/**
 * Internal dependencies
 */
import ImporterCounters from '../importer-counters';
import { useChunkedImport } from '../../hooks/use-chunked-import';
import type { StepComponentProps } from './types';

const ImportStep: React.FC< StepComponentProps > = ( { state, dispatch } ) => {
	const { run, isRunning } = useChunkedImport( {
		token: state.token,
		total: state.total,
		mapping: state.mapping,
		notifyCustomer: state.notifyCustomer,
		updateExisting: state.updateExisting,
		onChunk: ( response ) => {
			dispatch( { type: 'CHUNK_OK', payload: response } );
		},
		onFinish: ( summary ) => {
			dispatch( { type: 'FINISH', summary } );
		},
		onError: ( message, sessionEnded ) => {
			dispatch( { type: 'ERROR', message, sessionEnded } );
		},
	} );

	// Start the loop once when the step mounts. Restarting it on identity
	// changes could rerun an import that stopped with an error, so the latest
	// run is kept in a ref instead.
	const runRef = useRef( run );
	runRef.current = run;
	useEffect( () => {
		void runRef.current();
	}, [] );

	const percent =
		state.total > 0
			? Math.min(
					100,
					Math.round( ( state.processed / state.total ) * 100 )
				)
			: 0;

	// Processed, not imported: the count includes failed and skipped rows.
	const statusLabel = sprintf(
		/* translators: 1: processed rows, 2: total rows. */
		_n(
			'Processed %1$s of %2$s row',
			'Processed %1$s of %2$s rows',
			state.total,
			'woocommerce'
		),
		state.processed.toLocaleString(),
		state.total.toLocaleString()
	);

	return (
		<div
			className="woocommerce-fulfillment-importer-step woocommerce-fulfillment-importer-step--import"
			aria-busy={ isRunning }
		>
			<Card.Root className="woocommerce-fulfillment-importer-step__card">
				<Card.Content>
					<Stack
						direction="column"
						gap="lg"
						className="woocommerce-fulfillment-importer-step__card-body"
					>
						<Text variant="heading-md" render={ <h2 /> }>
							{ __( 'Importing fulfillments', 'woocommerce' ) }
						</Text>

						<ProgressBar
							className="woocommerce-fulfillment-importer-progress"
							value={ percent }
							aria-label={ __(
								'Import progress',
								'woocommerce'
							) }
						/>

						<Text
							render={ <p /> }
							role="status"
							aria-live="polite"
							className="woocommerce-fulfillment-importer-step__status"
						>
							{ statusLabel }
						</Text>

						<ImporterCounters counts={ state.counts } />

						{ state.error ? (
							<Notice status="error" isDismissible={ false }>
								<p>{ state.error }</p>
								<Stack direction="row" gap="sm">
									{ state.sessionEnded ? null : (
										<Button
											variant="secondary"
											onClick={ () => {
												dispatch( {
													type: 'CLEAR_ERROR',
												} );
												void run();
											} }
											isBusy={ isRunning }
											disabled={ isRunning }
										>
											{ __( 'Retry', 'woocommerce' ) }
										</Button>
									) }
									{ /* Also offered next to Retry, for an error that repeats on every retry. */ }
									<Button
										variant={
											state.sessionEnded
												? 'secondary'
												: 'tertiary'
										}
										onClick={ () =>
											dispatch( { type: 'RESET' } )
										}
										disabled={ isRunning }
									>
										{ __( 'Start over', 'woocommerce' ) }
									</Button>
								</Stack>
							</Notice>
						) : null }
					</Stack>
				</Card.Content>
			</Card.Root>
		</div>
	);
};

export default ImportStep;
