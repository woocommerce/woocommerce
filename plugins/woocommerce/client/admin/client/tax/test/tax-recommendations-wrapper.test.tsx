import { beforeEach, describe, expect, it, vi, type Mock } from 'vitest';

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
vi.mock( '@wordpress/data', async () => {
	const mock = {
		...( await vi.importActual( '@wordpress/data' ) ),
		useSelect: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@woocommerce/data', async () => {
	const mock = {
		...( await vi.importActual( '@woocommerce/data' ) ),
		useUser: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@wordpress/element', async () => {
	const mock = {
		...( await vi.importActual( '@wordpress/element' ) ),
		Suspense: () => <div>Recommended tax solutions</div>,
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
describe( 'TaxRecommendations', () => {
	beforeEach( () => {
		( useSelect as Mock ).mockImplementation( ( fn ) =>
			fn( () => ( {
				getOption: () => 'yes',
				hasFinishedResolution: () => true,
			} ) )
		);
		( useUser as Mock ).mockReturnValue( {
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
		( useSelect as Mock ).mockImplementation( ( fn ) =>
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
		( useUser as Mock ).mockReturnValue( {
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
