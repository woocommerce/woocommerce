/**
 * External dependencies
 */
import React, { useCallback, useState } from 'react';
import { __, sprintf } from '@wordpress/i18n';
import {
	BaseControl,
	Button,
	Card,
	CardBody,
	CheckboxControl,
	DropZone,
	FormFileUpload,
	Icon,
	Notice,
	TextControl,
} from '@wordpress/components';
import { chevronDown, chevronUp } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { prepare } from '../../data/api';
import { errorMessage } from '../../hooks/use-chunked-import';
import { downloadCsv } from '../../utils/csv';
import type { StepComponentProps } from './types';

const FALLBACK_MAX_ROWS = 5000;

const SAMPLE_CSV = [
	'order_number,tracking_number,shipment_provider,tracking_url,items',
	'1001,1Z999AA10123456784,UPS,https://www.ups.com/track?tracknum=1Z999AA10123456784,',
	'1002,9400100000000000000000,USPS,,',
].join( '\n' );

function formatBytes( bytes: number ): string {
	if ( bytes < 1024 ) {
		/* translators: %s: file size in bytes. */
		return sprintf( __( '%s B', 'woocommerce' ), String( bytes ) );
	}
	const kb = bytes / 1024;
	if ( kb < 1024 ) {
		/* translators: %s: file size in kilobytes. */
		return sprintf( __( '%s KB', 'woocommerce' ), kb.toFixed( 1 ) );
	}
	/* translators: %s: file size in megabytes. */
	return sprintf( __( '%s MB', 'woocommerce' ), ( kb / 1024 ).toFixed( 1 ) );
}

function downloadSampleCsv(): void {
	downloadCsv( 'fulfillments-sample.csv', SAMPLE_CSV );
}

/**
 * The server validates thoroughly; this only keeps obvious non-CSV drops
 * (images, PDFs) from being staged.
 */
export function isCsvLikeFile( file: File ): boolean {
	return (
		/\.(csv|txt)$/i.test( file.name ) ||
		[ 'text/csv', 'text/plain', 'application/csv' ].includes( file.type )
	);
}

