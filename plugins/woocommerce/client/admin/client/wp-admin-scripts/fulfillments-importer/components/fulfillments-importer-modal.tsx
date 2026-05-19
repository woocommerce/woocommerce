/**
 * External dependencies
 */
import React, { useCallback, useState } from 'react';
import { __ } from '@wordpress/i18n';
import {
	BaseControl,
	Button,
	CheckboxControl,
	Flex,
	FormFileUpload,
	Modal,
	Notice,
} from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import type { ImporterSummary } from '../data/types';
import ImporterSummaryPanel from './importer-summary';

const MAX_UPLOAD_BYTES = 10 * 1024 * 1024; // 10 MB.

interface Props {
	isOpen: boolean;
	onClose: () => void;
}

const FulfillmentsImporterModal: React.FC< Props > = ( {
	isOpen,
	onClose,
} ) => {
	const [ file, setFile ] = useState< File | null >( null );
	const [ notifyCustomer, setNotifyCustomer ] = useState( false );
	const [ updateExisting, setUpdateExisting ] = useState( true );
	const [ isImporting, setIsImporting ] = useState( false );
	const [ error, setError ] = useState< string | null >( null );
	const [ summary, setSummary ] = useState< ImporterSummary | null >( null );

	const reset = useCallback( () => {
		setFile( null );
		setNotifyCustomer( false );
		setUpdateExisting( true );
		setIsImporting( false );
		setError( null );
		setSummary( null );
	}, [] );

	const handleClose = useCallback( () => {
		if ( isImporting ) {
			return;
		}
		reset();
		onClose();
	}, [ isImporting, reset, onClose ] );

	const handleFileChange = useCallback(
		( e: React.ChangeEvent< HTMLInputElement > ) => {
			const picked = e.target.files?.[ 0 ] ?? null;
			if ( picked && picked.size > MAX_UPLOAD_BYTES ) {
				setFile( null );
				setError(
					__(
						'CSV file is too large. Choose a file smaller than 10 MB.',
						'woocommerce'
					)
				);
				return;
			}
			setFile( picked );
			setError( null );
		},
		[]
	);

	const handleClearFile = useCallback( () => {
		setFile( null );
		setError( null );
	}, [] );

	const handleSubmit = useCallback(
		async ( event: React.FormEvent ) => {
			event.preventDefault();
			if ( ! file ) {
				setError(
					__(
						'Choose a CSV file before starting the import.',
						'woocommerce'
					)
				);
				return;
			}

			setError( null );
			setSummary( null );
			setIsImporting( true );

			recordEvent( 'fulfillments_import_started', {
				notify_customer: notifyCustomer,
				update_existing: updateExisting,
			} );

			const formData = new FormData();
			formData.append( 'file', file );
			formData.append( 'notify_customer', notifyCustomer ? '1' : '0' );
			formData.append( 'update_existing', updateExisting ? '1' : '0' );

			try {
				const path =
					window.wcFulfillmentsImporterSettings?.importRoute ||
					'/wc/v3/fulfillments/import';
				const response = ( await apiFetch( {
					path,
					method: 'POST',
					body: formData,
				} ) ) as ImporterSummary;
				setSummary( response );
				recordEvent( 'fulfillments_import_completed', {
					created: response.created,
					updated: response.updated,
					skipped: response.skipped,
					failed: response.failed,
					notified: response.notified,
				} );
			} catch ( e ) {
				const message =
					( e as { message?: string } )?.message ||
					__( 'Import failed.', 'woocommerce' );
				setError( message );
				recordEvent( 'fulfillments_import_failed', {
					message,
				} );
			} finally {
				setIsImporting( false );
			}
		},
		[ file, notifyCustomer, updateExisting ]
	);

	if ( ! isOpen ) {
		return null;
	}

	return (
		<Modal
			title={ __( 'Import fulfillments from CSV', 'woocommerce' ) }
			onRequestClose={ handleClose }
			shouldCloseOnClickOutside={ ! isImporting }
			shouldCloseOnEsc={ ! isImporting }
			className="woocommerce-fulfillment-importer-modal"
		>
			{ summary ? (
				<div className="woocommerce-fulfillment-importer-modal__stack">
					<ImporterSummaryPanel summary={ summary } />
					<Flex justify="flex-end" gap={ 2 }>
						<Button variant="secondary" onClick={ reset }>
							{ __( 'Import another file', 'woocommerce' ) }
						</Button>
						<Button variant="primary" onClick={ handleClose }>
							{ __( 'Done', 'woocommerce' ) }
						</Button>
					</Flex>
				</div>
			) : (
				<form
					onSubmit={ handleSubmit }
					className="woocommerce-fulfillment-importer-modal__stack"
				>
					<p className="woocommerce-fulfillment-importer-modal__description">
						{ __(
							'Upload a CSV with fulfillment details for one or more orders. Each row creates or updates a fulfillment record.',
							'woocommerce'
						) }
					</p>

					<div className="woocommerce-fulfillment-importer-modal__columns">
						<p>
							<strong>
								{ __( 'Required columns', 'woocommerce' ) }
							</strong>{ ' ' }
							<code>order_number</code>{ ' ' }
							<code>tracking_number</code>{ ' ' }
							<code>shipment_provider</code>
						</p>
						<p>
							<strong>
								{ __( 'Optional columns', 'woocommerce' ) }
							</strong>{ ' ' }
							<code>tracking_url</code> <code>items</code>
						</p>
					</div>

					<BaseControl
						id="wc-fulfillment-importer-file"
						label={ __( 'CSV file', 'woocommerce' ) }
						help={
							file
								? file.name
								: __( 'No file selected.', 'woocommerce' )
						}
						__nextHasNoMarginBottom
					>
						<Flex
							justify="flex-start"
							gap={ 2 }
							wrap
							align="center"
						>
							<FormFileUpload
								accept=".csv,.txt,text/csv,text/plain"
								multiple={ false }
								disabled={ isImporting }
								onChange={ handleFileChange }
							>
								{ file
									? __(
											'Choose a different file',
											'woocommerce'
									  )
									: __( 'Choose CSV file', 'woocommerce' ) }
							</FormFileUpload>
							{ file && ! isImporting && (
								<Button
									variant="tertiary"
									isDestructive
									onClick={ handleClearFile }
									aria-label={ __(
										'Remove selected file',
										'woocommerce'
									) }
								>
									{ __( 'Remove', 'woocommerce' ) }
								</Button>
							) }
						</Flex>
					</BaseControl>

					<div className="woocommerce-fulfillment-importer-modal__options">
						<CheckboxControl
							__nextHasNoMarginBottom
							label={ __(
								'Send shipment notification emails for imported fulfillments.',
								'woocommerce'
							) }
							checked={ notifyCustomer }
							onChange={ setNotifyCustomer }
							disabled={ isImporting }
						/>
						<CheckboxControl
							__nextHasNoMarginBottom
							label={ __(
								'Update existing fulfillments when the tracking number already exists on the order.',
								'woocommerce'
							) }
							checked={ updateExisting }
							onChange={ setUpdateExisting }
							disabled={ isImporting }
						/>
					</div>

					{ error && (
						<Notice status="error" isDismissible={ false }>
							{ error }
						</Notice>
					) }

					<Flex justify="flex-end" gap={ 2 }>
						<Button
							variant="secondary"
							onClick={ handleClose }
							disabled={ isImporting }
						>
							{ __( 'Cancel', 'woocommerce' ) }
						</Button>
						<Button
							variant="primary"
							type="submit"
							isBusy={ isImporting }
							disabled={ isImporting || ! file }
						>
							{ isImporting
								? __( 'Importing…', 'woocommerce' )
								: __( 'Start import', 'woocommerce' ) }
						</Button>
					</Flex>
				</form>
			) }
		</Modal>
	);
};

export default FulfillmentsImporterModal;
