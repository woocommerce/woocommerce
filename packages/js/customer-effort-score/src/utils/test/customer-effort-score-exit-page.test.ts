import { beforeEach, describe, expect, it, vi, type Mock } from 'vitest';

/**
 * External dependencies
 */
import { dispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { triggerExitPageCesSurvey } from '../customer-effort-score-exit-page';
vi.mock( '@woocommerce/data', () => {
	const mock = {
		optionsStore: 'options',
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@wordpress/data', async () => {
	const mock = {
		...( await vi.importActual( '@wordpress/data' ) ),
		useSelect: vi.fn(),
		dispatch: vi.fn(),
		resolveSelect: vi.fn().mockReturnValue( {
			getOption: vi.fn().mockResolvedValue( 'yes' ),
		} ),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
describe( 'triggerExitPageCesSurvey', () => {
	const addCESSurveyMock = vi.fn();
	beforeEach( () => {
		vi.clearAllMocks();
		( dispatch as Mock ).mockReturnValue( {
			addCesSurvey: addCESSurveyMock,
		} );
	} );
	it( 'should not trigger addCESSurvey if local storage is empty', () => {
		triggerExitPageCesSurvey();
		expect( addCESSurveyMock ).not.toHaveBeenCalled();
	} );
	it( 'should not trigger addCESSurvey if copy does not exist for item, but clear localStorage still', () => {
		window.localStorage.setItem(
			'customer-effort-score-exit-page',
			JSON.stringify( [ 'random-id' ] )
		);
		triggerExitPageCesSurvey();
		expect( addCESSurveyMock ).not.toHaveBeenCalled();
		const list = window.localStorage.getItem(
			'customer-effort-score-exit-page'
		);
		expect( list ).toEqual( '[]' );
	} );
	it( 'should trigger addCESSurvey if copy does exist for item, and clear localStorage still', () => {
		window.localStorage.setItem(
			'customer-effort-score-exit-page',
			JSON.stringify( [ 'new_product' ] )
		);
		triggerExitPageCesSurvey();
		expect( addCESSurveyMock ).toHaveBeenCalled();
		const list = window.localStorage.getItem(
			'customer-effort-score-exit-page'
		);
		expect( list ).toEqual( '[]' );
	} );
} );
