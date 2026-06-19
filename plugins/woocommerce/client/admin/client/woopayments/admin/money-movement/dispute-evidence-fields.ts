/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

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

export const PRODUCT_TYPE_OPTIONS = [
	{
		label: __( 'Physical products', 'woocommerce' ),
		value: 'physical_product',
	},
	{
		label: __( 'Digital products', 'woocommerce' ),
		value: 'digital_product_or_service',
	},
	{
		label: __( 'Offline service', 'woocommerce' ),
		value: 'offline_service',
	},
	{
		label: __( 'Booking/Reservation', 'woocommerce' ),
		value: 'booking_reservation',
	},
	{
		label: __( 'Event', 'woocommerce' ),
		value: 'event',
	},
	{
		label: __( 'Other', 'woocommerce' ),
		value: 'other',
	},
];

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
	reason?: string;
	productType: string;
	refundStatus?: string;
	duplicateStatus?: string;
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

export type RecommendedDocumentField = {
	key: DocumentEvidenceField;
	label: string;
	description: string;
	order: number;
};

export type RecommendedDocumentInput = {
	reason?: string;
	productType?: string;
	refundStatus?: string;
	duplicateStatus?: string;
	enhancedEligibilityTypes?: string[];
	evidence?: Partial< WooPaymentsDisputeEvidence >;
};

const REASONS_WITHOUT_SHIPPING = [
	'duplicate',
	'subscription_canceled',
	'credit_not_processed',
];

const RECOMMENDED_DOCUMENT_LABELS: Record< DocumentEvidenceField, string > = {
	receipt: __( 'Order receipt', 'woocommerce' ),
	customer_communication: __( 'Customer communication', 'woocommerce' ),
	customer_signature: __( 'Customer signature', 'woocommerce' ),
	refund_policy: __( 'Refund policy', 'woocommerce' ),
	duplicate_charge_documentation: __(
		'Duplicate charge documentation',
		'woocommerce'
	),
	cancellation_policy: __( 'Cancellation policy', 'woocommerce' ),
	cancellation_rebuttal: __( 'Cancellation rebuttal', 'woocommerce' ),
	access_activity_log: __( 'Access activity log', 'woocommerce' ),
	service_documentation: __( 'Service documentation', 'woocommerce' ),
	shipping_documentation: __( 'Shipping documentation', 'woocommerce' ),
	uncategorized_file: __( 'Other documents', 'woocommerce' ),
};

const RECOMMENDED_DOCUMENT_DESCRIPTIONS: Record<
	DocumentEvidenceField,
	string
> = {
	receipt: __(
		"A copy of the customer's receipt, which can be found in the receipt history for this transaction.",
		'woocommerce'
	),
	customer_communication: __(
		'Any correspondence with the customer regarding this purchase.',
		'woocommerce'
	),
	customer_signature: __(
		"Any relevant documents showing the customer's signature, such as signed proof of delivery.",
		'woocommerce'
	),
	refund_policy: __(
		"A screenshot of your store's refund policy.",
		'woocommerce'
	),
	duplicate_charge_documentation: __(
		'Receipts or records for the disputed and related charges.',
		'woocommerce'
	),
	cancellation_policy: __(
		"A screenshot of your store's cancellation policy or terms of service.",
		'woocommerce'
	),
	cancellation_rebuttal: __(
		'Any explanation or records showing why the cancellation dispute should be challenged.',
		'woocommerce'
	),
	access_activity_log: __(
		'Any documents showing the login history, usage activity, or access logs for the digital product or service.',
		'woocommerce'
	),
	service_documentation: __(
		'Proof that the service was provided as described.',
		'woocommerce'
	),
	shipping_documentation: __(
		'Tracking, carrier, or delivery confirmation documents.',
		'woocommerce'
	),
	uncategorized_file: __(
		'Any other relevant documents that will support your case.',
		'woocommerce'
	),
};

const buildRecommendedDocument = (
	key: DocumentEvidenceField,
	order: number,
	label = RECOMMENDED_DOCUMENT_LABELS[ key ],
	description = RECOMMENDED_DOCUMENT_DESCRIPTIONS[ key ]
): RecommendedDocumentField => ( {
	key,
	label,
	description,
	order,
} );

