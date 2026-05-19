/**
 * External dependencies
 */
import React, { useCallback, useEffect, useRef, useState } from 'react';
import { __ } from '@wordpress/i18n';
import {
	Button,
	CheckboxControl,
	Modal,
	Notice,
	Spinner,
} from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import type { ImporterSummary } from '../data/types';
import ImporterSummaryPanel from './importer-summary';

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

	// Mirror form state in refs so handleSubmit can have a stable identity.
	const fileRef = useRef< File | null >( null );
	const notifyCustomerRef = useRef( notifyCustomer );
	const updateExistingRef = useRef( updateExisting );
	useEffect( () => {
		fileRef.current = file;
	}, [ file ] );
	useEffect( () => {
		notifyCustomerRef.current = notifyCustomer;
	}, [ notifyCustomer ] );
	useEffect( () => {
		updateExistingRef.current = updateExisting;
	}, [ updateExisting ] );

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
			setFile( picked );
			setError( null );
		},
		[]
	);

	const handleSubmit = useCallback( async ( event: React.FormEvent ) => {
		event.preventDefault();
		const currentFile = fileRef.current;
		const currentNotify = notifyCustomerRef.current;
		const currentUpdate = updateExistingRef.current;
		if ( ! currentFile ) {
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
			notify_customer: currentNotify,
			update_existing: currentUpdate,
		} );

		const formData = new FormData();
		formData.append( 'file', currentFile );
		formData.append( 'notify_customer', currentNotify ? '1' : '0' );
		formData.append( 'update_existing', currentUpdate ? '1' : '0' );

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
	}, [] );

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
				<>
					<ImporterSummaryPanel summary={ summary } />
					<div className="woocommerce-fulfillment-importer-modal__actions">
						<Button variant="secondary" onClick={ reset }>
							{ __( 'Import another file', 'woocommerce' ) }
						</Button>
						<Button variant="primary" onClick={ handleClose }>
							{ __( 'Done', 'woocommerce' ) }
						</Button>
					</div>
				</>
			) : (
				<form onSubmit={ handleSubmit }>
					<p className="woocommerce-fulfillment-importer-modal__description">
						{ __(
							'Upload a CSV with fulfillment details for one or more orders. Each row creates or updates a fulfillment record.',
							'woocommerce'
						) }
					</p>

					<p className="woocommerce-fulfillment-importer-modal__hint">
						<strong>
							{ __( 'Required columns:', 'woocommerce' ) }
						</strong>{ ' ' }
						<code>order_number</code>, <code>tracking_number</code>,{ ' ' }
						<code>shipment_provider</code>
						<br />
						<strong>
							{ __( 'Optional columns:', 'woocommerce' ) }
						</strong>{ ' ' }
						<code>tracking_url</code>, <code>items</code>
					</p>

					<div className="woocommerce-fulfillment-importer-modal__file-row">
						<label htmlFor="wc-fulfillments-importer-file">
							{ __( 'CSV file', 'woocommerce' ) }
						</label>
						<input
							id="wc-fulfillments-importer-file"
							type="file"
							accept=".csv,.txt,text/csv,text/plain"
							required
							disabled={ isImporting }
							onChange={ handleFileChange }
						/>
					</div>

					<CheckboxControl
						label={ __(
							'Send shipment notification emails for imported fulfillments.',
							'woocommerce'
						) }
						checked={ notifyCustomer }
						onChange={ setNotifyCustomer }
						disabled={ isImporting }
					/>

					<CheckboxControl
						label={ __(
							'Update existing fulfillments when the tracking number already exists on the order.',
							'woocommerce'
						) }
						checked={ updateExisting }
						onChange={ setUpdateExisting }
						disabled={ isImporting }
					/>

					{ error && (
						<Notice status="error" isDismissible={ false }>
							{ error }
						</Notice>
					) }

					<div className="woocommerce-fulfillment-importer-modal__actions">
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
							{ isImporting ? (
								<>
									<Spinner />
									{ __( 'Importing…', 'woocommerce' ) }
								</>
							) : (
								__( 'Start import', 'woocommerce' )
							) }
						</Button>
					</div>
				</form>
			) }
		</Modal>
	);
};

export default FulfillmentsImporterModal;
