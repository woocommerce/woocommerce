/**
 * Internal dependencies
 */
import {
	PRODUCT_TYPE_OPTIONS,
	buildEvidencePayload,
	getRecommendedDocumentFields,
	needsShipping,
} from '../money-movement/dispute-evidence-fields';
import { generateDisputeCoverLetter } from '../money-movement/dispute-evidence-cover-letter';

const getFieldKeys = (
	input: Parameters< typeof getRecommendedDocumentFields >[ 0 ]
) => getRecommendedDocumentFields( input ).map( ( field ) => field.key );

describe( 'WooPayments dispute evidence helpers', () => {
	it( 'should expose reference-shaped product type option values and labels', () => {
		expect( PRODUCT_TYPE_OPTIONS ).toEqual( [
			{
				label: 'Physical products',
				value: 'physical_product',
			},
			{
				label: 'Digital products',
				value: 'digital_product_or_service',
			},
			{
				label: 'Offline service',
				value: 'offline_service',
			},
			{
				label: 'Booking/Reservation',
				value: 'booking_reservation',
			},
			{
				label: 'Event',
				value: 'event',
			},
			{
				label: 'Other',
				value: 'other',
			},
		] );
	} );

	it.each( [
		[ 'fraudulent', 'physical_product', true ],
		[ 'product_not_received', 'physical_product', true ],
		[ 'product_unacceptable', 'physical_product', true ],
		[ 'duplicate', 'physical_product', false ],
		[ 'subscription_canceled', 'physical_product', false ],
		[ 'credit_not_processed', 'physical_product', false ],
		[ 'fraudulent', 'digital_product_or_service', false ],
		[ 'fraudulent', 'offline_service', false ],
	] )(
		'should return %s shipping need for %s/%s',
		( reason, productType, expected ) => {
			expect( needsShipping( reason, productType ) ).toBe( expected );
		}
	);

	it( 'should recommend physical-product fraudulent dispute documents', () => {
		const fields = getRecommendedDocumentFields( {
			reason: 'fraudulent',
			productType: 'physical_product',
			evidence: {},
		} );

		expect( fields.map( ( field ) => field.key ) ).toEqual( [
			'receipt',
			'access_activity_log',
			'customer_communication',
			'customer_signature',
			'refund_policy',
			'uncategorized_file',
		] );
		expect( fields.map( ( field ) => field.label ) ).toEqual( [
			'Order receipt',
			'Prior undisputed transaction history',
			'Customer communication',
			"Customer's signature",
			'Refund policy',
			'Other documents',
		] );
		expect( fields.map( ( field ) => field.key ) ).not.toContain(
			'shipping_documentation'
		);
	} );

	it( 'should recommend source-backed Visa compliance evidence labels', () => {
		const fields = getRecommendedDocumentFields( {
			reason: 'fraudulent',
			productType: 'physical_product',
			enhancedEligibilityTypes: [ 'visa_compliance' ],
			evidence: {},
		} );

		expect( fields ).toEqual( [
			expect.objectContaining( {
				key: 'customer_communication',
				label: 'Upload evidence',
				description:
					'Submit any files you find relevant to this dispute.',
			} ),
			expect.objectContaining( {
				key: 'uncategorized_file',
				label: 'Other documents',
				description:
					'Any other relevant documents that will support your case.',
			} ),
		] );
	} );

	it( 'should recommend access evidence instead of shipping for digital fraudulent disputes', () => {
		const fields = getRecommendedDocumentFields( {
			reason: 'fraudulent',
			productType: 'digital_product_or_service',
			evidence: {},
		} );

		expect( fields.map( ( field ) => field.key ) ).toEqual( [
			'access_activity_log',
			'service_documentation',
			'customer_communication',
			'uncategorized_file',
		] );
		expect( fields.map( ( field ) => field.label ) ).toEqual( [
			'Login or usage records',
			'Prior undisputed transaction history',
			'Customer communication',
			'Other documents',
		] );
	} );

	it( 'should recommend duplicate-charge documents without shipping', () => {
		const fields = getRecommendedDocumentFields( {
			reason: 'duplicate',
			productType: 'physical_product',
			duplicateStatus: 'is_duplicate',
			evidence: {},
		} );

		expect( fields.map( ( field ) => field.key ) ).toEqual( [
			'receipt',
			'duplicate_charge_documentation',
			'customer_communication',
			'access_activity_log',
			'refund_policy',
			'cancellation_policy',
			'uncategorized_file',
		] );
		expect( fields[ 1 ].label ).toBe( 'Refund receipt' );
		expect( fields[ 3 ].label ).toBe( 'Proof of active subscription' );
	} );

	it( 'should recommend additional receipts when a duplicate dispute was not a duplicate', () => {
		const fields = getRecommendedDocumentFields( {
			reason: 'duplicate',
			productType: 'physical_product',
			duplicateStatus: 'is_not_duplicate',
			evidence: {},
		} );

		expect( fields.map( ( field ) => field.key ) ).toEqual( [
			'receipt',
			'duplicate_charge_documentation',
			'customer_communication',
			'refund_policy',
			'uncategorized_file',
		] );
		expect( fields[ 1 ].label ).toBe( 'Any additional receipts' );
	} );

	it( 'should recommend refund receipt without adding a non-Stripe evidence key', () => {
		const fields = getRecommendedDocumentFields( {
			reason: 'credit_not_processed',
			productType: 'physical_product',
			refundStatus: 'refund_has_been_issued',
			evidence: {},
		} );

		expect( fields.map( ( field ) => field.key ) ).toContain(
			'duplicate_charge_documentation'
		);
		expect( fields ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					key: 'duplicate_charge_documentation',
					label: 'Refund receipt',
				} ),
			] )
		);
		expect( fields.map( ( field ) => field.key ) ).not.toContain(
			'refund_receipt_documentation'
		);
	} );

	it( 'should recommend refund evidence for credit-not-processed disputes where no refund was owed', () => {
		expect(
			getFieldKeys( {
				reason: 'credit_not_processed',
				productType: 'physical_product',
				refundStatus: 'refund_was_not_owed',
				evidence: {},
			} )
		).toEqual( [
			'uncategorized_file',
			'customer_communication',
			'refund_policy',
			'service_documentation',
		] );
	} );

	it( 'should recommend return tracking for other credit-not-processed disputes where a refund was issued', () => {
		const fields = getRecommendedDocumentFields( {
			reason: 'credit_not_processed',
			productType: 'other',
			refundStatus: 'refund_has_been_issued',
			evidence: {},
		} );

		expect( fields.map( ( field ) => field.key ) ).toEqual( [
			'receipt',
			'shipping_documentation',
			'customer_communication',
		] );
		expect( fields.map( ( field ) => field.label ) ).toEqual( [
			'Refund receipt',
			'Return tracking',
			'Other documents',
		] );
	} );

	it( 'should recommend Visa compliance documents for noncompliant disputes', () => {
		expect(
			getFieldKeys( {
				reason: 'noncompliant',
				productType: 'physical_product',
				enhancedEligibilityTypes: [ 'visa_compliance' ],
				evidence: {},
			} )
		).toEqual( [ 'customer_communication', 'uncategorized_file' ] );
	} );

	it( 'should preserve the dispute evidence clearing contract', () => {
		const payload = buildEvidencePayload(
			{
				reason: 'fraudulent',
				productType: 'physical_product',
				metadata: {
					existing_key: 'existing_value',
				},
				existingEvidence: {
					billing_address: '123 Main St',
					receipt: 'old_receipt_file',
					shipping_carrier: 'Old carrier',
					enhanced_evidence: {
						visa_compliance: {
							fee_acknowledged: 'true',
						},
					},
				},
				evidence: {
					receipt: 'file_receipt',
					customer_communication: '',
					shipping_tracking_number: '1Z999',
					product_description: 'A custom hat.',
					customer_purchase_ip: '203.0.113.1',
				},
			},
			false
		);

		expect( payload ).toMatchObject( {
			submit: false,
			metadata: {
				existing_key: 'existing_value',
				__product_type: 'physical_product',
			},
			evidence: {
				billing_address: '123 Main St',
				receipt: 'file_receipt',
				customer_communication: '',
				customer_signature: '',
				refund_policy: '',
				duplicate_charge_documentation: '',
				cancellation_policy: '',
				cancellation_rebuttal: '',
				access_activity_log: '',
				service_documentation: '',
				shipping_documentation: '',
				uncategorized_file: '',
				shipping_carrier: '',
				shipping_date: '',
				shipping_tracking_number: '1Z999',
				shipping_address: '',
				product_description: 'A custom hat.',
				uncategorized_text: '',
				customer_purchase_ip: '203.0.113.1',
				enhanced_evidence: {
					visa_compliance: {
						fee_acknowledged: 'true',
					},
				},
			},
		} );
	} );

	it( 'should clear shipping evidence when shipping is not applicable', () => {
		const payload = buildEvidencePayload(
			{
				reason: 'fraudulent',
				productType: 'digital_product_or_service',
				existingEvidence: {
					shipping_carrier: 'Old carrier',
					shipping_tracking_number: '1Z999',
					shipping_documentation: 'file_old_shipping',
				},
				evidence: {
					shipping_carrier: 'Old carrier',
					shipping_tracking_number: '1Z999',
					shipping_documentation: 'file_old_shipping',
					product_description: 'Downloaded software.',
				},
			} as Parameters< typeof buildEvidencePayload >[ 0 ] & {
				reason: string;
			},
			false
		);

		expect( payload.evidence ).toMatchObject( {
			shipping_carrier: '',
			shipping_date: '',
			shipping_tracking_number: '',
			shipping_address: '',
			shipping_documentation: '',
			product_description: 'Downloaded software.',
		} );
	} );

	it( 'should preserve return tracking for other credit-not-processed disputes where a refund was issued', () => {
		const payload = buildEvidencePayload(
			{
				reason: 'credit_not_processed',
				productType: 'other',
				refundStatus: 'refund_has_been_issued',
				existingEvidence: {},
				evidence: {
					shipping_documentation: 'file_return_tracking',
					product_description: 'Custom order.',
				},
			} as Parameters< typeof buildEvidencePayload >[ 0 ] & {
				reason: string;
			},
			false
		);

		expect( payload.evidence ).toMatchObject( {
			shipping_documentation: 'file_return_tracking',
			shipping_carrier: '',
			shipping_date: '',
			shipping_tracking_number: '',
			shipping_address: '',
		} );
	} );

	it( 'should clear stale return tracking for other credit-not-processed disputes where no refund was owed', () => {
		const payload = buildEvidencePayload(
			{
				reason: 'credit_not_processed',
				productType: 'other',
				refundStatus: 'refund_was_not_owed',
				existingEvidence: {
					shipping_documentation: 'file_return_tracking',
				},
				evidence: {
					shipping_documentation: 'file_return_tracking',
					product_description: 'Custom order.',
				},
			} as Parameters< typeof buildEvidencePayload >[ 0 ] & {
				reason: string;
			},
			false
		);

		expect( payload.evidence ).toMatchObject( {
			shipping_documentation: '',
			shipping_carrier: '',
			shipping_date: '',
			shipping_tracking_number: '',
			shipping_address: '',
		} );
	} );

	it( 'should generate deterministic formal cover letter content from available facts', () => {
		const fixture = {
			dispute: {
				id: 'dp_test',
				reason: 'fraudulent',
				amount: 5000,
				currency: 'usd',
				charge: {
					id: 'ch_test',
				},
				created: 1771423200,
				order: {
					id: 123,
					number: '1001',
					customer_name: 'Ada Lovelace',
					customer_email: 'ada@example.com',
				},
			},
			merchantName: 'WooCommerce Test Store',
			merchantAddress: '1 Market Street, San Francisco, CA',
			merchantEmail: 'support@example.com',
			merchantPhone: '+1 555 123 4567',
			bankName: 'Test Bank',
			productType: 'physical_product',
			evidence: {
				receipt: 'file_receipt',
				access_activity_log: 'file_history',
				customer_communication: 'file_customer_messages',
				product_description: 'Custom roasted coffee beans.',
				customer_purchase_ip: '203.0.113.10',
				shipping_carrier: 'UPS',
				shipping_date: '2026-06-01',
				shipping_tracking_number: '1Z999',
				shipping_address: '1 Market Street',
			},
			refundStatus:
				'No refund was issued because the order was fulfilled.',
			duplicateStatus: 'This order was charged once.',
		};

		const firstLetter = generateDisputeCoverLetter( fixture );
		const secondLetter = generateDisputeCoverLetter( fixture );

		expect( firstLetter ).toBe( secondLetter );
		expect( firstLetter ).toContain( 'WooCommerce Test Store' );
		expect( firstLetter ).toContain( '1 Market Street, San Francisco, CA' );
		expect( firstLetter ).toContain( 'support@example.com' );
		expect( firstLetter ).toContain( '+1 555 123 4567' );
		expect( firstLetter ).toContain( 'To: Test Bank' );
		expect( firstLetter ).toContain(
			'Subject: Chargeback Dispute - Case #dp_test'
		);
		expect( firstLetter ).toContain( 'Dear Dispute Resolution Team,' );
		expect( firstLetter ).toContain(
			'We are submitting evidence in response to chargeback #dp_test for transaction #ch_test'
		);
		expect( firstLetter ).toContain(
			'Our records indicate that the customer and legitimate cardholder, Ada Lovelace, ordered Custom roasted coffee beans.'
		);
		expect( firstLetter ).toContain(
			'To support our case, we are providing the following documentation:'
		);
		expect( firstLetter ).toContain( 'Order receipt (Attachment A)' );
		expect( firstLetter ).toContain(
			'Prior undisputed transaction history (Attachment B)'
		);
		expect( firstLetter ).toContain(
			'Customer communication (Attachment C)'
		);
		expect( firstLetter ).toContain( 'Thank you,' );
		expect( firstLetter ).toContain( 'WooCommerce Test Store' );
		expect( firstLetter ).toContain( 'Custom roasted coffee beans.' );
		expect( firstLetter ).toContain( 'Shipping carrier: UPS' );
		expect( firstLetter ).toContain( 'Tracking number: 1Z999' );
		expect( firstLetter ).not.toContain( 'Refund status:' );
		expect( firstLetter ).not.toContain( 'Duplicate status:' );
	} );

	it( 'should omit stale shipping details from cover letters when shipping is not applicable', () => {
		const letter = generateDisputeCoverLetter( {
			dispute: {
				id: 'dp_test',
				reason: 'fraudulent',
				order: {
					id: 123,
					number: '1001',
				},
			},
			productType: 'digital_product_or_service',
			evidence: {
				product_description: 'Downloaded software.',
				shipping_carrier: 'Old carrier',
				shipping_tracking_number: '1Z999',
			},
		} );

		expect( letter ).toContain( 'Downloaded software.' );
		expect( letter ).not.toContain( 'Old carrier' );
		expect( letter ).not.toContain( '1Z999' );
	} );
} );
