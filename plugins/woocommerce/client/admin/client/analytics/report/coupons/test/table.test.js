/**
 * Internal dependencies
 */
import CouponsReportTable from '../table';

const mockAdminSettings = {};

jest.mock( '~/utils/admin-settings', () => ( {
	getAdminSetting: ( key, fallback ) => mockAdminSettings[ key ] ?? fallback,
} ) );

describe( 'CouponsReportTable getCouponType', () => {
	const table = new CouponsReportTable();

	beforeEach( () => {
		mockAdminSettings.couponTypes = {
			percent: 'Percentage discount',
			custom_type: 'Custom discount',
		};
	} );

	it( 'keeps the core label for a built-in discount type', () => {
		expect( table.getCouponType( 'percent' ) ).toBe( 'Percentage' );
	} );

	it( 'uses the admin setting label for a discount type added by an extension', () => {
		expect( table.getCouponType( 'custom_type' ) ).toBe(
			'Custom discount'
		);
	} );

	it( 'returns N/A for a discount type without a label', () => {
		expect( table.getCouponType( 'unknown_type' ) ).toBe( 'N/A' );
	} );

	it( 'falls back to the core labels when the admin setting is missing', () => {
		delete mockAdminSettings.couponTypes;

		expect( table.getCouponType( 'fixed_cart' ) ).toBe( 'Fixed cart' );
		expect( table.getCouponType( 'custom_type' ) ).toBe( 'N/A' );
	} );
} );
