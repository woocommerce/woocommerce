/**
 * Internal dependencies
 */
import { getSplitGateways, getIsWCPayOrOtherCategoryDoneSetup } from '../utils';

const wcpay = {
	id: 'woocommerce_payments:something',
	plugins: [ 'woocommerce-payments' ],
	installed: false,
	needsSetup: true,
};

const wcpayBnpl = {
	id: 'woocommerce_payments:bnpl',
	plugins: [ 'woocommerce-payments' ],
	installed: true,
	needsSetup: false,
};

const cod = {
	is_offline: true,
};

const bacs = {
	is_offline: true,
};

const paypal = {
	id: 'paypal',
	category_other: [ 'CA' ],
	category_additional: [ 'CA' ],
	recommendation_priority: 2,
};

const stripe = {
	id: 'stripe',
	category_other: [ 'US' ],
	category_additional: [ 'US' ],
	recommendation_priority: 1,
};

const klarna = {
	id: 'klarna',
	category_other: [ '' ],
	category_additional: [ 'US' ],
	recommendation_priority: 3,
};

const amazonPay = {
	id: 'amazonPay',
	category_other: [ '' ],
	category_additional: [ 'US' ],
	recommendation_priority: 4,
};

describe( 'getSplitGateways()', () => {
	it( 'Returns WCPay gateways', () => {
		const [ wcpayGateways, , , wcpayBnplGateway ] = getSplitGateways(
			[ wcpay, cod, paypal, wcpayBnpl ],
			'US',
			true
		);
		expect( wcpayGateways ).toEqual( [ wcpay ] );
		expect( wcpayBnplGateway ).toEqual( [ wcpayBnpl ] );
	} );

	it( 'Returns offline gateways', () => {
		const [ , offlineGateways ] = getSplitGateways( [
			wcpay,
			cod,
			bacs,
			paypal,
		] );
		expect( offlineGateways ).toEqual( [ cod, bacs ] );
	} );

	it( 'Excludes enabled gateways', () => {
		const [ , , mainList ] = getSplitGateways( [
			wcpay,
			cod,
			bacs,
			{
				...paypal,
				enabled: true,
			},
		] );
		expect( mainList ).toEqual( [] );
	} );

	describe( 'Main gateway list with eligible WCPay', () => {
		it( 'Returns only "other" category gateways when WCPay or "other" category gateway isnt set up', () => {
			const [ , , mainList ] = getSplitGateways(
				[ wcpay, cod, bacs, paypal, stripe, klarna ],
				'US',
				true,
				false
			);
			expect( mainList ).toEqual( [ stripe ] );
		} );
		it( 'Returns only "additional" category gateways when WCPay or "other" category gateway is set up', () => {
			const [ , , mainList ] = getSplitGateways(
				[ wcpay, cod, bacs, paypal, stripe, klarna ],
				'US',
				true,
				true
			);
			expect( mainList ).toEqual( [ stripe, klarna ] );
		} );
		it( 'Returns "additional" category gateways in recommended order', () => {
			const [ , , mainList ] = getSplitGateways(
				[ wcpay, cod, bacs, amazonPay, paypal, stripe, klarna ],
				'US',
				true,
				true
			);
			expect( mainList ).toEqual( [ stripe, klarna, amazonPay ] );
		} );
	} );

	describe( 'Main gateway list with ineligible WCPay', () => {
		it( 'Returns "other" gateways when no "other" gateway is setup', () => {
			const [ , , mainList ] = getSplitGateways(
				[ wcpay, cod, bacs, paypal, stripe, klarna ],
				'US',
				false,
				false
			);
			expect( mainList ).toEqual( [ stripe ] );
		} );
		it( 'Returns only "additional" category gateways when "other" gateways is set up', () => {
			const [ , , mainList ] = getSplitGateways(
				[ wcpay, cod, bacs, paypal, stripe, klarna ],
				'US',
				false,
				true
			);
			expect( mainList ).toEqual( [ stripe, klarna ] );
		} );
	} );
} );

describe( 'getIsWCPayOrOtherCategoryDoneSetup()', () => {
	it( 'False when nothing is set up', () => {
		expect(
			getIsWCPayOrOtherCategoryDoneSetup( [ wcpay, cod, paypal ] )
		).toEqual( false );
	} );
	it( 'True when WCPay is set up', () => {
		expect(
			getIsWCPayOrOtherCategoryDoneSetup( [
				{ ...wcpay, installed: true, needsSetup: false },
			] )
		).toEqual( true );
	} );
	it( 'True when "other" category gateway is set up', () => {
		expect(
			getIsWCPayOrOtherCategoryDoneSetup(
				[ { ...stripe, installed: true, needsSetup: false } ],
				'US'
			)
		).toEqual( true );
	} );
} );