const UploadStep: React.FC< StepComponentProps > = ( { state, dispatch } ) => {
	const [ localError, setLocalError ] = useState< string | null >( null );
	const [ showAdvanced, setShowAdvanced ] = useState( false );

	// wp_localize_script casts scalars to strings, so coerce before formatting.
	const maxRows =
		Number( window.wcFulfillmentsImporterSettings?.maxRows ) ||
		FALLBACK_MAX_ROWS;

	const setFile = useCallback(
		( next: File | null ) => {
			setLocalError( null );
			dispatch( { type: 'SET_FILE', file: next } );
		},
		[ dispatch ]
	);

	const onFileChosen = useCallback(
		( event: React.ChangeEvent< HTMLInputElement > ) => {
			setFile( event.target.files?.[ 0 ] ?? null );
		},
		[ setFile ]
	);

	const onFilesDrop = useCallback(
		( files: File[] ) => {
			const next = files[ 0 ] ?? null;
			if ( ! next ) {
				return;
			}
			if ( ! isCsvLikeFile( next ) ) {
				setLocalError( __( 'Please drop a CSV file.', 'woocommerce' ) );
				return;
			}
			setFile( next );
		},
		[ setFile ]
	);

	const onContinue = useCallback( async () => {
		if ( ! state.file ) {
			setLocalError(
				__( 'Please choose a CSV file to upload.', 'woocommerce' )
			);
			return;
		}
		setLocalError( null );
		dispatch( { type: 'SET_BUSY', value: true } );
		// Keep a copy of the content: the File handle references the on-disk
		// file, so a later read fails if it was moved or edited, and the
		// summary's failed-rows export needs the bytes that were uploaded.
		try {
			dispatch( {
				type: 'SET_FILE_TEXT',
				text: await state.file.text(),
			} );
		} catch ( error ) {
			// Breadcrumb for a later export failure.
			window.console?.warn?.(
				'Fulfillments importer: could not cache the file content.',
				error
			);
			dispatch( { type: 'SET_FILE_TEXT', text: null } );
		}
		try {
			const response = await prepare( {
				file: state.file,
				delimiter: state.delimiter,
				notifyCustomer: state.notifyCustomer,
				updateExisting: state.updateExisting,
			} );
			dispatch( { type: 'PREPARE_OK', payload: response } );
		} catch ( error ) {
			// apiFetch rejects with a plain object, so extract the server's
			// actionable message instead of collapsing to a generic one.
			dispatch( { type: 'ERROR', message: errorMessage( error ) } );
		}
	}, [
		state.file,
		state.delimiter,
		state.notifyCustomer,
		state.updateExisting,
		dispatch,
	] );

	const fileLabel = state.file
		? `${ state.file.name } · ${ formatBytes( state.file.size ) }`
		: __( 'No file selected.', 'woocommerce' );

	return (
		<div className="woocommerce-fulfillment-importer-step woocommerce-fulfillment-importer-step--upload">
			<Card className="woocommerce-fulfillment-importer-step__card">
				<CardBody>
					<h2>{ __( 'Upload a CSV file', 'woocommerce' ) }</h2>
					<p>
						{ __(
							'Choose a CSV exported from your warehouse or 3PL. We support the common header aliases, and you can adjust the mapping in the next step.',
							'woocommerce'
						) }
					</p>

					{ localError || state.error ? (
						<Notice
							status="error"
							isDismissible
							onRemove={ () => {
								setLocalError( null );
								dispatch( { type: 'CLEAR_ERROR' } );
							} }
						>
							{ localError || state.error }
						</Notice>
					) : null }

					<BaseControl
						__nextHasNoMarginBottom
						id="wc-fulfillments-importer-file"
						label={ __( 'CSV file', 'woocommerce' ) }
					>
						<div className="woocommerce-fulfillment-importer-dropzone">
							<DropZone
								label={ __(
									'Drop your CSV file here',
									'woocommerce'
								) }
								onFilesDrop={ onFilesDrop }
							/>
							<span className="woocommerce-fulfillment-importer-dropzone__hint">
								{ __( 'Drag a CSV file here', 'woocommerce' ) }
							</span>
							<FormFileUpload
								accept=".csv,text/csv,text/plain"
								onChange={ onFileChosen }
								render={ ( { openFileDialog } ) => (
									<Button
										variant="secondary"
										onClick={ openFileDialog }
									>
										{ __(
											'Choose CSV file',
											'woocommerce'
										) }
									</Button>
								) }
							/>
						</div>
					</BaseControl>

					<p
						className="woocommerce-fulfillment-importer-file-label"
						aria-live="polite"
					>
						{ fileLabel }
					</p>

					<p className="woocommerce-fulfillment-importer-file-requirements">
						{ __(
							'Required columns: order number, tracking number, carrier. Optional: tracking URL, items.',
							'woocommerce'
						) }
					</p>

					<div className="woocommerce-fulfillment-importer-file-meta">
						<Button variant="link" onClick={ downloadSampleCsv }>
							{ __(
								'Download a sample CSV file',
								'woocommerce'
							) }
						</Button>
						<span>
							{ sprintf(
								/* translators: %s: maximum number of rows per file. */
								__( 'Up to %s rows per file', 'woocommerce' ),
								maxRows.toLocaleString()
							) }
						</span>
					</div>

					<CheckboxControl
						__nextHasNoMarginBottom
						label={ __(
							'Update existing fulfillments when the tracking number matches.',
							'woocommerce'
						) }
						checked={ state.updateExisting }
						onChange={ ( value: boolean ) =>
							dispatch( { type: 'SET_UPDATE_EXISTING', value } )
						}
					/>
				</CardBody>
			</Card>

			<Card className="woocommerce-fulfillment-importer-step__card">
				<CardBody>
					{ /* A heading inside a button loses its role, not the reverse. */ }
					<h2 className="woocommerce-fulfillment-importer-advanced-toggle">
						<button
							type="button"
							aria-expanded={ showAdvanced }
							aria-controls="wc-fulfillments-importer-advanced"
							onClick={ () => setShowAdvanced( ( v ) => ! v ) }
						>
							{ __( 'Advanced options', 'woocommerce' ) }
							<Icon
								icon={ showAdvanced ? chevronUp : chevronDown }
							/>
						</button>
					</h2>
					<div
						id="wc-fulfillments-importer-advanced"
						hidden={ ! showAdvanced }
					>
						<TextControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'CSV delimiter', 'woocommerce' ) }
							help={ __(
								'Single character used to separate columns in the CSV. Defaults to comma.',
								'woocommerce'
							) }
							value={ state.delimiter }
							placeholder=","
							maxLength={ 1 }
							onChange={ ( value: string ) =>
								dispatch( {
									type: 'SET_DELIMITER',
									delimiter: value.slice( 0, 1 ),
								} )
							}
						/>
					</div>
				</CardBody>
			</Card>

			<footer className="woocommerce-fulfillment-importer-step__footer">
				<Button
					variant="primary"
					onClick={ onContinue }
					isBusy={ state.isBusy }
					disabled={ ! state.file || state.isBusy }
				>
					{ __( 'Continue', 'woocommerce' ) }
				</Button>
			</footer>
		</div>
	);
};

export default UploadStep;
