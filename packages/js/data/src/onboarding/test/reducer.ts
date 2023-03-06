/**
 * @jest-environment node
 */

/**
 * Internal dependencies
 */
import reducer, { defaultState } from '../reducer';
import TYPES from '../action-types';

const profileItems = {
	business_extensions: [],
	completed: false,
	industry: null,
	number_employees: null,
	other_platform: null,
	other_platform_name: '',
	product_count: null,
	product_types: null,
	revenue: null,
	selling_venues: null,
	setup_client: false,
	skipped: true,
	theme: null,
	wccom_connected: null,
	is_agree_marketing: null,
	store_email: null,
};

const paymentMethods = [
	{
		id: '',
		content: '',
		plugins: [],
		title: '',
		category_additional: [],
		category_other: [],
		image: '',
	},
];

describe( 'plugins reducer', () => {
	it( 'should return a default state', () => {
		// @ts-expect-error - we're testing the default state
		const state = reducer( undefined, {} );
		expect( state ).toEqual( defaultState );
	} );

	it( 'should handle SET_PROFILE_ITEMS', () => {
		const state = reducer(
			{
				// @ts-expect-error - we're only testing profileItems
				profileItems,
			},
			{
				type: TYPES.SET_PROFILE_ITEMS,
				profileItems: { is_agree_marketing: true },
			}
		);

		expect( state.profileItems.is_agree_marketing ).toBe( true );
	} );

	it( 'should handle SET_PROFILE_ITEMS with replace', () => {
		const state = reducer(
			{
				// @ts-expect-error - we're only testing profileItems
				profileItems,
			},
			{
				type: TYPES.SET_PROFILE_ITEMS,
				profileItems: { is_agree_marketing: true },
				replace: true,
			}
		);

		expect( state.profileItems ).not.toHaveProperty( 'store_email' );
		expect( state.profileItems ).toHaveProperty( 'is_agree_marketing' );
		expect( state.profileItems.is_agree_marketing ).toBe( true );
	} );

	it( 'should handle GET_PAYMENT_METHODS_SUCCESS', () => {
		const state = reducer(
			// @ts-expect-error - we're only testing paymentMethods
			{
				paymentMethods,
			},
			{
				type: TYPES.GET_PAYMENT_METHODS_SUCCESS,
				paymentMethods: [ { image_72x72: 'changed' } ],
			}
		);

		expect( state.paymentMethods[ 0 ] ).not.toHaveProperty(
			'previousItem'
		);
		expect( state.paymentMethods[ 0 ] ).toHaveProperty( 'image_72x72' );
		expect( state.paymentMethods[ 0 ].image_72x72 ).toBe( 'changed' );
	} );

	it( 'should handle SET_ERROR', () => {
		const state = reducer( defaultState, {
			type: TYPES.SET_ERROR,
			selector: 'getProfileItems',
			error: { code: 'error' },
		} );

		/* eslint-disable dot-notation */
		// @ts-expect-error we're asserting error properties
		expect( state.errors[ 'getProfileItems' ].code ).toBe( 'error' );
		/* eslint-enable dot-notation */
	} );

	it( 'should handle SET_IS_REQUESTING', () => {
		const state = reducer( defaultState, {
			type: TYPES.SET_IS_REQUESTING,
			selector: 'updateProfileItems',
			isRequesting: true,
		} );

		/* eslint-disable dot-notation */
		expect( state.requesting[ 'updateProfileItems' ] ).toBeTruthy();
		/* eslint-enable dot-notation */
	} );
} );
