/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { createElement } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { CustomerEffortScoreModalContainer } from '..';

jest.mock( '@wordpress/data', () => {
	const originalModule = jest.requireActual( '@wordpress/data' );

	return {
		__esModule: true,
		...originalModule,
		useDispatch: jest.fn(),
		useSelect: jest.fn(),
	};
} );

jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn(),
} ) );

const mockedUseDispatch = useDispatch as jest.Mock;
const mockedUseSelect = useSelect as jest.Mock;

describe( 'CustomerEffortScoreModalContainer', () => {
	beforeEach( () => {
		mockedUseSelect.mockReturnValue( {
			storeAgeInWeeks: 1,
			resolving: false,
			visibleCESModalData: null,
		} );
	} );

	afterEach( () => {
		jest.clearAllMocks();
	} );

	it( 'should not throw when the core/notices store dispatch is null', () => {
		// Simulate the bug condition: useDispatch( 'core/notices' ) returns
		// null because the WordPress notices script dependency is missing
		// (reproduced when the customer-effort-score-tracks feature is
		// disabled and the notices store isn't registered).
		mockedUseDispatch.mockImplementation( ( storeKey: string ) => {
			if ( storeKey === 'core/notices' ) {
				return null;
			}
			return { hideCesModal: jest.fn() };
		} );

		expect( () =>
			render( <CustomerEffortScoreModalContainer /> )
		).not.toThrow();
	} );

	it( 'should render null when no visible CES modal data', () => {
		mockedUseDispatch.mockImplementation( ( storeKey: string ) => {
			if ( storeKey === 'core/notices' ) {
				return { createSuccessNotice: jest.fn() };
			}
			return { hideCesModal: jest.fn() };
		} );

		const { container } = render( <CustomerEffortScoreModalContainer /> );
		expect( container.firstChild ).toBeNull();
	} );
} );
