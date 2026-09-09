/**
 * Internal dependencies
 */
import { persistSettingsForGroup } from '../actions';
import TYPES from '../action-types';

const GROUP = 'wc_admin';
const DIRTY_KEYS = [ 'wcAdminSettings' ];
const DIRTY_DATA = {
	wcAdminSettings: { woocommerce_default_date_range: 'period=month' },
};

// The step at which the generator yields the batch request. Everything before
// it is `setIsRequesting` and the two `resolveSelect` controls.
const API_FETCH_STEP = 3;

type YieldedAction = Record< string, unknown > & { type: string };

const OWN_TYPES: string[] = Object.values( TYPES );

/**
 * Drives persistSettingsForGroup to completion, answering each control with a
 * canned value, and returns the actions it dispatched along the way.
 *
 * @param options           What the batch request should do.
 * @param options.apiResult Value the request resolves to.
 * @param options.apiError  Error the request rejects with instead.
 */
const runPersist = ( {
	apiResult,
	apiError,
}: {
	apiResult?: unknown;
	apiError?: Error;
} ) => {
	const replies: unknown[] = [ undefined, DIRTY_KEYS, DIRTY_DATA ];
	const yielded: YieldedAction[] = [];
	const generator = persistSettingsForGroup( GROUP );

	let step = generator.next();
	let index = 0;
	let thrown: unknown;

	while ( ! step.done ) {
		yielded.push( step.value as YieldedAction );
		try {
			if ( index === API_FETCH_STEP ) {
				step = apiError
					? generator.throw( apiError )
					: generator.next( apiResult );
			} else {
				step = generator.next( replies[ index ] );
			}
		} catch ( e ) {
			thrown = e;
			break;
		}
		index++;
	}

	return {
		thrown,
		// Controls (resolveSelect, apiFetch) are not dispatched, so only the
		// store's own actions are relevant to the order under test.
		actions: yielded.filter( ( action ) =>
			OWN_TYPES.includes( action.type )
		),
	};
};

describe( 'persistSettingsForGroup', () => {
	it( 'clears the stale error and the dirty keys before it stops requesting', () => {
		const { actions } = runPersist( { apiResult: { update: [] } } );

		// The Analytics settings screen decides between the success and the
		// error notice on the `isRequesting` true -> false transition, so that
		// transition has to come last.
		expect( actions.map( ( action ) => action.type ) ).toEqual( [
			TYPES.SET_IS_REQUESTING,
			TYPES.CLEAR_ERROR_FOR_GROUP,
			TYPES.CLEAR_IS_DIRTY,
			TYPES.SET_IS_REQUESTING,
		] );
		expect( actions[ 0 ] ).toEqual( {
			type: TYPES.SET_IS_REQUESTING,
			group: GROUP,
			isRequesting: true,
		} );
		expect( actions[ 3 ] ).toEqual( {
			type: TYPES.SET_IS_REQUESTING,
			group: GROUP,
			isRequesting: false,
		} );
	} );

	it( 'records the error before it stops requesting when the save fails', () => {
		const apiError = new Error( 'Nope.' );
		const { actions, thrown } = runPersist( { apiError } );

		expect( actions.map( ( action ) => action.type ) ).toEqual( [
			TYPES.SET_IS_REQUESTING,
			TYPES.UPDATE_ERROR_FOR_GROUP,
			TYPES.SET_IS_REQUESTING,
		] );
		expect( actions[ 1 ] ).toMatchObject( {
			group: GROUP,
			data: null,
			error: apiError,
		} );
		expect( actions[ 2 ] ).toEqual( {
			type: TYPES.SET_IS_REQUESTING,
			group: GROUP,
			isRequesting: false,
		} );
		expect( thrown ).toBe( apiError );
	} );
} );
