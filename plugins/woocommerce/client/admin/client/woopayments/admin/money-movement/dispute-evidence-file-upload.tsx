/**
 * External dependencies
 */
import { speak } from '@wordpress/a11y';
import { Button, Spinner } from '@wordpress/components';
import { useEffect, useRef, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';
import type { ChangeEvent } from 'react';

/**
 * Internal dependencies
 */
import { uploadWooPaymentsDisputeFile } from './data';
import {
	EVIDENCE_FILE_ACCEPT_ATTRIBUTE,
	MAX_EVIDENCE_FILE_BYTES,
	type DocumentEvidenceField,
	getEvidenceFileName,
	isAcceptedEvidenceFile,
} from './dispute-evidence-fields';
import type { WooPaymentsDisputeFile } from './types';
import { getErrorMessage } from './utils';

type DisputeEvidenceFileUploadProps = {
	field: DocumentEvidenceField;
	label: string;
	file?: WooPaymentsDisputeFile;
	totalFileBytes: number;
	disabled?: boolean;
	disputeTracksProperties: Record< string, string | undefined >;
	onUploaded: (
		field: DocumentEvidenceField,
		file: WooPaymentsDisputeFile
	) => void;
	onRemove: ( field: DocumentEvidenceField ) => void;
	onError: ( message: string ) => void;
	onUploadStateChange?: (
		field: DocumentEvidenceField,
		isUploading: boolean
	) => void;
};

export const DisputeEvidenceFileUpload = ( {
	field,
	label,
	file,
	totalFileBytes,
	disabled = false,
	disputeTracksProperties,
	onUploaded,
	onRemove,
	onError,
	onUploadStateChange,
}: DisputeEvidenceFileUploadProps ) => {
	const [ isUploading, setIsUploading ] = useState( false );
	const rowRef = useRef< HTMLDivElement | null >( null );
	const inputRef = useRef< HTMLInputElement | null >( null );
	const inputId = `woocommerce-woopayments-dispute-evidence-${ field }`;
	const fileName = getEvidenceFileName( file );
	const currentFileSize = file?.size || 0;
	const labelAlreadyContainsUpload = label
		.toLowerCase()
		.startsWith( 'upload ' );
	const uploadLabel = labelAlreadyContainsUpload
		? label
		: sprintf(
				/* translators: %s: evidence file field label. */
				__( 'Upload %s', 'woocommerce' ),
				label.toLowerCase()
		  );
	const removeFieldLabel = labelAlreadyContainsUpload
		? label.replace( /^upload\s+/i, '' )
		: label;
	const removeLabel = sprintf(
		/* translators: %s: evidence file field label. */
		__( 'Remove %s', 'woocommerce' ),
		removeFieldLabel.toLowerCase()
	);
	const isControlDisabled = disabled || isUploading;

	useEffect( () => {
		return () => onUploadStateChange?.( field, false );
	}, [ field, onUploadStateChange ] );

	const setUploading = ( nextIsUploading: boolean ) => {
		setIsUploading( nextIsUploading );
		onUploadStateChange?.( field, nextIsUploading );
	};

	const handleFileChange = async (
		event: ChangeEvent< HTMLInputElement >
	) => {
		const selectedFile = event.target.files?.[ 0 ];
		event.target.value = '';

		if ( ! selectedFile ) {
			return;
		}

		if ( ! isAcceptedEvidenceFile( selectedFile ) ) {
			onError(
				__(
					'Upload a PDF, PNG, or JPEG file for dispute evidence.',
					'woocommerce'
				)
			);
			return;
		}

		const nextTotal = totalFileBytes - currentFileSize + selectedFile.size;

		if ( nextTotal > MAX_EVIDENCE_FILE_BYTES ) {
			onError(
				__(
					'The selected files exceed the 4.5 MB dispute evidence limit.',
					'woocommerce'
				)
			);
			return;
		}

		recordEvent( 'wcpay_dispute_file_upload_started', {
			...disputeTracksProperties,
			type: field,
		} );
		setUploading( true );

		const body = new FormData();
		body.append( 'file', selectedFile );
		body.append( 'purpose', 'dispute_evidence' );

		let uploadSucceeded = false;

		try {
			const uploadedFile = await uploadWooPaymentsDisputeFile( body );
			uploadSucceeded = true;
			onUploaded( field, uploadedFile );
			speak(
				sprintf(
					/* translators: %s: evidence file field label. */
					__( '%s uploaded.', 'woocommerce' ),
					label
				),
				'polite'
			);
			recordEvent( 'wcpay_dispute_file_upload_success', {
				...disputeTracksProperties,
				type: field,
			} );
		} catch ( error ) {
			const message = getErrorMessage(
				error,
				__( 'Unable to upload dispute evidence file.', 'woocommerce' )
			);
			onError( message );
			recordEvent( 'wcpay_dispute_file_upload_failed', {
				...disputeTracksProperties,
				message,
			} );
		} finally {
			setUploading( false );
			if ( uploadSucceeded ) {
				setTimeout( () => {
					const ownerDocument = rowRef.current?.ownerDocument;
					const activeElement = ownerDocument?.activeElement;
					const focusIsStillInUploadRow =
						activeElement instanceof HTMLElement &&
						!! rowRef.current?.contains( activeElement );

					if (
						activeElement === ownerDocument?.body ||
						focusIsStillInUploadRow
					) {
						inputRef.current?.focus();
					}
				}, 0 );
			}
		}
	};

	const handleRemove = () => {
		onRemove( field );
		speak(
			sprintf(
				/* translators: %s: evidence file field label. */
				__( '%s removed.', 'woocommerce' ),
				label
			),
			'polite'
		);
		setTimeout( () => inputRef.current?.focus(), 0 );
	};

	return (
		<div
			ref={ rowRef }
			className="woocommerce-woopayments-dispute-evidence-file"
		>
			<div className="woocommerce-woopayments-dispute-evidence-file__main">
				{ disabled ? (
					<span className="woocommerce-woopayments-dispute-evidence-file__label">
						{ uploadLabel }
					</span>
				) : (
					<label htmlFor={ inputId }>{ uploadLabel }</label>
				) }
				{ fileName ? (
					<span className="woocommerce-woopayments-dispute-evidence-file__name">
						{ fileName }
					</span>
				) : (
					<span className="woocommerce-woopayments-dispute-evidence-file__empty">
						{ __( 'No file selected', 'woocommerce' ) }
					</span>
				) }
			</div>
			{ disabled ? (
				<span className="woocommerce-woopayments-dispute-evidence-file__readonly">
					{ __( 'Read-only', 'woocommerce' ) }
				</span>
			) : (
				<div className="woocommerce-woopayments-dispute-evidence-file__controls">
					<input
						ref={ inputRef }
						id={ inputId }
						type="file"
						accept={ EVIDENCE_FILE_ACCEPT_ATTRIBUTE }
						disabled={ isControlDisabled }
						onChange={ handleFileChange }
					/>
					{ isUploading && <Spinner /> }
					{ fileName && (
						<Button
							variant="tertiary"
							type="button"
							accessibleWhenDisabled
							disabled={ isControlDisabled }
							onClick={ handleRemove }
						>
							{ removeLabel }
						</Button>
					) }
				</div>
			) }
		</div>
	);
};
