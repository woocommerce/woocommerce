/**
 * External dependencies
 */
import { dispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { triggerExitPageCesSurvey } from '../customer-effort-score-exit-page';

jest.mock( '@woocommerce/data', () => ( {
	OPTIONS_STORE_NAME: 'options',
} ) );
jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useSelect: jest.fn(),
	dispatch: jest.fn(),
	resolveSelect: jest.fn().mockReturnValue( {
		getOption: jest.fn().mockResolvedValue( 'yes' ),
	} ),
} ) );

describe( 'triggerExitPageCesSurvey', () => {
	const addCESSurveyMock = jest.fn();
	beforeEach( () => {
		jest.clearAllMocks();
		( dispatch as jest.Mock ).mockReturnValue( {
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
