/**
 * External dependencies
 */
import { speak } from '@wordpress/a11y';
import {
	Button,
	ExternalLink,
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
import { generateDisputeCoverLetter } from './dispute-evidence-cover-letter';
import { DisputeEvidenceFileUpload } from './dispute-evidence-file-upload';
import {
	DOCUMENT_EVIDENCE_FIELDS,
	OPTIONAL_TEXT_EVIDENCE_FIELDS,
	PRODUCT_TYPE_OPTIONS,
	SHIPPING_EVIDENCE_FIELDS,
	type DocumentEvidenceField,
	type EvidenceFileMap,
	type EvidenceField,
	type RecommendedDocumentField,
	buildEvidencePayload,
	getEvidenceFileByteTotal,
	getRecommendedDocumentFields,
	getRecommendedShippingDocumentFields,
	isVisaComplianceDispute,
	isDisputeActionable,
	needsShipping,
} from './dispute-evidence-fields';
import type { WooPaymentsDispute, WooPaymentsDisputeFile } from './types';
import {
	formatAmount,
	formatDate,
	formatLabel,
	getDisputeId,
	getErrorMessage,
} from './utils';
import { getSettingsPaymentsProviderRouteUrl } from '../utils';
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

type EvidenceStep = 'basics' | 'shipping' | 'review' | 'confirmation';

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
	dispute.metadata?.__product_type ||
	dispute.order?.suggested_product_type ||
	'physical_product';

const getStepLabel = ( step: EvidenceStep ) => {
	switch ( step ) {
		case 'basics':
			return __( "Let's gather the basics", 'woocommerce' );
		case 'shipping':
			return __( 'Add your shipping details', 'woocommerce' );
		case 'review':
			return __( 'Review your cover letter', 'woocommerce' );
		case 'confirmation':
			return __( 'Thanks for sharing your response!', 'woocommerce' );
	}
};

const getStepDescription = ( step: EvidenceStep ) => {
	switch ( step ) {
		case 'basics':
			return __(
				'Add product and customer details that support your response.',
				'woocommerce'
			);
		case 'shipping':
			return __(
				'Add the shipment information for this physical product.',
				'woocommerce'
			);
		case 'review':
			return __(
				'Review the generated cover letter before submitting evidence.',
				'woocommerce'
			);
		case 'confirmation':
			return __(
				"We'll update this dispute when the bank reviews the submitted evidence.",
				'woocommerce'
			);
	}
};

const getPreviousStep = (
	currentStep: EvidenceStep,
	includeShippingStep: boolean
): EvidenceStep => {
	if ( currentStep === 'review' ) {
		return includeShippingStep ? 'shipping' : 'basics';
	}

	return 'basics';
};

const getNextStep = (
	currentStep: EvidenceStep,
	includeShippingStep: boolean
): EvidenceStep | null => {
	if ( currentStep === 'basics' ) {
		return includeShippingStep ? 'shipping' : 'review';
	}

	if ( currentStep === 'shipping' ) {
		return 'review';
	}

	return null;
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
	const [ refundStatus, setRefundStatus ] = useState(
		'refund_has_been_issued'
	);
	const [ duplicateStatus, setDuplicateStatus ] = useState( 'is_duplicate' );
	const [ filesByField, setFilesByField ] =
		useState< EvidenceFileMap >( fileDetails );
	const [ currentStep, setCurrentStep ] =
		useState< EvidenceStep >( 'basics' );
	const [ isCoverLetterManuallyEdited, setIsCoverLetterManuallyEdited ] =
		useState(
			() => !! getStringEvidenceValue( dispute, 'uncategorized_text' )
		);
	const [ notice, setNotice ] = useState< NoticeState >( null );
	const [ saveInProgress, setSaveInProgress ] = useState<
		'draft' | 'submit' | null
	>( null );
	const [ uploadingFields, setUploadingFields ] = useState<
		Partial< Record< DocumentEvidenceField, boolean > >
	>( {} );
	const formContainerRef = useRef< HTMLDivElement | null >( null );
	const stepHeadingRef = useRef< HTMLHeadingElement | null >( null );
	const previousStepRef = useRef< EvidenceStep >( currentStep );
	const noticeRef = useRef< HTMLDivElement | null >( null );
	const previousFileDetailsRef = useRef< EvidenceFileMap >( fileDetails );
	const disputeId = getDisputeId( dispute );
	const refundIssuedControlId = `${ disputeId }-refund-status-issued`;
	const refundNotOwedControlId = `${ disputeId }-refund-status-not-owed`;
	const duplicateControlId = `${ disputeId }-duplicate-status-duplicate`;
	const notDuplicateControlId = `${ disputeId }-duplicate-status-not-duplicate`;
	const readOnly = ! isDisputeActionable( dispute );
	const isUploadingEvidence =
		Object.values( uploadingFields ).some( Boolean );
	const formLocked = readOnly || !! saveInProgress || isUploadingEvidence;
	const totalFileBytes = useMemo(
		() => getEvidenceFileByteTotal( filesByField ),
		[ filesByField ]
	);
	const isVisaCompliance = isVisaComplianceDispute(
		dispute.reason,
		dispute.enhanced_eligibility_types
	);
	const includeShippingStep = needsShipping( dispute.reason, productType );
	const recommendedDocuments = useMemo(
		() =>
			getRecommendedDocumentFields( {
				reason: dispute.reason,
				productType,
				refundStatus,
				duplicateStatus,
				enhancedEligibilityTypes: dispute.enhanced_eligibility_types,
				evidence,
			} ),
		[
			duplicateStatus,
			dispute.enhanced_eligibility_types,
			dispute.reason,
			evidence,
			productType,
			refundStatus,
		]
	);
	const recommendedShippingDocuments = useMemo(
		() =>
			includeShippingStep
				? getRecommendedShippingDocumentFields(
						dispute.reason,
						productType
				  )
				: [],
		[ dispute.reason, includeShippingStep, productType ]
	);
	const recommendedDocumentLabels = useMemo(
		() =>
			[ ...recommendedDocuments, ...recommendedShippingDocuments ].reduce<
				Partial<
					Record< DocumentEvidenceField, RecommendedDocumentField >
				>
			>(
				( labels, document ) => ( {
					...labels,
					[ document.key ]: document,
				} ),
				{}
			),
		[ recommendedDocuments, recommendedShippingDocuments ]
	);
	const documentFieldsToRender = useMemo( () => {
		const fields = recommendedDocuments.map( ( document ) => document.key );

		DOCUMENT_EVIDENCE_FIELDS.forEach( ( field ) => {
			if ( field === 'shipping_documentation' ) {
				return;
			}

			if (
				! fields.includes( field ) &&
				( filesByField[ field ] || evidence[ field ] )
			) {
				fields.push( field );
			}
		} );

		return fields;
	}, [ evidence, filesByField, recommendedDocuments ] );
	const shippingDocumentFieldsToRender = useMemo( () => {
		const fields = recommendedShippingDocuments.map(
			( document ) => document.key
		);

		DOCUMENT_EVIDENCE_FIELDS.forEach( ( field ) => {
			if (
				! fields.includes( field ) &&
				field === 'shipping_documentation' &&
				( filesByField[ field ] || evidence[ field ] )
			) {
				fields.push( field );
			}
		} );

		return fields;
	}, [ evidence, filesByField, recommendedShippingDocuments ] );
	const generatedCoverLetter = useMemo(
		() =>
			generateDisputeCoverLetter( {
				dispute,
				productType,
				evidence,
				refundStatus,
				duplicateStatus,
			} ),
		[ dispute, duplicateStatus, evidence, productType, refundStatus ]
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
		setIsCoverLetterManuallyEdited(
			!! getStringEvidenceValue( dispute, 'uncategorized_text' )
		);
	}, [ dispute ] );

	useEffect( () => {
		if ( isVisaCompliance || isCoverLetterManuallyEdited ) {
			return;
		}

		setEvidence( ( currentEvidence ) => {
			if ( currentEvidence.uncategorized_text === generatedCoverLetter ) {
				return currentEvidence;
			}

			return {
				...currentEvidence,
				uncategorized_text: generatedCoverLetter,
			};
		} );
	}, [
		generatedCoverLetter,
		isCoverLetterManuallyEdited,
		isVisaCompliance,
	] );

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

	useEffect( () => {
		if ( previousStepRef.current === currentStep ) {
			return;
		}

		previousStepRef.current = currentStep;

		const ownerDocument = formContainerRef.current?.ownerDocument;
		const activeElement = ownerDocument?.activeElement;
		const focusIsInsideForm =
			activeElement instanceof HTMLElement &&
			!! formContainerRef.current?.contains( activeElement );

		if ( activeElement === ownerDocument?.body || focusIsInsideForm ) {
			stepHeadingRef.current?.focus();
		}
	}, [ currentStep ] );

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
		setIsCoverLetterManuallyEdited( false );
	};

	const handleRefundStatusChange = ( nextRefundStatus: string ) => {
		setRefundStatus( nextRefundStatus );
		setIsCoverLetterManuallyEdited( false );
	};

	const handleDuplicateStatusChange = ( nextDuplicateStatus: string ) => {
		setDuplicateStatus( nextDuplicateStatus );
		setIsCoverLetterManuallyEdited( false );
	};

	const handleSave = async (
		submit: boolean,
		{
			notify = true,
			refreshDispute = true,
			trackSuccess = true,
		}: {
			notify?: boolean;
			refreshDispute?: boolean;
			trackSuccess?: boolean;
		} = {}
	) => {
		if ( readOnly || saveInProgress ) {
			return null;
		}

		if ( isUploadingEvidence ) {
			updateNotice( {
				type: 'error',
				message: __(
					'Please wait until file upload is finished',
					'woocommerce'
				),
			} );
			return null;
		}

		if ( submit ) {
			// eslint-disable-next-line no-alert -- The reference dispute flow confirms final submission with a browser confirmation.
			const confirmed = window.confirm(
				__(
					'Are you sure you’re ready to submit this evidence? Evidence submissions are final.',
					'woocommerce'
				)
			);

			if ( ! confirmed ) {
				return null;
			}
		}

		const eventPrefix = submit
			? 'wcpay_dispute_submit_evidence'
			: 'wcpay_dispute_save_evidence';

		recordEvent( `${ eventPrefix }_clicked`, disputeTracksProperties );
		updateNotice( null );
		setSaveInProgress( submit ? 'submit' : 'draft' );

		try {
			const nextEvidence = {
				...evidence,
				uncategorized_text:
					evidence.uncategorized_text ||
					( isVisaCompliance ? '' : generatedCoverLetter ),
			};
			const updatedDispute = await updateWooPaymentsDispute(
				disputeId,
				buildEvidencePayload(
					{
						reason: dispute.reason,
						productType,
						refundStatus,
						duplicateStatus,
						evidence: nextEvidence,
						existingEvidence: dispute.evidence,
						metadata: dispute.metadata,
					},
					submit
				)
			);
			if ( trackSuccess ) {
				recordEvent(
					`${ eventPrefix }_success`,
					disputeTracksProperties
				);
			}
			if ( submit ) {
				setCurrentStep( 'confirmation' );
			}
			if ( notify ) {
				updateNotice( {
					type: 'success',
					message: submit
						? __( 'Evidence submitted!', 'woocommerce' )
						: __( 'Evidence saved!', 'woocommerce' ),
				} );
			}
			if ( refreshDispute ) {
				onDisputeUpdated?.( updatedDispute );
			}

			return updatedDispute;
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

			return null;
		} finally {
			setSaveInProgress( null );
		}
	};

	const handleContinue = async () => {
		const nextStep = getNextStep( currentStep, includeShippingStep );

		if ( ! nextStep ) {
			return;
		}

		if ( readOnly ) {
			setCurrentStep( nextStep );
			return;
		}

		const updatedDispute = await handleSave( false, {
			notify: false,
			refreshDispute: false,
			trackSuccess: false,
		} );

		if ( updatedDispute ) {
			setCurrentStep( nextStep );
		}
	};

	const handleBack = () => {
		setCurrentStep( getPreviousStep( currentStep, includeShippingStep ) );
	};

	const handleCoverLetterChange = ( value: string ) => {
		setIsCoverLetterManuallyEdited( true );
		updateEvidenceField( 'uncategorized_text', value );
	};

	const handleVisaComplianceDetailsChange = ( value: string ) => {
		if ( value.length > 20000 ) {
			return;
		}

		handleCoverLetterChange( value );
	};

	const renderRecommendedDocumentsSection = (
		fieldsToRender = documentFieldsToRender
	) => (
		<fieldset className="woocommerce-woopayments-dispute-evidence__section">
			<legend>{ __( 'Recommended documents', 'woocommerce' ) }</legend>
			<p className="woocommerce-woopayments-dispute-evidence__section-description">
				{ __(
					'While optional, we strongly recommend providing as many of these documents as possible. The following file types are supported: PDF, JPEG, and PNG.',
					'woocommerce'
				) }
			</p>
			<p className="woocommerce-woopayments-dispute-evidence__section-description">
				<ExternalLink href="https://woocommerce.com/document/woopayments/fraud-and-disputes/managing-disputes/#challenge-or-accept">
					{ __( 'Learn more about documents', 'woocommerce' ) }
				</ExternalLink>
			</p>
			{ fieldsToRender.map( ( field ) => {
				const recommendedDocument = recommendedDocumentLabels[ field ];
				const label =
					recommendedDocument?.label || formatLabel( field );

				return (
					<div
						key={ field }
						className="woocommerce-woopayments-dispute-evidence__document"
					>
						<DisputeEvidenceFileUpload
							field={ field }
							label={ label }
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
						{ recommendedDocument?.description && (
							<p className="woocommerce-woopayments-dispute-evidence__document-description">
								{ recommendedDocument.description }
							</p>
						) }
					</div>
				);
			} ) }
		</fieldset>
	);

	return (
		<div
			ref={ formContainerRef }
			className="woocommerce-woopayments-dispute-evidence"
		>
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
			{ currentStep === 'confirmation' ? (
				<div className="woocommerce-woopayments-dispute-evidence__confirmation">
					<h3 ref={ stepHeadingRef } tabIndex={ -1 }>
						{ getStepLabel( 'confirmation' ) }
					</h3>
					<p>
						{ isVisaCompliance
							? __(
									'Your response has been submitted under Visa’s compliance process.',
									'woocommerce'
							  )
							: getStepDescription( 'confirmation' ) }
					</p>
					<h4>{ __( 'What’s next?', 'woocommerce' ) }</h4>
					{ isVisaCompliance ? (
						<>
							<ul>
								<li>
									{ __(
										'Visa will review your submission under its network rules and determine the outcome of the dispute.',
										'woocommerce'
									) }
								</li>
								<li>
									{ __(
										'This review typically takes several weeks, but in some cases may take up to 3 months.',
										'woocommerce'
									) }
								</li>
							</ul>
							<p className="woocommerce-woopayments-dispute-evidence__notice is-info">
								<strong>
									{ __(
										'The outcome of this dispute will be determined by Visa.',
										'woocommerce'
									) }
								</strong>{ ' ' }
								{ __(
									'WooPayments has no influence over the decision and is not liable for any chargebacks.',
									'woocommerce'
								) }
							</p>
						</>
					) : (
						<p>
							{ __(
								'The bank determines the dispute outcome after reviewing the submitted evidence.',
								'woocommerce'
							) }
						</p>
					) }
					<div className="woocommerce-woopayments-dispute-evidence__actions">
						<Button
							variant="secondary"
							href={ getSettingsPaymentsProviderRouteUrl(
								'/woopayments/disputes'
							) }
						>
							{ __( 'Return to disputes', 'woocommerce' ) }
						</Button>
						<Button
							variant="primary"
							href={ getSettingsPaymentsProviderRouteUrl(
								`/woopayments/disputes/details?id=${ encodeURIComponent(
									disputeId
								) }`
							) }
						>
							{ __( 'View submitted dispute', 'woocommerce' ) }
						</Button>
					</div>
				</div>
			) : (
				<form className="woocommerce-woopayments-dispute-evidence__form">
					<div
						className="woocommerce-woopayments-dispute-evidence__step"
						aria-current="step"
					>
						<h3 ref={ stepHeadingRef } tabIndex={ -1 }>
							{ isVisaCompliance
								? __( 'Dispute information', 'woocommerce' )
								: getStepLabel( currentStep ) }
						</h3>
						<p>
							{ isVisaCompliance
								? __(
										'Tell us about this compliance dispute and upload any relevant documents.',
										'woocommerce'
								  )
								: getStepDescription( currentStep ) }
						</p>
					</div>
					{ isVisaCompliance && (
						<>
							<fieldset className="woocommerce-woopayments-dispute-evidence__section">
								<legend>
									{ __( 'Dispute details', 'woocommerce' ) }
								</legend>
								<h4>
									{ __(
										'Tell us about the dispute',
										'woocommerce'
									) }
								</h4>
								<p className="woocommerce-woopayments-dispute-evidence__section-description">
									{ __(
										'This is a compliance case and the issuer has indicated network rules have been violated. Please check for accuracy and upload any relevant documents.',
										'woocommerce'
									) }
								</p>
								<TextareaControl
									label={ __(
										'Why do you disagree with this dispute?',
										'woocommerce'
									) }
									help={ __(
										'Please enter any relevant details here.',
										'woocommerce'
									) }
									value={ evidence.uncategorized_text }
									readOnly={ formLocked }
									maxLength={ 20000 }
									rows={ 10 }
									__nextHasNoMarginBottom
									onChange={
										handleVisaComplianceDetailsChange
									}
								/>
							</fieldset>
							{ renderRecommendedDocumentsSection() }
						</>
					) }
					{ ! isVisaCompliance && currentStep === 'basics' && (
						<>
							<fieldset className="woocommerce-woopayments-dispute-evidence__section">
								<legend>
									{ __( 'Product details', 'woocommerce' ) }
								</legend>
								<SelectControl
									label={ __(
										'Product type',
										'woocommerce'
									) }
									value={ productType }
									options={ PRODUCT_TYPE_OPTIONS }
									disabled={ formLocked }
									__next40pxDefaultSize
									__nextHasNoMarginBottom
									onChange={ handleProductTypeChange }
								/>
								<TextareaControl
									label={ __(
										'Product description',
										'woocommerce'
									) }
									value={ evidence.product_description }
									readOnly={ formLocked }
									__nextHasNoMarginBottom
									onChange={ ( value ) =>
										updateEvidenceField(
											'product_description',
											value
										)
									}
								/>
								<TextControl
									label={ __(
										'Customer purchase IP',
										'woocommerce'
									) }
									value={ evidence.customer_purchase_ip }
									readOnly={ formLocked }
									__next40pxDefaultSize
									__nextHasNoMarginBottom
									onChange={ ( value ) =>
										updateEvidenceField(
											'customer_purchase_ip',
											value
										)
									}
								/>
							</fieldset>
							{ dispute.reason === 'credit_not_processed' && (
								<fieldset className="woocommerce-woopayments-dispute-evidence__section">
									<legend>
										{ __( 'Refund status', 'woocommerce' ) }
									</legend>
									<div className="woocommerce-woopayments-dispute-evidence__radio-group">
										<label
											htmlFor={ refundIssuedControlId }
										>
											<input
												id={ refundIssuedControlId }
												type="radio"
												name="woocommerce-woopayments-dispute-refund-status"
												value="refund_has_been_issued"
												checked={
													refundStatus ===
													'refund_has_been_issued'
												}
												disabled={ formLocked }
												onChange={ () =>
													handleRefundStatusChange(
														'refund_has_been_issued'
													)
												}
											/>
											{ __(
												'Refund has been issued',
												'woocommerce'
											) }
										</label>
										<label
											htmlFor={ refundNotOwedControlId }
										>
											<input
												id={ refundNotOwedControlId }
												type="radio"
												name="woocommerce-woopayments-dispute-refund-status"
												value="refund_was_not_owed"
												checked={
													refundStatus ===
													'refund_was_not_owed'
												}
												disabled={ formLocked }
												onChange={ () =>
													handleRefundStatusChange(
														'refund_was_not_owed'
													)
												}
											/>
											{ __(
												'Refund was not owed',
												'woocommerce'
											) }
										</label>
									</div>
								</fieldset>
							) }
							{ dispute.reason === 'duplicate' && (
								<fieldset className="woocommerce-woopayments-dispute-evidence__section">
									<legend>
										{ __(
											'Was this charge a duplicate?',
											'woocommerce'
										) }
									</legend>
									<div className="woocommerce-woopayments-dispute-evidence__radio-group">
										<label htmlFor={ duplicateControlId }>
											<input
												id={ duplicateControlId }
												type="radio"
												name="woocommerce-woopayments-dispute-duplicate-status"
												value="is_duplicate"
												checked={
													duplicateStatus ===
													'is_duplicate'
												}
												disabled={ formLocked }
												onChange={ () =>
													handleDuplicateStatusChange(
														'is_duplicate'
													)
												}
											/>
											{ __(
												'It was a duplicate',
												'woocommerce'
											) }
										</label>
										<label
											htmlFor={ notDuplicateControlId }
										>
											<input
												id={ notDuplicateControlId }
												type="radio"
												name="woocommerce-woopayments-dispute-duplicate-status"
												value="is_not_duplicate"
												checked={
													duplicateStatus ===
													'is_not_duplicate'
												}
												disabled={ formLocked }
												onChange={ () =>
													handleDuplicateStatusChange(
														'is_not_duplicate'
													)
												}
											/>
											{ __(
												'It was not a duplicate',
												'woocommerce'
											) }
										</label>
									</div>
								</fieldset>
							) }
							{ renderRecommendedDocumentsSection() }
						</>
					) }
					{ ! isVisaCompliance && currentStep === 'shipping' && (
						<>
							<fieldset className="woocommerce-woopayments-dispute-evidence__section">
								<legend>
									{ __( 'Shipping details', 'woocommerce' ) }
								</legend>
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
							{ renderRecommendedDocumentsSection(
								shippingDocumentFieldsToRender
							) }
						</>
					) }
					{ ! isVisaCompliance && currentStep === 'review' && (
						<fieldset className="woocommerce-woopayments-dispute-evidence__section">
							<legend>{ __( 'Review', 'woocommerce' ) }</legend>
							<TextareaControl
								label={ __( 'Cover letter', 'woocommerce' ) }
								value={
									evidence.uncategorized_text ||
									generatedCoverLetter
								}
								readOnly={ formLocked }
								__nextHasNoMarginBottom
								onChange={ handleCoverLetterChange }
							/>
						</fieldset>
					) }
					{ ! readOnly && (
						<div className="woocommerce-woopayments-dispute-evidence__actions">
							{ ! isVisaCompliance &&
								currentStep !== 'basics' && (
									<Button
										variant="secondary"
										type="button"
										accessibleWhenDisabled
										disabled={
											!! saveInProgress ||
											isUploadingEvidence
										}
										onClick={ handleBack }
									>
										{ __( 'Back', 'woocommerce' ) }
									</Button>
								) }
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
							{ ! isVisaCompliance &&
								getNextStep(
									currentStep,
									includeShippingStep
								) && (
									<Button
										variant="primary"
										type="button"
										isBusy={ saveInProgress === 'draft' }
										accessibleWhenDisabled
										disabled={
											!! saveInProgress ||
											isUploadingEvidence
										}
										onClick={ handleContinue }
									>
										{ __( 'Continue', 'woocommerce' ) }
									</Button>
								) }
							{ ( isVisaCompliance ||
								currentStep === 'review' ) && (
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
							) }
						</div>
					) }
				</form>
			) }
		</div>
	);
};
