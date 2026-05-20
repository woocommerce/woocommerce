/**
 * External dependencies
 */
import React, { useCallback, useState } from 'react';
import { __, sprintf } from '@wordpress/i18n';
import {
	BaseControl,
	Button,
	CheckboxControl,
	Flex,
	FormFileUpload,
	Notice,
	TextControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { prepare } from '../../data/api';
import type { StepComponentProps } from './types';

function formatBytes( bytes: number ): string {
	if ( bytes < 1024 ) {
		return `${ bytes } B`;
	}
	const kb = bytes / 1024;
	if ( kb < 1024 ) {
		return `${ kb.toFixed( 1 ) } KB`;
	}
	return `${ ( kb / 1024 ).toFixed( 1 ) } MB`;
}

const UploadStep: React.FC< StepComponentProps > = ( {
	state,
	dispatch,
	onClose,
} ) => {
	const [ localError, setLocalError ] = useState< string | null >( null );

	const onFileChosen = useCallback(
		( event: React.ChangeEvent< HTMLInputElement > ) => {
			const next = event.target.files?.[ 0 ] ?? null;
			setLocalError( null );
			dispatch( { type: 'SET_FILE', file: next } );
		},
		[ dispatch ]
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
		try {
			const response = await prepare( {
				file: state.file,
				delimiter: state.delimiter,
				notifyCustomer: state.notifyCustomer,
				updateExisting: state.updateExisting,
			} );
			dispatch( { type: 'PREPARE_OK', payload: response } );
		} catch ( error ) {
			const message =
				error instanceof Error
					? error.message
					: __( 'Upload failed.', 'woocommerce' );
			dispatch( { type: 'ERROR', message } );
		}
	}, [
		state.file,
		state.delimiter,
		state.notifyCustomer,
		state.updateExisting,
		dispatch,
	] );

	const fileLabel = state.file
		? sprintf(
				/* translators: 1: file name, 2: human-readable file size. */
				__( '%1$s · %2$s', 'woocommerce' ),
				state.file.name,
				formatBytes( state.file.size )
		  )
		: __( 'No file selected.', 'woocommerce' );

	return (
		<div className="woocommerce-fulfillment-importer-step woocommerce-fulfillment-importer-step--upload">
			<h2>{ __( 'Upload a CSV file', 'woocommerce' ) }</h2>
			<p>
				{ __(
					'Choose a CSV exported from your warehouse or 3PL. We support the common header aliases — you can adjust the mapping in the next step.',
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
				id="wc-fulfillments-importer-file"
				label={ __( 'CSV file', 'woocommerce' ) }
			>
				<Flex justify="flex-start" align="center">
					<FormFileUpload
						accept=".csv,text/csv,text/plain"
						onChange={ onFileChosen }
						__next40pxDefaultSize
					>
						{ __( 'Choose CSV file', 'woocommerce' ) }
					</FormFileUpload>
					<span aria-live="polite">{ fileLabel }</span>
				</Flex>
			</BaseControl>

			<TextControl
				__next40pxDefaultSize
				label={ __( 'CSV delimiter', 'woocommerce' ) }
				help={ __(
					'Character used to separate columns in the CSV. Defaults to comma.',
					'woocommerce'
				) }
				value={ state.delimiter }
				placeholder=","
				onChange={ ( value: string ) =>
					dispatch( {
						type: 'SET_DELIMITER',
						delimiter: value,
					} )
				}
			/>

			<CheckboxControl
				label={ __(
					'Send shipment notification emails to customers.',
					'woocommerce'
				) }
				checked={ state.notifyCustomer }
				onChange={ ( value: boolean ) =>
					dispatch( { type: 'SET_NOTIFY', value } )
				}
			/>

			<CheckboxControl
				label={ __(
					'Update existing fulfillments when the tracking number matches.',
					'woocommerce'
				) }
				checked={ state.updateExisting }
				onChange={ ( value: boolean ) =>
					dispatch( { type: 'SET_UPDATE_EXISTING', value } )
				}
			/>

			<footer className="woocommerce-fulfillment-importer-step__footer">
				<Button variant="tertiary" onClick={ onClose }>
					{ __( 'Cancel', 'woocommerce' ) }
				</Button>
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
