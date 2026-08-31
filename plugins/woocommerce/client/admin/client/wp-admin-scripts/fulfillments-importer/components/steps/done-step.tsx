/**
 * External dependencies
 */
import React, { useCallback } from 'react';
import { __ } from '@wordpress/i18n';
import { Button, Card, CardBody, Notice } from '@wordpress/components';

/**
 * Internal dependencies
 */
import ImporterSummaryPanel from '../importer-summary';
import { prepare } from '../../data/api';
import { errorMessage } from '../../hooks/use-chunked-import';
import { buildFailedRowsCsv, downloadCsv } from '../../utils/csv';
import type { StepComponentProps } from './types';

function failedRowsFilename( original: string | undefined ): string {
	const base = ( original || 'import' ).replace( /\.[^.]+$/, '' );
	return `${ base }-failed-rows.csv`;
}

const DoneStep: React.FC< StepComponentProps > = ( {
	state,
	dispatch,
	onClose,
} ) => {
	const summary = state.summary;
	const failed = summary?.failed ?? 0;
	const imported = ( summary?.created ?? 0 ) + ( summary?.updated ?? 0 );
	const failedRows = summary?.rows.filter(
		( row ) => row.status === 'failed'
	);

	// Most failures are problems with the file the warehouse sent, so the
	// export is the failed rows in their original columns plus a reason
	// column, ready to send straight back.
	const onDownloadFailedRows = useCallback( async () => {
		if ( ! state.file || ! failedRows?.length ) {
			return;
		}
		try {
			// Prefer the copy read at upload time; the File handle fails if
			// the on-disk file changed since it was chosen.
			const text = state.fileText ?? ( await state.file.text() );
			downloadCsv(
				failedRowsFilename( state.file.name ),
				buildFailedRowsCsv( text, state.delimiter, failedRows )
			);
		} catch ( error ) {
			dispatch( { type: 'ERROR', message: errorMessage( error ) } );
		}
	}, [ state.file, state.fileText, state.delimiter, failedRows, dispatch ] );

	// The server deletes the session when a run finishes, so returning to
	// mapping re-stages the kept file for a fresh token; the reducer keeps
	// the merchant's mapping when the headers are unchanged.
	const onBackToMapping = useCallback( async () => {
		if ( ! state.file ) {
			return;
		}
		dispatch( { type: 'SET_BUSY', value: true } );
		try {
			const response = await prepare( {
				file: state.file,
				delimiter: state.delimiter,
				notifyCustomer: state.notifyCustomer,
				updateExisting: state.updateExisting,
			} );
			dispatch( { type: 'PREPARE_OK', payload: response } );
		} catch ( error ) {
			dispatch( { type: 'ERROR', message: errorMessage( error ) } );
		}
	}, [
		state.file,
		state.delimiter,
		state.notifyCustomer,
		state.updateExisting,
		dispatch,
	] );

	const allOrderNotFound =
		failed > 0 &&
		Boolean(
			failedRows?.every( ( row ) => row.code === 'order_not_found' )
		);

	let footerButtons: React.ReactNode;
	if ( failed === 0 ) {
		footerButtons = (
			<>
				<Button
					variant="secondary"
					onClick={ () => dispatch( { type: 'RESET' } ) }
				>
					{ __( 'Import another file', 'woocommerce' ) }
				</Button>
				<Button variant="primary" onClick={ onClose }>
					{ __( 'Done', 'woocommerce' ) }
				</Button>
			</>
		);
	} else if ( imported > 0 ) {
		footerButtons = (
			<>
				<Button
					variant="secondary"
					onClick={ () => dispatch( { type: 'RESET' } ) }
				>
					{ __( 'Import another file', 'woocommerce' ) }
				</Button>
				<Button variant="primary" onClick={ onDownloadFailedRows }>
					{ __( 'Download failed rows', 'woocommerce' ) }
				</Button>
			</>
		);
	} else {
		// Nothing imported is usually a mapping mistake rather than bad
		// data, so going back to mapping is the most useful action.
		footerButtons = (
			<>
				<Button variant="tertiary" onClick={ onClose }>
					{ __( 'Done', 'woocommerce' ) }
				</Button>
				<Button variant="secondary" onClick={ onDownloadFailedRows }>
					{ __( 'Download failed rows', 'woocommerce' ) }
				</Button>
				<Button
					variant="primary"
					onClick={ onBackToMapping }
					isBusy={ state.isBusy }
					disabled={ state.isBusy || ! state.file }
				>
					{ __( 'Back to mapping', 'woocommerce' ) }
				</Button>
			</>
		);
	}

	return (
		<div className="woocommerce-fulfillment-importer-step woocommerce-fulfillment-importer-step--done">
			<Card className="woocommerce-fulfillment-importer-step__card">
				<CardBody>
					<h2>{ __( 'Import complete', 'woocommerce' ) }</h2>

					{ state.error ? (
						<Notice
							status="error"
							isDismissible
							onRemove={ () =>
								dispatch( { type: 'CLEAR_ERROR' } )
							}
						>
							{ state.error }
						</Notice>
					) : null }

					{ failed > 0 && imported === 0 ? (
						<Notice status="error" isDismissible={ false }>
							{ allOrderNotFound
								? __(
										'No rows were imported. Every row failed to find its order, which usually means a column is mapped to the wrong field.',
										'woocommerce'
								  )
								: __(
										'No rows were imported. Check the messages below, then fix the file or the mapping and try again.',
										'woocommerce'
								  ) }
						</Notice>
					) : null }

					{ summary ? (
						<ImporterSummaryPanel summary={ summary } />
					) : null }
				</CardBody>
			</Card>
			<footer className="woocommerce-fulfillment-importer-step__footer">
				{ footerButtons }
			</footer>
		</div>
	);
};

export default DoneStep;
