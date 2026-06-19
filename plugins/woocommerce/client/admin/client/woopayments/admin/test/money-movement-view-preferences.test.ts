/**
 * Internal dependencies
 */
import {
	getMoneyMovementViewPreferences,
	mergeMoneyMovementViewPreferences,
	setMoneyMovementViewPreferences,
} from '../money-movement/view-preferences';

describe( 'WooPayments money movement view preferences', () => {
	beforeEach( () => {
		window.localStorage.clear();
	} );

	it( 'persists DataViews fields and layout outside the REST query state', () => {
		setMoneyMovementViewPreferences( 'transactions', {
			fields: [ 'type', 'amount' ],
			layout: {
				table: {
					density: 'compact',
				},
			},
			showTitle: false,
			titleField: 'type',
		} );

		expect(
			getMoneyMovementViewPreferences( 'transactions' )
		).toMatchObject( {
			fields: [ 'type', 'amount' ],
			layout: {
				table: {
					density: 'compact',
				},
			},
			showTitle: false,
			titleField: 'type',
		} );
	} );

	it( 'falls back to query-owned DataViews state when local storage is invalid', () => {
		window.localStorage.setItem(
			'woocommerce_woopayments_money_movement_view_transactions',
			'{invalid'
		);

		expect(
			mergeMoneyMovementViewPreferences(
				{
					type: 'table',
					fields: [ 'date', 'type', 'customer', 'amount' ],
					layout: {},
					page: 2,
					perPage: 50,
					search: 'Ada',
				},
				getMoneyMovementViewPreferences( 'transactions' )
			)
		).toEqual( {
			type: 'table',
			fields: [ 'date', 'type', 'customer', 'amount' ],
			layout: {},
			page: 2,
			perPage: 50,
			search: 'Ada',
		} );
	} );
} );
