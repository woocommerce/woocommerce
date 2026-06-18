/**
 * Internal dependencies
 */
import type {
	WooPaymentsDispute,
	WooPaymentsDisputeEvidence,
	WooPaymentsDisputeFile,
	WooPaymentsDisputeMetadata,
} from './types';

export const ACTIONABLE_DISPUTE_STATUSES = [
	'needs_response',
	'warning_needs_response',
] as const;

export const DOCUMENT_EVIDENCE_FIELDS = [
	'receipt',
	'customer_communication',
	'customer_signature',
	'refund_policy',
	'duplicate_charge_documentation',
	'cancellation_policy',
	'cancellation_rebuttal',
	'access_activity_log',
	'service_documentation',
	'shipping_documentation',
	'uncategorized_file',
] as const;

export const SHIPPING_EVIDENCE_FIELDS = [
	'shipping_carrier',
	'shipping_date',
	'shipping_tracking_number',
	'shipping_address',
] as const;

export const OPTIONAL_TEXT_EVIDENCE_FIELDS = [
	'product_description',
	'uncategorized_text',
	'customer_purchase_ip',
] as const;

export const PRODUCT_TYPE_METADATA_KEY = '__product_type';

export const MAX_EVIDENCE_FILE_BYTES = 4500000;

export const ACCEPTED_EVIDENCE_FILE_MIME_TYPES = [
	'application/pdf',
	'image/png',
	'image/jpeg',
] as const;

export const ACCEPTED_EVIDENCE_FILE_EXTENSIONS = [
	'.pdf',
	'.png',
	'.jpg',
	'.jpeg',
] as const;

export const EVIDENCE_FILE_ACCEPT_ATTRIBUTE = [
	...ACCEPTED_EVIDENCE_FILE_EXTENSIONS,
	...ACCEPTED_EVIDENCE_FILE_MIME_TYPES,
].join( ',' );

export type DocumentEvidenceField =
	( typeof DOCUMENT_EVIDENCE_FIELDS )[ number ];

export type ShippingEvidenceField =
	( typeof SHIPPING_EVIDENCE_FIELDS )[ number ];

export type OptionalTextEvidenceField =
	( typeof OPTIONAL_TEXT_EVIDENCE_FIELDS )[ number ];

export type EvidenceField =
	| DocumentEvidenceField
	| ShippingEvidenceField
	| OptionalTextEvidenceField;

export type EvidenceFormState = {
	productType: string;
	evidence: Partial< Record< EvidenceField, string > > &
		Record< string, string | undefined >;
	existingEvidence?: WooPaymentsDisputeEvidence;
	metadata?: WooPaymentsDisputeMetadata;
};

export type EvidencePayload = {
	evidence: WooPaymentsDisputeEvidence;
	metadata: WooPaymentsDisputeMetadata;
	submit: boolean;
};

export type EvidenceFileMap = Partial<
	Record< DocumentEvidenceField, WooPaymentsDisputeFile >
>;

export const isDisputeActionable = ( dispute?: WooPaymentsDispute | null ) =>
	!! dispute?.status &&
	ACTIONABLE_DISPUTE_STATUSES.includes(
		dispute.status as ( typeof ACTIONABLE_DISPUTE_STATUSES )[ number ]
	);

export const buildEvidencePayload = (
	formState: EvidenceFormState,
	submit: boolean
): EvidencePayload => {
	const evidence: WooPaymentsDisputeEvidence = {
		...( formState.existingEvidence || {} ),
	};

	DOCUMENT_EVIDENCE_FIELDS.forEach( ( field ) => {
		evidence[ field ] = formState.evidence[ field ] || '';
	} );

	SHIPPING_EVIDENCE_FIELDS.forEach( ( field ) => {
		evidence[ field ] = formState.evidence[ field ] || '';
	} );

	OPTIONAL_TEXT_EVIDENCE_FIELDS.forEach( ( field ) => {
		evidence[ field ] = formState.evidence[ field ] || '';
	} );

	return {
		evidence,
		metadata: {
			...( formState.metadata || {} ),
			[ PRODUCT_TYPE_METADATA_KEY ]: formState.productType,
		},
		submit,
	};
};

export const extractSavedEvidenceFileIds = (
	dispute?: WooPaymentsDispute | null
) => {
	const evidence = dispute?.evidence || {};

	return DOCUMENT_EVIDENCE_FIELDS.reduce<
		Partial< Record< DocumentEvidenceField, string > >
	>( ( savedFileIds, field ) => {
		const value = evidence[ field ];

		if ( typeof value === 'string' && value ) {
			savedFileIds[ field ] = value;
		}

		return savedFileIds;
	}, {} );
};

export const getEvidenceFileByteTotal = (
	filesByField: EvidenceFileMap
): number =>
	Object.values( filesByField ).reduce(
		( total, file ) => total + ( file?.size || 0 ),
		0
	);

export const getEvidenceFileName = ( file?: WooPaymentsDisputeFile ) =>
	file?.filename || file?.file_name || file?.name || file?.id || '';

export const isAcceptedEvidenceFile = ( file: File ) => {
	if (
		ACCEPTED_EVIDENCE_FILE_MIME_TYPES.includes(
			file.type as ( typeof ACCEPTED_EVIDENCE_FILE_MIME_TYPES )[ number ]
		)
	) {
		return true;
	}

	const lowerName = file.name.toLowerCase();

	return ACCEPTED_EVIDENCE_FILE_EXTENSIONS.some( ( extension ) =>
		lowerName.endsWith( extension )
	);
};