const buildRecommendedDocuments = (
	entries: Array<
		[
			DocumentEvidenceField,
			number,
			( string | undefined )?,
			( string | undefined )?
		]
	>
): RecommendedDocumentField[] =>
	entries.map( ( [ key, order, label, description ] ) =>
		buildRecommendedDocument( key, order, label, description )
	);

const OTHER_DOCUMENTS_DESCRIPTION = __(
	'Any other relevant documents that will support your case.',
	'woocommerce'
);

const ORDER_RECEIPT_DESCRIPTION = __(
	"A copy of the customer's receipt, which can be found in the receipt history for this transaction.",
	'woocommerce'
);

const REFUND_POLICY_DESCRIPTION = __(
	"A screenshot of your store's refund policy.",
	'woocommerce'
);

const CUSTOMER_COMMUNICATION_DOCUMENT = buildRecommendedDocument(
	'customer_communication',
	20,
	__( 'Customer communication', 'woocommerce' ),
	__(
		'Any correspondence with the customer regarding this purchase.',
		'woocommerce'
	)
);

const getDuplicateDocuments = (
	productType: string,
	duplicateStatus = 'is_duplicate'
): RecommendedDocumentField[] => {
	const receiptDocuments: RecommendedDocumentField[] =
		duplicateStatus === 'is_duplicate'
			? buildRecommendedDocuments( [
					[
						'receipt',
						10,
						__( 'Order receipt', 'woocommerce' ),
						ORDER_RECEIPT_DESCRIPTION,
					],
					[
						'duplicate_charge_documentation',
						15,
						__( 'Refund receipt', 'woocommerce' ),
						__(
							'A confirmation that a refund was issued.',
							'woocommerce'
						),
					],
			  ] )
			: buildRecommendedDocuments( [
					[
						'receipt',
						10,
						__( 'Order receipt', 'woocommerce' ),
						ORDER_RECEIPT_DESCRIPTION,
					],
					[
						'duplicate_charge_documentation',
						12,
						__( 'Any additional receipts', 'woocommerce' ),
						__(
							'Receipt(s) for any other order(s) from this customer.',
							'woocommerce'
						),
					],
			  ] );

	if (
		duplicateStatus === 'is_duplicate' &&
		productType === 'physical_product'
	) {
		return [
			...receiptDocuments,
			buildRecommendedDocument(
				'access_activity_log',
				22,
				__( 'Proof of active subscription', 'woocommerce' ),
				__(
					'Any documents showing the billing history, subscription status, or cancellation logs, for example.',
					'woocommerce'
				)
			),
			buildRecommendedDocument(
				'refund_policy',
				25,
				__( 'Refund policy', 'woocommerce' ),
				REFUND_POLICY_DESCRIPTION
			),
			buildRecommendedDocument(
				'cancellation_policy',
				30,
				__( 'Terms of service', 'woocommerce' ),
				__(
					"A screenshot of your store's terms of service.",
					'woocommerce'
				)
			),
			buildRecommendedDocument(
				'uncategorized_file',
				100,
				__( 'Other documents', 'woocommerce' ),
				OTHER_DOCUMENTS_DESCRIPTION
			),
		];
	}

	return [
		...receiptDocuments,
		buildRecommendedDocument(
			'refund_policy',
			25,
			__( 'Refund policy', 'woocommerce' ),
			REFUND_POLICY_DESCRIPTION
		),
		buildRecommendedDocument(
			'uncategorized_file',
			100,
			__( 'Other documents', 'woocommerce' ),
			OTHER_DOCUMENTS_DESCRIPTION
		),
	];
};

