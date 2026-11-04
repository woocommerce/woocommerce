/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { useContext } from '@wordpress/element';
import { useExperiment } from '@woocommerce/explat';

jest.mock( '@woocommerce/explat', () => ( {
	useExperiment: jest.fn(),
} ) );

/**
 * Internal dependencies
 */
import {
	MarketplaceContext,
	MarketplaceContextProvider,
} from '../marketplace-context';

function VariationProbe() {
	const { productPreviewVariation } = useContext( MarketplaceContext );
	return <span>{ productPreviewVariation ?? 'none' }</span>;
}

describe( 'MarketplaceContextProvider', () => {
	const originalTracks = window.wcTracks;

	beforeEach( () => {
		( useExperiment as jest.Mock ).mockReturnValue( [
			false,
			{ variationName: 'treatment' },
		] );
		global.fetch = jest.fn( () => new Promise( () => {} ) ) as jest.Mock;
	} );

	afterEach( () => {
		window.wcTracks = originalTracks;
	} );

	it( 'passes the assigned variation when tracking is on', () => {
		window.wcTracks = { ...originalTracks, isEnabled: true };
		const { getByText } = render(
			<MarketplaceContextProvider>
				<VariationProbe />
			</MarketplaceContextProvider>
		);
		expect( getByText( 'treatment' ) ).toBeInTheDocument();
	} );

	it( 'ignores a cached variation once tracking is turned off', () => {
		window.wcTracks = { ...originalTracks, isEnabled: false };
		const { getByText } = render(
			<MarketplaceContextProvider>
				<VariationProbe />
			</MarketplaceContextProvider>
		);
		expect( getByText( 'none' ) ).toBeInTheDocument();
	} );
} );
