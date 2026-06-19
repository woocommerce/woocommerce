/**
 * External dependencies
 */
import { dispatch } from '@wordpress/data';

const mockCreateErrorNotice = jest.fn();
const mockCreateSuccessNotice = jest.fn();
const mockInvalidateResolution = jest.fn();
const mockRegister = jest.fn();

jest.mock( '@wordpress/data', () => ( {
	combineReducers: jest.fn( ( reducers ) => reducers ),
	createReduxStore: jest.fn( ( name, config ) => ( {
		name,
		...config,
	} ) ),
	dispatch: jest.fn( ( storeName: string ) => {
		if ( storeName === 'core/notices' ) {
			return {
				createErrorNotice: mockCreateErrorNotice,
				createSuccessNotice: mockCreateSuccessNotice,
			};
		}

		return {
			invalidateResolution: mockInvalidateResolution,
		};
	} ),
	register: ( ...args: unknown[] ) => mockRegister( ...args ),
	useDispatch: jest.fn(),
	useSelect: jest.fn(),
} ) );

describe( 'WooPayments PM promotions data store', () => {
	beforeEach( () => {
		mockCreateErrorNotice.mockReset();
		mockCreateSuccessNotice.mockReset();
		mockInvalidateResolution.mockReset();
		mockRegister.mockReset();
		( dispatch as jest.Mock ).mockClear();
	} );

	it( 'registers the PM promotions store name used by WooPayments components', async () => {
		const { STORE_NAME, store } = await import( '../store' );

		expect( STORE_NAME ).toBe( 'wc/payments/pmPromotions' );
		expect( store.name ).toBe( 'wc/payments/pmPromotions' );
		expect( mockRegister ).toHaveBeenCalledWith( store );
	} );

	it( 'returns a flat PM promotions array from state', async () => {
		const promotion = {
			id: 'card-promo__badge',
			promo_id: 'card-promo',
			payment_method: 'card',
			type: 'badge',
			title: 'Limited time',
		};
		const { getPmPromotions } = await import( '../selectors' );

		expect(
			getPmPromotions( {
				promotions: {
					data: [ promotion ],
				},
			} )
		).toEqual( [ promotion ] );
		expect( getPmPromotions( {} ) ).toEqual( [] );
	} );

	it( 'resolves PM promotions from the WooPayments endpoint', async () => {
		const promotion = {
			id: 'card-promo__badge',
			promo_id: 'card-promo',
			payment_method: 'card',
			type: 'badge',
			title: 'Limited time',
		};
		const { getPmPromotions } = await import( '../resolvers' );
		const resolver = getPmPromotions();

		expect( resolver.next().value ).toEqual( {
			type: 'API_FETCH',
			request: {
				path: '/wc/v3/payments/pm-promotions',
				method: 'GET',
			},
		} );
		expect( resolver.next( [ promotion ] ).value ).toEqual( {
			type: 'SET_PM_PROMOTIONS',
			promotions: [ promotion ],
		} );
		expect( resolver.next().done ).toBe( true );
	} );

	it( 'does not set PM promotions when the resolver receives a malformed response', async () => {
		const { getPmPromotions } = await import( '../resolvers' );
		const resolver = getPmPromotions();

		resolver.next();
		const result = resolver.next( { data: { id: 'card' } } );

		expect( result.value ).toBeUndefined();
		expect( mockCreateErrorNotice ).toHaveBeenCalledWith(
			'Error retrieving payment method promotions.'
		);
		expect( resolver.next().done ).toBe( true );
	} );

	it( 'posts activation requests to the PM promotion activate endpoint', async () => {
		const { activatePmPromotion } = await import( '../actions' );
		const action = activatePmPromotion( 'card' );

		expect( action.next().value ).toEqual( {
			type: 'API_FETCH',
			request: {
				path: '/wc/v3/payments/pm-promotions/card/activate',
				method: 'POST',
			},
		} );

		const result = action.next( {} );

		expect( mockInvalidateResolution ).toHaveBeenCalledWith(
			'getPmPromotions',
			[]
		);
		expect( mockCreateSuccessNotice ).toHaveBeenCalledWith(
			'Promotion activated successfully.'
		);
		expect( result.value ).toBe( true );
		expect( result.done ).toBe( true );
	} );

	it( 'posts dismissal requests to the PM promotion dismiss endpoint', async () => {
		const { dismissPmPromotion } = await import( '../actions' );
		const action = dismissPmPromotion( 'card' );

		expect( action.next().value ).toEqual( {
			type: 'API_FETCH',
			request: {
				path: '/wc/v3/payments/pm-promotions/card/dismiss',
				method: 'POST',
			},
		} );

		const result = action.next( {} );

		expect( mockInvalidateResolution ).toHaveBeenCalledWith(
			'getPmPromotions',
			[]
		);
		expect( mockCreateSuccessNotice ).toHaveBeenCalledWith(
			'Promotion dismissed.'
		);
		expect( result.value ).toBe( true );
		expect( result.done ).toBe( true );
	} );
} );