const getCreditNotProcessedDocuments = (
	productType: string,
	refundStatus = 'refund_has_been_issued'
): RecommendedDocumentField[] => {
	if ( productType === 'physical_product' ) {
		if ( refundStatus === 'refund_was_not_owed' ) {
			return buildRecommendedDocuments( [
				[
					'uncategorized_file',
					10,
					__( 'Proof of acceptance', 'woocommerce' ),
					__(
						'Screenshot or document showing where the customer agreed to or acknowledged the refund policy during checkout or on the receipt.',
						'woocommerce'
					),
				],
				[
					'customer_communication',
					20,
					__( 'Customer communication', 'woocommerce' ),
					RECOMMENDED_DOCUMENT_DESCRIPTIONS.customer_communication,
				],
				[
					'refund_policy',
					25,
					__( 'Refund policy', 'woocommerce' ),
					REFUND_POLICY_DESCRIPTION,
				],
				[
					'service_documentation',
					100,
					__( 'Other documents', 'woocommerce' ),
					OTHER_DOCUMENTS_DESCRIPTION,
				],
			] );
		}

		return buildRecommendedDocuments( [
			[
				'receipt',
				10,
				__( 'Order receipt', 'woocommerce' ),
				ORDER_RECEIPT_DESCRIPTION,
			],
			[
				'duplicate_charge_documentation',
				12,
				__( 'Refund receipt', 'woocommerce' ),
				__(
					'A confirmation that a merchant is waiting for a return prior to refund.',
					'woocommerce'
				),
			],
			[
				'shipping_documentation',
				15,
				__( 'Return tracking', 'woocommerce' ),
				__(
					'A confirmation that a merchant is waiting for a return prior to refund (if applicable).',
					'woocommerce'
				),
			],
			[
				'customer_communication',
				20,
				__( 'Customer communication', 'woocommerce' ),
				RECOMMENDED_DOCUMENT_DESCRIPTIONS.customer_communication,
			],
			[
				'customer_signature',
				25,
				__( "Customer's signature", 'woocommerce' ),
				RECOMMENDED_DOCUMENT_DESCRIPTIONS.customer_signature,
			],
			[
				'refund_policy',
				30,
				__( 'Refund policy', 'woocommerce' ),
				REFUND_POLICY_DESCRIPTION,
			],
			[
				'uncategorized_file',
				100,
				__( 'Other documents', 'woocommerce' ),
				OTHER_DOCUMENTS_DESCRIPTION,
			],
		] );
	}

	if ( refundStatus === 'refund_was_not_owed' ) {
		return buildRecommendedDocuments( [
			[
				'uncategorized_file',
				10,
				__( 'Proof of acceptance', 'woocommerce' ),
				__(
					'Screenshot or document showing where the customer agreed to or acknowledged the refund policy during checkout or on the receipt.',
					'woocommerce'
				),
			],
			[
				'refund_policy',
				25,
				__( 'Refund policy', 'woocommerce' ),
				REFUND_POLICY_DESCRIPTION,
			],
			[
				'customer_communication',
				100,
				__( 'Other documents', 'woocommerce' ),
				OTHER_DOCUMENTS_DESCRIPTION,
			],
		] );
	}

	if ( productType === 'other' ) {
		return buildRecommendedDocuments( [
			[
				'receipt',
				10,
				__( 'Refund receipt', 'woocommerce' ),
				__(
					'A copy of the refund receipt, which can be found in the receipt history for this transaction.',
					'woocommerce'
				),
			],
			[
				'shipping_documentation',
				15,
				__( 'Return tracking', 'woocommerce' ),
				__(
					'A confirmation that a merchant is waiting for a return prior to refund (if applicable).',
					'woocommerce'
				),
			],
			[
				'customer_communication',
				100,
				__( 'Other documents', 'woocommerce' ),
				OTHER_DOCUMENTS_DESCRIPTION,
			],
		] );
	}

	return buildRecommendedDocuments( [
		[
			'receipt',
			10,
			__( 'Refund receipt', 'woocommerce' ),
			__(
				'A copy of the refund receipt, which can be found in the receipt history for this transaction.',
				'woocommerce'
			),
		],
		[
			'cancellation_rebuttal',
			20,
			__( 'Cancellation logs', 'woocommerce' ),
			__(
				'Records showing no cancellation attempt or request was made before the charge, such as account activity, subscription status, or communication history.',
				'woocommerce'
			),
		],
		[
			'customer_communication',
			100,
			__( 'Other documents', 'woocommerce' ),
			OTHER_DOCUMENTS_DESCRIPTION,
		],
	] );
};

const REFERENCE_EVIDENCE_MATRIX: Record<
	string,
	Record< string, RecommendedDocumentField[] >
