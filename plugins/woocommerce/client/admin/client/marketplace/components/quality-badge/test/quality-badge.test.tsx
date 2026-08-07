/**
 * External dependencies
 */
import { render, screen, fireEvent } from '@testing-library/react';
import React from 'react';

jest.mock( '@woocommerce/navigation', () => ( {
	getNewPath: jest.fn( () => '/new-path' ),
	navigateTo: jest.fn(),
	useQuery: jest.fn( () => ( {} ) ),
} ) );

jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn(),
} ) );

/**
 * Internal dependencies
 */
import { navigateTo, useQuery } from '@woocommerce/navigation';
import QualityBadge from '../quality-badge';
import QualityBadgeFilter from '../quality-badge-filter';
import { MarketplaceContext } from '../../../contexts/marketplace-context';
import { MarketplaceContextType } from '../../../contexts/types';
import { Product, ProductType } from '../../product-list/types';

const contextWithBadge = {
	iamSettings: {
		quality_badge: {
			enabled: true,
			label: 'Excellence Verified',
			tooltip: 'Verified against WooCommerce standards.',
		},
	},
} as MarketplaceContextType;

const contextWithBadgeDisabled = {
	iamSettings: {
		quality_badge: {
			enabled: false,
			label: 'Excellence Verified',
			tooltip: '',
		},
	},
} as MarketplaceContextType;

const contextWithoutBadgeSettings = {
	iamSettings: {},
} as MarketplaceContextType;

const product: Product = {
	id: 1,
	title: 'Test extension',
	image: '',
	type: ProductType.extension,
	description: '',
	vendorName: '',
	vendorUrl: '',
	icon: '',
	url: '',
	price: 0,
	isInstallable: false,
	currency: 'USD',
	isOnSale: false,
	regularPrice: 0,
	hasQualityBadge: true,
};

function renderWithContext(
	ui: React.ReactElement,
	context: MarketplaceContextType
) {
	return render(
		<MarketplaceContext.Provider value={ context }>
			{ ui }
		</MarketplaceContext.Provider>
	);
}

beforeEach( () => {
	( useQuery as jest.Mock ).mockReturnValue( {} );
} );

describe( 'QualityBadge', () => {
	it( 'renders the badge label when enabled and the product has the badge', () => {
		renderWithContext(
			<QualityBadge product={ product } />,
			contextWithBadge
		);

		expect( screen.getByText( 'Excellence Verified' ) ).toBeInTheDocument();
	} );

	it( 'renders nothing when the API reports the badge as disabled', () => {
		const { container } = renderWithContext(
			<QualityBadge product={ product } />,
			contextWithBadgeDisabled
		);

		expect( container ).toBeEmptyDOMElement();
	} );

	it( 'renders nothing when the API sends no badge settings', () => {
		const { container } = renderWithContext(
			<QualityBadge product={ product } />,
			contextWithoutBadgeSettings
		);

		expect( container ).toBeEmptyDOMElement();
	} );

	it( 'renders nothing when the product does not have the badge', () => {
		const { container } = renderWithContext(
			<QualityBadge product={ { ...product, hasQualityBadge: false } } />,
			contextWithBadge
		);

		expect( container ).toBeEmptyDOMElement();
	} );
} );

describe( 'QualityBadgeFilter', () => {
	it( 'renders the toggle with the label from the API', () => {
		renderWithContext( <QualityBadgeFilter />, contextWithBadge );

		expect(
			screen.getByLabelText( 'Show only Excellence Verified' )
		).toBeInTheDocument();
	} );

	it( 'renders nothing when the API reports the badge as disabled', () => {
		const { container } = renderWithContext(
			<QualityBadgeFilter />,
			contextWithBadgeDisabled
		);

		expect( container ).toBeEmptyDOMElement();
	} );

	it( 'navigates with the quality_badge param when toggled on', () => {
		renderWithContext( <QualityBadgeFilter />, contextWithBadge );

		fireEvent.click(
			screen.getByLabelText( 'Show only Excellence Verified' )
		);

		expect( navigateTo ).toHaveBeenCalledWith( { url: '/new-path' } );
	} );

	it( 'is checked when the quality_badge query param is set', () => {
		( useQuery as jest.Mock ).mockReturnValue( { quality_badge: '1' } );

		renderWithContext( <QualityBadgeFilter />, contextWithBadge );

		expect(
			screen.getByLabelText( 'Show only Excellence Verified' )
		).toBeChecked();
	} );

	it( 'shows the tooltip copy in a popover without a link when no docs URL is set', () => {
		renderWithContext( <QualityBadgeFilter />, contextWithBadge );

		fireEvent.click( screen.getByLabelText( 'About Excellence Verified' ) );

		expect(
			screen.getByText( 'Verified against WooCommerce standards.' )
		).toBeInTheDocument();
		expect( screen.queryByText( 'Learn more' ) ).not.toBeInTheDocument();
	} );

	it( 'shows a "Learn more" link in the popover when the API sends a docs URL', () => {
		const context = {
			iamSettings: {
				quality_badge: {
					...contextWithBadge.iamSettings.quality_badge,
					docs_url: 'https://woocommerce.com/document/excellence/',
				},
			},
		} as MarketplaceContextType;

		renderWithContext( <QualityBadgeFilter />, context );

		fireEvent.click( screen.getByLabelText( 'About Excellence Verified' ) );

		expect(
			screen.getByText( 'Learn more' ).closest( 'a' )
		).toHaveAttribute(
			'href',
			'https://woocommerce.com/document/excellence/'
		);
	} );
} );
