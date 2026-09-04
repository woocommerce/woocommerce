/**
 * External dependencies
 */
import React, { useEffect, useRef } from 'react';
import { __, sprintf } from '@wordpress/i18n';
import {
	Button,
	Card,
	CardBody,
	Notice,
	ProgressBar,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import ImporterCounters from '../importer-counters';
import { useChunkedImport } from '../../hooks/use-chunked-import';
import type { StepComponentProps } from './types';

const ImportStep: React.FC< StepComponentProps > = ( { state, dispatch } ) => {
	const { run, retry, isRunning } = useChunkedImport( {
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

	// Kick off the loop exactly once when the step mounts. Re-running on
	// identity changes (e.g. mapping edits in another step's reducer) could
	// silently restart an import that had already errored and reset its
	// running guard, so the latest run is captured in a ref instead.
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
		__( 'Processed %1$d of %2$d rows', 'woocommerce' ),
		state.processed,
		state.total
	);

	return (
		<div
			className="woocommerce-fulfillment-importer-step woocommerce-fulfillment-importer-step--import"
			aria-busy={ isRunning }
		>
			<Card className="woocommerce-fulfillment-importer-step__card">
				<CardBody>
					<h2>{ __( 'Importing fulfillments', 'woocommerce' ) }</h2>

					<ProgressBar
						className="woocommerce-fulfillment-importer-progress"
						value={ percent }
						aria-label={ __( 'Import progress', 'woocommerce' ) }
					/>

					<p role="status" aria-live="polite">
						{ statusLabel }
					</p>

					<ImporterCounters counts={ state.counts } />

					{ state.error ? (
						<Notice status="error" isDismissible={ false }>
							<p>{ state.error }</p>
							{ state.sessionEnded ? (
								// The session is gone server-side, so retrying the chunk
								// would fail the same way. Send the user back to upload.
								<Button
									variant="secondary"
									onClick={ () =>
										dispatch( { type: 'RESET' } )
									}
								>
									{ __( 'Start over', 'woocommerce' ) }
								</Button>
							) : (
								<Button
									variant="secondary"
									onClick={ () => {
										dispatch( { type: 'CLEAR_ERROR' } );
										retry();
									} }
									isBusy={ isRunning }
									disabled={ isRunning }
								>
									{ __( 'Retry', 'woocommerce' ) }
								</Button>
							) }
						</Notice>
					) : null }
				</CardBody>
			</Card>
		</div>
	);
};

export default ImportStep;