> = {
	fraudulent: {
		physical_product: buildRecommendedDocuments( [
			[
				'receipt',
				10,
				__( 'Order receipt', 'woocommerce' ),
				ORDER_RECEIPT_DESCRIPTION,
			],
			[
				'access_activity_log',
				15,
				__( 'Prior undisputed transaction history', 'woocommerce' ),
				__(
					'Proof of past undisputed transactions from the same customer, with matching billing and device details.',
					'woocommerce'
				),
			],
			[
				'customer_signature',
				25,
				__( "Customer's signature", 'woocommerce' ),
				RECOMMENDED_DOCUMENT_DESCRIPTIONS.customer_signature,
			],
			[
				'refund_policy',
				30,
				__( 'Refund policy', 'woocommerce' ),
				REFUND_POLICY_DESCRIPTION,
			],
			[
				'uncategorized_file',
				100,
				__( 'Other documents', 'woocommerce' ),
				OTHER_DOCUMENTS_DESCRIPTION,
			],
		] ),
		digital_product_or_service: buildRecommendedDocuments( [
			[
				'access_activity_log',
				10,
				__( 'Login or usage records', 'woocommerce' ),
				RECOMMENDED_DOCUMENT_DESCRIPTIONS.access_activity_log,
			],
			[
				'service_documentation',
				15,
				__( 'Prior undisputed transaction history', 'woocommerce' ),
				__(
					'Proof of past undisputed transactions from the same customer, with matching billing and device details.',
					'woocommerce'
				),
			],
			[
				'uncategorized_file',
				100,
				__( 'Other documents', 'woocommerce' ),
				OTHER_DOCUMENTS_DESCRIPTION,
			],
		] ),
		booking_reservation: buildRecommendedDocuments( [
			[
				'access_activity_log',
				10,
				__( 'Prior undisputed transaction history', 'woocommerce' ),
				__(
					'Proof of past undisputed transactions from the same customer, with matching billing and device details.',
					'woocommerce'
				),
			],
			[
				'uncategorized_file',
				100,
				__( 'Other documents', 'woocommerce' ),
				OTHER_DOCUMENTS_DESCRIPTION,
			],
		] ),
		offline_service: buildRecommendedDocuments( [
			[
				'access_activity_log',
				10,
				__( 'Prior undisputed transaction history', 'woocommerce' ),
				__(
					'Proof of past undisputed transactions from the same customer, with matching billing and device details.',
					'woocommerce'
				),
			],
			[
				'uncategorized_file',
				100,
				__( 'Other documents', 'woocommerce' ),
				OTHER_DOCUMENTS_DESCRIPTION,
			],
		] ),
		event: buildRecommendedDocuments( [
			[
				'access_activity_log',
				10,
				__( 'Prior undisputed transaction history', 'woocommerce' ),
				__(
					'Proof of past undisputed transactions from the same customer, with matching billing and device details.',
					'woocommerce'
				),
			],
			[
				'uncategorized_file',
				100,
				__( 'Other documents', 'woocommerce' ),
				OTHER_DOCUMENTS_DESCRIPTION,
			],
		] ),
		other: buildRecommendedDocuments( [
			[
				'access_activity_log',
				10,
				__( 'Prior undisputed transaction history', 'woocommerce' ),
				__(
					'Proof of past undisputed transactions from the same customer, with matching billing and device details.',
					'woocommerce'
				),
			],
			[
				'uncategorized_file',
				100,
				__( 'Other documents', 'woocommerce' ),
				OTHER_DOCUMENTS_DESCRIPTION,
			],
		] ),
	},
	product_not_received: {
		physical_product: buildRecommendedDocuments( [
			[
				'receipt',
				10,
				__( 'Order receipt', 'woocommerce' ),
				ORDER_RECEIPT_DESCRIPTION,
			],
			[
				'customer_signature',
				25,
				__( "Customer's signature", 'woocommerce' ),
				RECOMMENDED_DOCUMENT_DESCRIPTIONS.customer_signature,
			],
			[
				'refund_policy',
				30,
				__( 'Refund policy', 'woocommerce' ),
				REFUND_POLICY_DESCRIPTION,
			],
			[
				'uncategorized_file',
				100,
				__( 'Other documents', 'woocommerce' ),
				OTHER_DOCUMENTS_DESCRIPTION,
			],
		] ),
		digital_product_or_service: buildRecommendedDocuments( [
			[
				'receipt',
				10,
				__( 'Order receipt', 'woocommerce' ),
				ORDER_RECEIPT_DESCRIPTION,
			],
			[
				'access_activity_log',
				15,
				__( 'Login or usage records', 'woocommerce' ),
				RECOMMENDED_DOCUMENT_DESCRIPTIONS.access_activity_log,
			],
			[
				'uncategorized_file',
				100,
				__( 'Other documents', 'woocommerce' ),
				OTHER_DOCUMENTS_DESCRIPTION,
			],
		] ),
		booking_reservation: buildRecommendedDocuments( [
			[
				'receipt',
				10,
				__( 'Order receipt', 'woocommerce' ),
				ORDER_RECEIPT_DESCRIPTION,
			],
			[
				'service_documentation',
				25,
				__( 'Reservation or booking confirmation', 'woocommerce' ),
				__(
					'Any documents showing the service completion, attendance or reservation confirmation.',
					'woocommerce'
				),
			],
			[
				'cancellation_rebuttal',
				30,
				__( 'Cancellation confirmation', 'woocommerce' ),
				__(
					'Documents showing the product or service was canceled, such as cancellation logs, confirmation emails, or account records.',
					'woocommerce'
				),
			],
			[
				'uncategorized_file',
				100,
				__( 'Other documents', 'woocommerce' ),
				OTHER_DOCUMENTS_DESCRIPTION,
			],
		] ),
		offline_service: buildRecommendedDocuments( [
			[
				'receipt',
				10,
				__( 'Order receipt', 'woocommerce' ),
				ORDER_RECEIPT_DESCRIPTION,
			],
			[
				'service_documentation',
				15,
				__( 'Proof of service completion', 'woocommerce' ),
				__(
					'Screenshots or documents showing the service was completed and delivered to the customer.',
					'woocommerce'
				),
			],
			[
				'uncategorized_file',
				100,
				__( 'Other documents', 'woocommerce' ),
				OTHER_DOCUMENTS_DESCRIPTION,
			],
		] ),
		event: buildRecommendedDocuments( [
			[
				'receipt',
				10,
				__( 'Order receipt', 'woocommerce' ),
				ORDER_RECEIPT_DESCRIPTION,
			],
			[
				'service_documentation',
				15,
				__( 'Attendance confirmation', 'woocommerce' ),
				__(
					'Any documents showing the service completion, attendance or reservation confirmation.',
					'woocommerce'
				),
			],
			[
				'uncategorized_file',
				100,
				__( 'Other documents', 'woocommerce' ),
				OTHER_DOCUMENTS_DESCRIPTION,
			],
		] ),
		other: buildRecommendedDocuments( [
			[
				'receipt',
				10,
				__( 'Order receipt', 'woocommerce' ),
				ORDER_RECEIPT_DESCRIPTION,
			],
			[
				'service_documentation',
				15,
				__( 'Service completion records', 'woocommerce' ),
				__(
					'Screenshots or documents showing the service was completed and delivered to the customer.',
					'woocommerce'
				),
			],
			[
				'uncategorized_file',
				100,
				__( 'Other documents', 'woocommerce' ),
				OTHER_DOCUMENTS_DESCRIPTION,
			],
		] ),
	},
	product_unacceptable: {
		physical_product: buildRecommendedDocuments( [
			[
				'receipt',
				10,
				__( 'Order receipt', 'woocommerce' ),
				ORDER_RECEIPT_DESCRIPTION,
			],
			[
				'customer_signature',
				15,
				__( "Customer's signature", 'woocommerce' ),
				RECOMMENDED_DOCUMENT_DESCRIPTIONS.customer_signature,
			],
			[
				'refund_policy',
				25,
				__( 'Refund policy', 'woocommerce' ),
				REFUND_POLICY_DESCRIPTION,
			],
			[
				'service_documentation',
				30,
				__( "Item's condition", 'woocommerce' ),
				__(
					"Photos showing the item's condition prior to shipping.",
					'woocommerce'
				),
			],
			[
				'uncategorized_file',
				100,
				__( 'Other documents', 'woocommerce' ),
				OTHER_DOCUMENTS_DESCRIPTION,
			],
		] ),
		digital_product_or_service: buildRecommendedDocuments( [
			[
				'service_documentation',
				10,
				__( 'Proof of delivered service', 'woocommerce' ),
				__(
					'Screenshots or documents showing the digital product or service was delivered and accessible to the customer.',
					'woocommerce'
				),
			],
			[
				'receipt',
				12,
				__( 'Order receipt', 'woocommerce' ),
				ORDER_RECEIPT_DESCRIPTION,
			],
			[
				'access_activity_log',
				15,
				__( 'Login or usage records', 'woocommerce' ),
				RECOMMENDED_DOCUMENT_DESCRIPTIONS.access_activity_log,
			],
			[
				'refund_policy',
				25,
				__( 'Refund policy', 'woocommerce' ),
				REFUND_POLICY_DESCRIPTION,
			],
			[
				'uncategorized_file',
				100,
				__( 'Other documents', 'woocommerce' ),
				OTHER_DOCUMENTS_DESCRIPTION,
			],
		] ),
		booking_reservation: buildRecommendedDocuments( [
			[
				'service_documentation',
				10,
				__( 'Event or booking documentation', 'woocommerce' ),
				__(
					'Screenshots or documents showing the event or reservation details (date, location, description, and terms) and confirmation it occurred or remained valid as described.',
					'woocommerce'
				),
			],
			[
				'receipt',
				15,
				__( 'Order receipt', 'woocommerce' ),
				ORDER_RECEIPT_DESCRIPTION,
			],
			[
				'refund_policy',
				25,
				__( 'Refund policy', 'woocommerce' ),
				REFUND_POLICY_DESCRIPTION,
			],
			[
				'uncategorized_file',
				100,
				__( 'Other documents', 'woocommerce' ),
				OTHER_DOCUMENTS_DESCRIPTION,
			],
		] ),
		offline_service: buildRecommendedDocuments( [
			[
				'service_documentation',
				10,
				__( 'Proof of delivered service', 'woocommerce' ),
				__(
					'Screenshots or documents showing the service was completed and delivered to the customer.',
					'woocommerce'
				),
			],
			[
				'receipt',
				12,
				__( 'Order receipt', 'woocommerce' ),
				ORDER_RECEIPT_DESCRIPTION,
			],
			[
				'refund_policy',
				25,
				__( 'Refund policy', 'woocommerce' ),
				REFUND_POLICY_DESCRIPTION,
			],
			[
				'uncategorized_file',
				100,
				__( 'Other documents', 'woocommerce' ),
				OTHER_DOCUMENTS_DESCRIPTION,
			],
		] ),
		event: buildRecommendedDocuments( [
			[
				'service_documentation',
				10,
				__( 'Event or booking documentation', 'woocommerce' ),
				__(
					'Screenshots or documents showing the event or reservation details (date, location, description, and terms) and confirmation it occurred or remained valid as described.',
					'woocommerce'
				),
			],
			[
				'receipt',
				12,
				__( 'Order receipt', 'woocommerce' ),
				ORDER_RECEIPT_DESCRIPTION,
			],
			[
				'refund_policy',
				25,
				__( 'Refund policy', 'woocommerce' ),
				REFUND_POLICY_DESCRIPTION,
			],
			[
				'uncategorized_file',
				100,
				__( 'Other documents', 'woocommerce' ),
				OTHER_DOCUMENTS_DESCRIPTION,
			],
		] ),
		other: buildRecommendedDocuments( [
			[
				'receipt',
				10,
				__( 'Order receipt', 'woocommerce' ),
				ORDER_RECEIPT_DESCRIPTION,
			],
			[
				'cancellation_policy',
				25,
				__( 'Terms of service', 'woocommerce' ),
				__(
					"A screenshot of your store's terms of service.",
					'woocommerce'
				),
			],
			[
				'uncategorized_file',
				100,
				__( 'Other documents', 'woocommerce' ),
				OTHER_DOCUMENTS_DESCRIPTION,
			],
		] ),
	},
	subscription_canceled: {
		physical_product: buildRecommendedDocuments( [
			[
				'receipt',
				10,
				__( 'Order receipt', 'woocommerce' ),
				ORDER_RECEIPT_DESCRIPTION,
			],
			[
				'cancellation_rebuttal',
				25,
				__( 'Cancellation logs', 'woocommerce' ),
				__(
					'Records showing no cancellation attempt or request was made before the charge, such as account activity, subscription status, or communication history.',
					'woocommerce'
				),
			],
			[
				'refund_policy',
				30,
				__( 'Refund policy', 'woocommerce' ),
				REFUND_POLICY_DESCRIPTION,
			],
			[
				'cancellation_policy',
				35,
				__( 'Terms of service', 'woocommerce' ),
				__(
					"A screenshot of your store's terms of service.",
					'woocommerce'
				),
			],
			[
				'uncategorized_file',
				100,
				__( 'Other documents', 'woocommerce' ),
				OTHER_DOCUMENTS_DESCRIPTION,
			],
		] ),
		digital_product_or_service: buildRecommendedDocuments( [
			[
				'receipt',
				10,
				__( 'Order receipt', 'woocommerce' ),
				ORDER_RECEIPT_DESCRIPTION,
			],
			[
				'cancellation_rebuttal',
				15,
				__( 'Cancellation logs', 'woocommerce' ),
				__(
					'Records showing no cancellation attempt or request was made before the charge, such as account activity, subscription status, or communication history.',
					'woocommerce'
				),
			],
			[
				'access_activity_log',
				22,
				__( 'Login or usage records', 'woocommerce' ),
				RECOMMENDED_DOCUMENT_DESCRIPTIONS.access_activity_log,
			],
			[
				'cancellation_policy',
				30,
				__( 'Terms of service', 'woocommerce' ),
				__(
					"A screenshot of your store's terms of service.",
					'woocommerce'
				),
			],
			[
				'uncategorized_file',
				100,
				__( 'Other documents', 'woocommerce' ),
				OTHER_DOCUMENTS_DESCRIPTION,
			],
		] ),
		booking_reservation: buildRecommendedDocuments( [
			[
				'receipt',
				10,
				__( 'Order receipt', 'woocommerce' ),
				ORDER_RECEIPT_DESCRIPTION,
			],
			[
				'cancellation_rebuttal',
				25,
				__( 'Cancellation logs', 'woocommerce' ),
				__(
					'Records showing no cancellation attempt or request was made before the charge, such as account activity, subscription status, or communication history.',
					'woocommerce'
				),
			],
			[
				'cancellation_policy',
				30,
				__( 'Terms of service', 'woocommerce' ),
				__(
					"A screenshot of your store's terms of service.",
					'woocommerce'
				),
			],
			[
				'uncategorized_file',
				100,
				__( 'Other documents', 'woocommerce' ),
				OTHER_DOCUMENTS_DESCRIPTION,
			],
		] ),
		offline_service: buildRecommendedDocuments( [
			[
				'receipt',
				10,
				__( 'Order receipt', 'woocommerce' ),
				ORDER_RECEIPT_DESCRIPTION,
			],
			[
				'cancellation_rebuttal',
				25,
				__( 'Cancellation logs', 'woocommerce' ),
				__(
					'Records showing no cancellation attempt or request was made before the charge, such as account activity, subscription status, or communication history.',
					'woocommerce'
				),
			],
			[
				'cancellation_policy',
				30,
				__( 'Terms of service', 'woocommerce' ),
				__(
					"A screenshot of your store's terms of service.",
					'woocommerce'
				),
			],
			[
				'uncategorized_file',
				100,
				__( 'Other documents', 'woocommerce' ),
				OTHER_DOCUMENTS_DESCRIPTION,
			],
		] ),
		event: buildRecommendedDocuments( [
			[
				'receipt',
				10,
				__( 'Order receipt', 'woocommerce' ),
				ORDER_RECEIPT_DESCRIPTION,
			],
			[
				'cancellation_rebuttal',
				25,
				__( 'Cancellation logs', 'woocommerce' ),
				__(
					'Records showing no cancellation attempt or request was made before the charge, such as account activity, subscription status, or communication history.',
					'woocommerce'
				),
			],
			[
				'cancellation_policy',
				30,
				__( 'Terms of service', 'woocommerce' ),
				__(
					"A screenshot of your store's terms of service.",
					'woocommerce'
				),
			],
			[
				'uncategorized_file',
				100,
				__( 'Other documents', 'woocommerce' ),
				OTHER_DOCUMENTS_DESCRIPTION,
			],
		] ),
		other: buildRecommendedDocuments( [
			[
				'receipt',
				10,
				__( 'Order receipt', 'woocommerce' ),
				ORDER_RECEIPT_DESCRIPTION,
			],
			[
				'cancellation_policy',
				25,
				__( 'Terms of service', 'woocommerce' ),
				__(
					"A screenshot of your store's terms of service.",
					'woocommerce'
				),
			],
			[
				'uncategorized_file',
				100,
				__( 'Other documents', 'woocommerce' ),
				OTHER_DOCUMENTS_DESCRIPTION,
			],
		] ),
	},
};

