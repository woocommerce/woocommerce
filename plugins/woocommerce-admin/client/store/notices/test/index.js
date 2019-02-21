/** @format */
/**
 * Internal dependencies
 */
import actions from '../actions';
import { DEFAULT_STATE, testNotice } from './fixtures';
import reducers from '../reducers';
import selectors from '../selectors';

describe( 'actions', () => {
	test( 'should create an add notice action', () => {
		const expectedAction = {
			type: 'ADD_NOTICE',
			notice: testNotice,
		};

		expect( actions.addNotice( testNotice ) ).toEqual( expectedAction );
	} );
} );

describe( 'selectors', () => {
	const notices = [ testNotice ];
	const updatedState = { ...DEFAULT_STATE, ...{ notices } };

	test( 'should return an emtpy initial state', () => {
		expect( selectors.getNotices( DEFAULT_STATE ) ).toEqual( [] );
	} );

	test( 'should have an array length matching number of notices', () => {
		expect( selectors.getNotices( updatedState ).length ).toEqual( 1 );
	} );

	test( 'should return the message content', () => {
		expect( selectors.getNotices( updatedState )[ 0 ].message ).toEqual( 'Test notice' );
	} );
} );

describe( 'reducers', () => {
	test( 'should return an emtpy initial state', () => {
		expect( reducers.notices( DEFAULT_STATE.notices, {} ) ).toEqual( [] );
	} );

	test( 'should return the added notice', () => {
		expect(
			reducers.notices( DEFAULT_STATE.notices, { type: 'ADD_NOTICE', notice: testNotice } )
		).toEqual( [ testNotice ] );
	} );

	const initialNotices = [ { message: 'Initial notice' } ];
	test( 'should return the initial notice and the added notice', () => {
		expect(
			reducers.notices( initialNotices, { type: 'ADD_NOTICE', notice: testNotice } )
		).toEqual( [ ...initialNotices, testNotice ] );
	} );
} );
