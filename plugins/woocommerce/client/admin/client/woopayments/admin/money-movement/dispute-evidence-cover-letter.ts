/**
 * Internal dependencies
 */
import type { WooPaymentsDispute } from './types';
import {
	getRecommendedDocumentFields,
	getRecommendedShippingDocumentFields,
	needsShipping,
	type DocumentEvidenceField,
	type EvidenceField,
} from './dispute-evidence-fields';

type DisputeCoverLetterInput = {
	dispute: WooPaymentsDispute;
	merchantName?: string;
	merchantAddress?: string;
	merchantEmail?: string;
	merchantPhone?: string;
	bankName?: string;
	today?: string;
	productType?: string;
	evidence?: Partial< Record< EvidenceField, string > > &
		Record< string, string | undefined >;
	refundStatus?: string;
	duplicateStatus?: string;
};

const ATTACHMENT_LABELS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.split( '' );

const getEvidenceValue = (
	dispute: WooPaymentsDispute,
	evidence: DisputeCoverLetterInput[ 'evidence' ],
	field: DocumentEvidenceField
) => {
	const value = evidence?.[ field ] || dispute.evidence?.[ field ];

	return typeof value === 'string' ? value : '';
};

const getChargeId = ( dispute: WooPaymentsDispute ) => {
	if ( typeof dispute.charge === 'string' ) {
		return dispute.charge;
	}

	return (
		dispute.charge?.id ||
		dispute.charge_id ||
		dispute.transaction_id ||
		dispute.payment_intent ||
		'<Transaction ID>'
	);
};

const formatDate = ( value?: string | number ) => {
	if ( value === undefined || value === '' ) {
		return '<Transaction Date>';
	}

	const date =
		typeof value === 'number'
			? new Date( value * 1000 )
			: new Date( value );

	if ( Number.isNaN( date.getTime() ) ) {
		return String( value );
	}

	return new Intl.DateTimeFormat( 'en-US', {
		month: 'short',
		day: 'numeric',
		year: 'numeric',
	} ).format( date );
};

const getAttachmentLines = ( {
	dispute,
	productType,
	evidence,
	refundStatus,
	duplicateStatus,
}: DisputeCoverLetterInput ) => {
	const recommendedDocuments = getRecommendedDocumentFields( {
		reason: dispute.reason,
		productType,
		refundStatus,
		duplicateStatus,
		enhancedEligibilityTypes: dispute.enhanced_eligibility_types,
		evidence: dispute.evidence,
	} );
	const shippingDocuments = needsShipping( dispute.reason, productType )
		? getRecommendedShippingDocumentFields( dispute.reason, productType )
		: [];
	const labelsByField = [
		...recommendedDocuments,
		...shippingDocuments,
	].reduce< Partial< Record< DocumentEvidenceField, string > > >(
		( labels, document ) => ( {
			...labels,
			[ document.key ]: document.label,
		} ),
		{}
	);
	const uploadedLabels = Object.entries( labelsByField )
		.filter( ( [ field ] ) =>
			getEvidenceValue(
				dispute,
				evidence,
				field as DocumentEvidenceField
			)
		)
		.map( ( [ , label ] ) => label || '' )
		.filter( Boolean );

	if ( ! uploadedLabels.length ) {
		return [
			'<Attachment description> (Attachment A)',
			'<Attachment description> (Attachment B)',
		];
	}

	return uploadedLabels.map(
		( label, index ) =>
			`${ label } (Attachment ${
				ATTACHMENT_LABELS[ index ] || index + 1
			})`
	);
};

export const generateDisputeCoverLetter = ( {
	dispute,
	merchantName = '<Your Business Name>',
	merchantAddress = '<Business Address>',
	merchantEmail = '<business@email.com>',
	merchantPhone = '<Business Phone Number>',
	bankName = '<Bank Name>',
	today = formatDate( Math.floor( Date.now() / 1000 ) ),
	productType,
	evidence = {},
	refundStatus,
	duplicateStatus,
}: DisputeCoverLetterInput ): string => {
	const order = dispute.order;
	const disputeId = dispute.id || dispute.dispute_id || '<Case Number>';
	const transactionId = getChargeId( dispute );
	const customerName =
		order?.customer_name || dispute.customer_name || '<Customer Name>';
	const customerEmail = order?.customer_email || dispute.customer_email;
	const product = evidence.product_description || '<Product>';
	const orderNumber = order?.number ? `#${ order.number }` : order?.id;
	const attachmentLines = getAttachmentLines( {
		dispute,
		productType,
		evidence,
		refundStatus,
		duplicateStatus,
	} );
	const fulfillmentLines: string[] = [];

	if ( needsShipping( dispute.reason, productType ) ) {
		if ( evidence.shipping_carrier ) {
			fulfillmentLines.push(
				`Shipping carrier: ${ evidence.shipping_carrier }`
			);
		}
		if ( evidence.shipping_date ) {
			fulfillmentLines.push(
				`Shipping date: ${ evidence.shipping_date }`
			);
		}
		if ( evidence.shipping_tracking_number ) {
			fulfillmentLines.push(
				`Tracking number: ${ evidence.shipping_tracking_number }`
			);
		}
		if ( evidence.shipping_address ) {
			fulfillmentLines.push(
				`Shipping address: ${ evidence.shipping_address }`
			);
		}
	}

	if ( dispute.reason === 'credit_not_processed' && refundStatus ) {
		fulfillmentLines.push( `Refund status: ${ refundStatus }` );
	}
	if ( dispute.reason === 'duplicate' && duplicateStatus ) {
		fulfillmentLines.push( `Duplicate status: ${ duplicateStatus }` );
	}

	return `${ merchantName }
${ merchantAddress }
${ merchantEmail }
${ merchantPhone }
${ today }

To: ${ bankName }
Subject: Chargeback Dispute - Case #${ disputeId }

Dear Dispute Resolution Team,

We are submitting evidence in response to chargeback #${ disputeId } for transaction #${ transactionId } on ${ formatDate(
		dispute.created || dispute.date
	) }.

Our records indicate that the customer and legitimate cardholder, ${ customerName }, ordered ${ product }${
		orderNumber ? ` on order ${ orderNumber }` : ''
	}.${ customerEmail ? ` Customer email: ${ customerEmail }.` : '' }
${ fulfillmentLines.length ? `\n${ fulfillmentLines.join( '\n' ) }\n` : '' }
To support our case, we are providing the following documentation:
${ attachmentLines.join( '\n' ) }

Based on this information, we respectfully request that the chargeback be reversed. Please let us know if any further details are required.

Thank you,
${ merchantName }`;
};