export const needsShipping = ( reason?: string, productType = '' ): boolean =>
	productType === 'physical_product' &&
	! REASONS_WITHOUT_SHIPPING.includes( reason || '' );

export const isVisaComplianceDispute = (
	reason?: string,
	enhancedEligibilityTypes: string[] = []
): boolean =>
	reason === 'noncompliant' ||
	enhancedEligibilityTypes.includes( 'visa_compliance' );

export const getRecommendedDocumentFields = ( {
	reason,
	productType = 'physical_product',
	refundStatus,
	duplicateStatus,
	enhancedEligibilityTypes = [],
}: RecommendedDocumentInput ): RecommendedDocumentField[] => {
	if ( isVisaComplianceDispute( reason, enhancedEligibilityTypes ) ) {
		return [
			buildRecommendedDocument(
				'customer_communication',
				0,
				__( 'Upload evidence', 'woocommerce' ),
				__(
					'Submit any files you find relevant to this dispute.',
					'woocommerce'
				)
			),
			buildRecommendedDocument(
				'uncategorized_file',
				0,
				__( 'Other documents', 'woocommerce' ),
				OTHER_DOCUMENTS_DESCRIPTION
			),
		];
	}

	let documents: RecommendedDocumentField[] | undefined;

	if ( reason === 'duplicate' ) {
		documents = getDuplicateDocuments( productType, duplicateStatus );
	} else if ( reason === 'credit_not_processed' ) {
		documents = getCreditNotProcessedDocuments( productType, refundStatus );
	} else if ( reason ) {
		documents = REFERENCE_EVIDENCE_MATRIX[ reason ]?.[ productType ];
	}

	if ( ! documents ) {
		documents = buildRecommendedDocuments( [
			[
				'receipt',
				10,
				__( 'Order receipt', 'woocommerce' ),
				ORDER_RECEIPT_DESCRIPTION,
			],
			[
				'uncategorized_file',
				100,
				__( 'Other documents', 'woocommerce' ),
				OTHER_DOCUMENTS_DESCRIPTION,
			],
		] );
	}

	if (
		! documents.some(
			( document ) => document.key === 'customer_communication'
		)
	) {
		documents = [ ...documents, CUSTOMER_COMMUNICATION_DOCUMENT ];
	}

	return [ ...documents ].sort( ( firstDocument, secondDocument ) => {
		if ( firstDocument.order !== secondDocument.order ) {
			return firstDocument.order - secondDocument.order;
		}

		return firstDocument.label.localeCompare( secondDocument.label );
	} );
};

