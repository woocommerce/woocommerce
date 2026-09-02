/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { useSelect } from '@wordpress/data';
import { useUser } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { TaxRecommendations } from '../tax-recommendations-wrapper';

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useSelect: jest.fn(),
} ) );

jest.mock( '@woocommerce/data', () => ( {
	...jest.requireActual( '@woocommerce/data' ),
	useUser: jest.fn(),
} ) );

jest.mock( '@wordpress/element', () => ( {
	...jest.requireActual( '@wordpress/element' ),
	Suspense: () => <div>Recommended tax solutions</div>,
} ) );

describe( 'TaxRecommendations', () => {
	beforeEach( () => {
		( useSelect as jest.Mock ).mockImplementation( ( fn ) =>
			fn( () => ( {
				getOption: () => 'yes',
				hasFinishedResolution: () => true,
			} ) )
		);

		( useUser as jest.Mock ).mockReturnValue( {
			currentUserCan: () => true,
		} );
	} );

	it( 'should not render when page is not wc-settings', () => {
		const { queryByText } = render(
			<TaxRecommendations
				page="wc-admin"
				tab="tax"
				section={ undefined }
			/>
		);

		expect(
			queryByText( 'Recommended tax solutions' )
		).not.toBeInTheDocument();
	} );

	it( 'should not render when tab is not tax', () => {
		const { queryByText } = render(
			<TaxRecommendations
				page="wc-settings"
				tab="shipping"
				section={ undefined }
			/>
		);

		expect(
			queryByText( 'Recommended tax solutions' )
		).not.toBeInTheDocument();
	} );

	it( 'should not render when section is not empty', () => {
		const { queryByText } = render(
			<TaxRecommendations
				page="wc-settings"
				tab="tax"
				section="standard"
			/>
		);

		expect(
			queryByText( 'Recommended tax solutions' )
		).not.toBeInTheDocument();
	} );

	it( 'should not render when marketplace suggestions are disabled', () => {
		( useSelect as jest.Mock ).mockImplementation( ( fn ) =>
			fn( () => ( {
				getOption: () => 'no',
				hasFinishedResolution: () => true,
			} ) )
		);

		const { queryByText } = render(
			<TaxRecommendations
				page="wc-settings"
				tab="tax"
				section={ undefined }
			/>
		);

		expect(
			queryByText( 'Recommended tax solutions' )
		).not.toBeInTheDocument();
	} );

	it( 'should not render when the current user cannot install plugins', () => {
		( useUser as jest.Mock ).mockReturnValue( {
			currentUserCan: () => false,
		} );

		const { queryByText } = render(
			<TaxRecommendations
				page="wc-settings"
				tab="tax"
				section={ undefined }
			/>
		);

		expect(
			queryByText( 'Recommended tax solutions' )
		).not.toBeInTheDocument();
	} );

	it( 'should render on the default tax settings section', () => {
		const { getByText } = render(
			<TaxRecommendations
				page="wc-settings"
				tab="tax"
				section={ undefined }
			/>
		);

		expect( getByText( 'Recommended tax solutions' ) ).toBeInTheDocument();
	} );
} );
