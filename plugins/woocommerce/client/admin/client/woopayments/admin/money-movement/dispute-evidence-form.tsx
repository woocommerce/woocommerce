/**
 * External dependencies
 */
import { speak } from '@wordpress/a11y';
import {
	Button,
	SelectControl,
	TextControl,
	TextareaControl,
} from '@wordpress/components';
import {
	useCallback,
	useEffect,
	useMemo,
	useRef,
	useState,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { updateWooPaymentsDispute } from './data';
import { DisputeEvidenceFileUpload } from './dispute-evidence-file-upload';
import {
	DOCUMENT_EVIDENCE_FIELDS,
	OPTIONAL_TEXT_EVIDENCE_FIELDS,
	PRODUCT_TYPE_METADATA_KEY,
	SHIPPING_EVIDENCE_FIELDS,
	type DocumentEvidenceField,
	type EvidenceFileMap,
	type EvidenceField,
	buildEvidencePayload,
	getEvidenceFileByteTotal,
	isDisputeActionable,
} from './dispute-evidence-fields';
import type { WooPaymentsDispute, WooPaymentsDisputeFile } from './types';
import {
	formatAmount,
	formatDate,
	formatLabel,
	getDisputeId,
	getErrorMessage,
} from './utils';
import './dispute-evidence.scss';

type DisputeEvidenceFormProps = {
	dispute: WooPaymentsDispute;
	fileDetails?: EvidenceFileMap;
	onDisputeUpdated?: ( dispute: WooPaymentsDispute ) => void;
};

type NoticeState = {
	type: 'success' | 'error';
	message: string;
} | null;

const PRODUCT_TYPE_OPTIONS = [
	{
		label: __( 'Physical product', 'woocommerce' ),
		value: 'physical_product',
	},
	{
		label: __( 'Digital product or service', 'woocommerce' ),
		value: 'digital_product_or_service',
	},
	{
		label: __( 'Offline service', 'woocommerce' ),
		value: 'offline_service',
	},
	{
		label: __( 'Event', 'woocommerce' ),
		value: 'event',
	},
	{
		label: __( 'Booking or reservation', 'woocommerce' ),
		value: 'booking_reservation',
	},
	{
		label: __( 'Subscription', 'woocommerce' ),
		value: 'subscription',
	},
	{
		label: __( 'Multiple products', 'woocommerce' ),
		value: 'multiple',
	},
	{
		label: __( 'Other', 'woocommerce' ),
		value: 'other',
	},
];

const getStringEvidenceValue = (
	dispute: WooPaymentsDispute,
	field: EvidenceField
) => {
	const value = dispute.evidence?.[ field ];

	return typeof value === 'string' ? value : '';
};

const getInitialEvidenceState = ( dispute: WooPaymentsDispute ) => {
	const evidence = {} as Record< EvidenceField, string >;

	[
		...DOCUMENT_EVIDENCE_FIELDS,
		...SHIPPING_EVIDENCE_FIELDS,
		...OPTIONAL_TEXT_EVIDENCE_FIELDS,
	].forEach( ( field ) => {
		evidence[ field ] = getStringEvidenceValue( dispute, field );
	} );

	return evidence;
};

const getInitialProductType = ( dispute: WooPaymentsDispute ) =>
	dispute.metadata?.[ PRODUCT_TYPE_METADATA_KEY ] ||
	dispute.order?.suggested_product_type ||
	'physical_product';

const getDocumentEvidenceFieldLabel = ( field: DocumentEvidenceField ) => {
	switch ( field ) {
		case 'receipt':
			return __( 'Receipt', 'woocommerce' );
		case 'customer_communication':
			return __( 'Customer communication', 'woocommerce' );
		case 'customer_signature':
			return __( 'Customer signature', 'woocommerce' );
		case 'refund_policy':
			return __( 'Refund policy', 'woocommerce' );
		case 'duplicate_charge_documentation':
			return __( 'Duplicate charge documentation', 'woocommerce' );
		case 'cancellation_policy':
			return __( 'Cancellation policy', 'woocommerce' );
		case 'cancellation_rebuttal':
			return __( 'Cancellation rebuttal', 'woocommerce' );
		case 'access_activity_log':
			return __( 'Access activity log', 'woocommerce' );
		case 'service_documentation':
			return __( 'Service documentation', 'woocommerce' );
		case 'shipping_documentation':
			return __( 'Shipping documentation', 'woocommerce' );
		case 'uncategorized_file':
			return __( 'Additional file', 'woocommerce' );
	}
};

export const DisputeEvidenceForm = ( {
	dispute,
	fileDetails = {},
	onDisputeUpdated,
}: DisputeEvidenceFormProps ) => {
	const [ evidence, setEvidence ] = useState( () =>
		getInitialEvidenceState( dispute )
	);
	const [ productType, setProductType ] = useState( () =>
		getInitialProductType( dispute )
	);
	const [ filesByField, setFilesByField ] =
		useState< EvidenceFileMap >( fileDetails );
	const [ notice, setNotice ] = useState< NoticeState >( null );
	const [ saveInProgress, setSaveInProgress ] = useState<
		'draft' | 'submit' | null
	>( null );
	const [ uploadingFields, setUploadingFields ] = useState<
		Partial< Record< DocumentEvidenceField, boolean > >
	>( {} );
	const noticeRef = useRef< HTMLDivElement | null >( null );
	const previousFileDetailsRef = useRef< EvidenceFileMap >( fileDetails );
	const disputeId = getDisputeId( dispute );
	const readOnly = ! isDisputeActionable( dispute );
	const isUploadingEvidence =
		Object.values( uploadingFields ).some( Boolean );
	const formLocked = readOnly || !! saveInProgress || isUploadingEvidence;
	const totalFileBytes = useMemo(
		() => getEvidenceFileByteTotal( filesByField ),
		[ filesByField ]
	);
	const disputeTracksProperties = useMemo(
		() => ( {
			dispute_id: disputeId,
			dispute_status: dispute.status,
			dispute_reason: dispute.reason,
		} ),
		[ disputeId, dispute.reason, dispute.status ]
	);

	useEffect( () => {
		setEvidence( getInitialEvidenceState( dispute ) );
		setProductType( getInitialProductType( dispute ) );
	}, [ dispute ] );

	useEffect( () => {
		const previousFileDetails = previousFileDetailsRef.current;

		setFilesByField( ( currentFiles ) => {
			const nextFiles = { ...currentFiles };

			DOCUMENT_EVIDENCE_FIELDS.forEach( ( field ) => {
				const previousFileId = previousFileDetails[ field ]?.id || '';
				const currentFileId = currentFiles[ field ]?.id || '';
				const nextFile = fileDetails[ field ];

				if ( currentFileId !== previousFileId ) {
					return;
				}

				if ( nextFile ) {
					nextFiles[ field ] = nextFile;
					return;
				}

				delete nextFiles[ field ];
			} );

			return nextFiles;
		} );
		previousFileDetailsRef.current = fileDetails;
	}, [ fileDetails ] );

	const updateNotice = useCallback( ( nextNotice: NoticeState ) => {
		setNotice( nextNotice );

		if ( ! nextNotice ) {
			return;
		}

		speak(
			nextNotice.message,
			nextNotice.type === 'error' ? 'assertive' : 'polite'
		);
		setTimeout( () => noticeRef.current?.focus(), 0 );
	}, [] );

	const updateEvidenceField = ( field: EvidenceField, value: string ) => {
		setEvidence( ( currentEvidence ) => ( {
			...currentEvidence,
			[ field ]: value,
		} ) );
	};

	const updateUploadState = useCallback(
		( field: DocumentEvidenceField, isUploading: boolean ) => {
			setUploadingFields( ( currentUploadingFields ) => {
				const nextUploadingFields = {
					...currentUploadingFields,
				};

				if ( isUploading ) {
					nextUploadingFields[ field ] = true;
				} else {
					delete nextUploadingFields[ field ];
				}

				return nextUploadingFields;
			} );
		},
		[]
	);

	const handleUploaded = (
		field: DocumentEvidenceField,
		file: WooPaymentsDisputeFile
	) => {
		updateNotice( null );
		setFilesByField( ( currentFiles ) => ( {
			...currentFiles,
			[ field ]: file,
		} ) );
		updateEvidenceField( field, file.id || '' );
	};

	const handleRemoveFile = ( field: DocumentEvidenceField ) => {
		setFilesByField( ( currentFiles ) => {
			const nextFiles = { ...currentFiles };
			delete nextFiles[ field ];
			return nextFiles;
		} );
		updateEvidenceField( field, '' );
	};

	const handleError = ( message: string ) => {
		updateNotice( {
			type: 'error',
			message,
		} );
	};

	const handleProductTypeChange = ( nextProductType: string ) => {
		recordEvent( 'wcpay_dispute_product_selected', {
			...disputeTracksProperties,
			selection: nextProductType,
		} );
		setProductType( nextProductType );
	};

	const handleSave = async ( submit: boolean ) => {
		if ( readOnly || saveInProgress ) {
			return;
		}

		if ( isUploadingEvidence ) {
			updateNotice( {
				type: 'error',
				message: __(
					'Wait for evidence files to finish uploading before saving.',
					'woocommerce'
				),
			} );
			return;
		}

		if ( submit ) {
			// eslint-disable-next-line no-alert -- The reference dispute flow confirms final submission with a browser confirmation.
			const confirmed = window.confirm(
				__(
					'Submitting evidence is final and cannot be undone. Submit evidence now?',
					'woocommerce'
				)
			);

			if ( ! confirmed ) {
				return;
			}
		}

		const eventPrefix = submit
			? 'wcpay_dispute_submit_evidence'
			: 'wcpay_dispute_save_evidence';

		recordEvent( `${ eventPrefix }_clicked`, disputeTracksProperties );
		updateNotice( null );
		setSaveInProgress( submit ? 'submit' : 'draft' );

		try {
			const updatedDispute = await updateWooPaymentsDispute(
				disputeId,
				buildEvidencePayload(
					{
						productType,
						evidence,
						existingEvidence: dispute.evidence,
						metadata: dispute.metadata,
					},
					submit
				)
			);
			recordEvent( `${ eventPrefix }_success`, disputeTracksProperties );
			updateNotice( {
				type: 'success',
				message: submit
					? __( 'Evidence submitted.', 'woocommerce' )
					: __( 'Evidence draft saved.', 'woocommerce' ),
			} );
			onDisputeUpdated?.( updatedDispute );
		} catch ( error ) {
			const message = getErrorMessage(
				error,
				submit
					? __( 'Unable to submit dispute evidence.', 'woocommerce' )
					: __( 'Unable to save dispute evidence.', 'woocommerce' )
			);

			recordEvent( `${ eventPrefix }_failed`, disputeTracksProperties );
			updateNotice( {
				type: 'error',
				message,
			} );
		} finally {
			setSaveInProgress( null );
		}
	};

	return (
		<div className="woocommerce-woopayments-dispute-evidence">
			<div className="woocommerce-woopayments-dispute-evidence__summary">
				<dl className="woocommerce-woopayments-money-movement__details">
					<div>
						<dt>{ __( 'Dispute ID', 'woocommerce' ) }</dt>
						<dd>{ disputeId }</dd>
					</div>
					<div>
						<dt>{ __( 'Reason', 'woocommerce' ) }</dt>
						<dd>{ formatLabel( dispute.reason ) }</dd>
					</div>
					<div>
						<dt>{ __( 'Status', 'woocommerce' ) }</dt>
						<dd>{ formatLabel( dispute.status ) }</dd>
					</div>
					<div>
						<dt>{ __( 'Amount', 'woocommerce' ) }</dt>
						<dd>
							{ formatAmount( dispute.amount, dispute.currency ) }
						</dd>
					</div>
					<div>
						<dt>{ __( 'Evidence due', 'woocommerce' ) }</dt>
						<dd>
							{ formatDate(
								dispute.evidence_due_by ||
									dispute.evidence_details?.due_by
							) }
						</dd>
					</div>
				</dl>
				{ readOnly && (
					<p className="woocommerce-woopayments-dispute-evidence__readonly">
						{ __( 'This dispute is read-only.', 'woocommerce' ) }
					</p>
				) }
			</div>
			{ notice && (
				<div
					ref={ noticeRef }
					className={ `woocommerce-woopayments-dispute-evidence__notice is-${ notice.type }` }
					role={ notice.type === 'error' ? 'alert' : 'status' }
					aria-live={
						notice.type === 'error' ? 'assertive' : 'polite'
					}
					tabIndex={ -1 }
				>
					{ notice.message }
				</div>
			) }
			<form className="woocommerce-woopayments-dispute-evidence__form">
				<fieldset className="woocommerce-woopayments-dispute-evidence__section">
					<legend>{ __( 'Product details', 'woocommerce' ) }</legend>
					<SelectControl
						label={ __( 'Product type', 'woocommerce' ) }
						value={ productType }
						options={ PRODUCT_TYPE_OPTIONS }
						disabled={ formLocked }
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						onChange={ handleProductTypeChange }
					/>
					<TextareaControl
						label={ __( 'Product description', 'woocommerce' ) }
						value={ evidence.product_description }
						readOnly={ formLocked }
						__nextHasNoMarginBottom
						onChange={ ( value ) =>
							updateEvidenceField( 'product_description', value )
						}
					/>
					<TextareaControl
						label={ __( 'Additional evidence', 'woocommerce' ) }
						value={ evidence.uncategorized_text }
						readOnly={ formLocked }
						__nextHasNoMarginBottom
						onChange={ ( value ) =>
							updateEvidenceField( 'uncategorized_text', value )
						}
					/>
					<TextControl
						label={ __( 'Customer purchase IP', 'woocommerce' ) }
						value={ evidence.customer_purchase_ip }
						readOnly={ formLocked }
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						onChange={ ( value ) =>
							updateEvidenceField( 'customer_purchase_ip', value )
						}
					/>
				</fieldset>
				<fieldset className="woocommerce-woopayments-dispute-evidence__section">
					<legend>{ __( 'Shipping details', 'woocommerce' ) }</legend>
					{ SHIPPING_EVIDENCE_FIELDS.map( ( field ) => (
						<TextControl
							key={ field }
							label={ formatLabel( field ) }
							value={ evidence[ field ] }
							readOnly={ formLocked }
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							onChange={ ( value ) =>
								updateEvidenceField( field, value )
							}
						/>
					) ) }
				</fieldset>
				<fieldset className="woocommerce-woopayments-dispute-evidence__section">
					<legend>{ __( 'Documents', 'woocommerce' ) }</legend>
					{ DOCUMENT_EVIDENCE_FIELDS.map( ( field ) => (
						<DisputeEvidenceFileUpload
							key={ field }
							field={ field }
							label={ getDocumentEvidenceFieldLabel( field ) }
							file={ filesByField[ field ] }
							totalFileBytes={ totalFileBytes }
							disabled={
								readOnly ||
								!! saveInProgress ||
								( isUploadingEvidence &&
									! uploadingFields[ field ] )
							}
							disputeTracksProperties={ disputeTracksProperties }
							onUploaded={ handleUploaded }
							onRemove={ handleRemoveFile }
							onError={ handleError }
							onUploadStateChange={ updateUploadState }
						/>
					) ) }
				</fieldset>
				{ ! readOnly && (
					<div className="woocommerce-woopayments-dispute-evidence__actions">
						<Button
							variant="secondary"
							type="button"
							isBusy={ saveInProgress === 'draft' }
							accessibleWhenDisabled
							disabled={
								!! saveInProgress || isUploadingEvidence
							}
							onClick={ () => handleSave( false ) }
						>
							{ __( 'Save draft', 'woocommerce' ) }
						</Button>
						<Button
							variant="primary"
							type="button"
							isBusy={ saveInProgress === 'submit' }
							accessibleWhenDisabled
							disabled={
								!! saveInProgress || isUploadingEvidence
							}
							onClick={ () => handleSave( true ) }
						>
							{ __( 'Submit evidence', 'woocommerce' ) }
						</Button>
					</div>
				) }
			</form>
		</div>
	);
};