export const getRecommendedShippingDocumentFields = (
	reason?: string,
	productType?: string
): RecommendedDocumentField[] => {
	const fields = [
		buildRecommendedDocument(
			'shipping_documentation',
			0,
			__( 'Proof of shipping', 'woocommerce' ),
			__(
				'A receipt from the shipping carrier or a tracking number, for example.',
				'woocommerce'
			)
		),
	];

	if (
		reason === 'product_not_received' &&
		productType === 'physical_product'
	) {
		fields.push(
			buildRecommendedDocument(
				'customer_signature',
				1,
				__( 'Proof of delivery', 'woocommerce' ),
				__(
					'A confirmation that the product was delivered.',
					'woocommerce'
				)
			)
		);
	}

	return fields;
};

const shouldPreserveShippingDocumentation = (
	reason?: string,
	productType?: string,
	refundStatus?: string,
	duplicateStatus?: string
): boolean =>
	needsShipping( reason, productType ) ||
	getRecommendedDocumentFields( {
		reason,
		productType,
		refundStatus,
		duplicateStatus,
	} ).some( ( field ) => field.key === 'shipping_documentation' );

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
	const includeShippingEvidence = needsShipping(
		formState.reason,
		formState.productType
	);
	const includeShippingDocumentation = shouldPreserveShippingDocumentation(
		formState.reason,
		formState.productType,
		formState.refundStatus,
		formState.duplicateStatus
	);

	DOCUMENT_EVIDENCE_FIELDS.forEach( ( field ) => {
		evidence[ field ] = formState.evidence[ field ] || '';
	} );

	SHIPPING_EVIDENCE_FIELDS.forEach( ( field ) => {
		evidence[ field ] = includeShippingEvidence
			? formState.evidence[ field ] || ''
			: '';
	} );

	if ( ! includeShippingDocumentation ) {
		evidence.shipping_documentation = '';
	}

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
