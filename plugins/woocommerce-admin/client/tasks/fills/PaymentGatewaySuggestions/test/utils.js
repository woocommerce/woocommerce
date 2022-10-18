/**
 * Internal dependencies
 */
import { getSplitGateways, getIsWCPayOrOtherCategoryDoneSetup } from '../utils';

const wcpay = {
	plugins: [ 'woocommerce-payments' ],
	installed: false,
	needsSetup: true,
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
};

const stripe = {
	id: 'stripe',
	category_other: [ 'US' ],
	category_additional: [ 'US' ],
};

const klarna = {
	id: 'klarna',
	category_other: [ '' ],
	category_additional: [ 'US' ],
};

describe( 'getSplitGateways()', () => {
	it( 'Returns WCPay gateways', () => {
		const [ wcpayGateways ] = getSplitGateways( [ wcpay, cod, paypal ] );
		expect( wcpayGateways ).toEqual( [ wcpay ] );
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
		const [ , , additionalGateways ] = getSplitGateways( [
			wcpay,
			cod,
			bacs,
			{
				...paypal,
				enabled: true,
			},
		] );
		expect( additionalGateways ).toEqual( [] );
	} );

	describe( 'Additional gateways with eligible WCPay', () => {
		it( 'Returns only "other" category gateways when WCPay or "other" category gateway isnt set up', () => {
			const [ , , additionalGateways ] = getSplitGateways(
				[ wcpay, cod, bacs, paypal, stripe, klarna ],
				'US',
				true,
				false
			);
			expect( additionalGateways ).toEqual( [ stripe ] );
		} );
		it( 'Returns only "additional" category gateways when WCPay or "other" category gateway is set up', () => {
			const [ , , additionalGateways ] = getSplitGateways(
				[ wcpay, cod, bacs, paypal, stripe, klarna ],
				'US',
				true,
				true
			);
			expect( additionalGateways ).toEqual( [ stripe, klarna ] );
		} );
	} );

	describe( 'Additional gateways with ineligible WCPay', () => {
		it( 'Returns all gateways when "other" gateways isnt set up', () => {
			const [ , , additionalGateways ] = getSplitGateways(
				[ wcpay, cod, bacs, paypal, stripe, klarna ],
				'US',
				false,
				false
			);
			expect( additionalGateways ).toEqual( [ paypal, stripe, klarna ] );
		} );
		it( 'Returns only "additional" category gateways when "other" gateways is set up', () => {
			const [ , , additionalGateways ] = getSplitGateways(
				[ wcpay, cod, bacs, paypal, stripe, klarna ],
				'US',
				false,
				true
			);
			expect( additionalGateways ).toEqual( [ stripe, klarna ] );
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
