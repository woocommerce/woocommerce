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

jest.mock( '@wordpress/a11y', () => ( {
	speak: jest.fn(),
} ) );

/**
 * Internal dependencies
 */
import { speak } from '@wordpress/a11y';
import { navigateTo, useQuery } from '@woocommerce/navigation';
import QualityBadge from '../quality-badge';
import QualityBadgeFilter, {
	isQualityBadgeFilterActive,
} from '../quality-badge-filter';
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

	it( 'opens a popover with the explanation when the chip is clicked', () => {
		renderWithContext(
			<QualityBadge product={ product } />,
			contextWithBadge
		);

		fireEvent.click( screen.getByText( 'Excellence Verified' ) );

		expect(
			screen.getByText( 'Verified against WooCommerce standards.' )
		).toBeInTheDocument();
		expect( screen.queryByText( 'Learn more' ) ).not.toBeInTheDocument();
	} );

	it( 'links to the docs from the chip popover when the API sends a docs URL', () => {
		const context = {
			iamSettings: {
				quality_badge: {
					...contextWithBadge.iamSettings.quality_badge,
					docs_url: 'https://woocommerce.com/document/excellence/',
				},
			},
		} as MarketplaceContextType;

		renderWithContext( <QualityBadge product={ product } />, context );

		fireEvent.click( screen.getByText( 'Excellence Verified' ) );

		expect(
			screen.getByText( 'Learn more' ).closest( 'a' )
		).toHaveAttribute(
			'href',
			'https://woocommerce.com/document/excellence/'
		);
	} );

	it( 'drops non-https docs URLs from the popover', () => {
		const context = {
			iamSettings: {
				quality_badge: {
					...contextWithBadge.iamSettings.quality_badge,
					docs_url: 'javascript:alert(1)',
				},
			},
		} as MarketplaceContextType;

		renderWithContext( <QualityBadge product={ product } />, context );

		fireEvent.click( screen.getByText( 'Excellence Verified' ) );

		expect(
			screen.getByText( 'Verified against WooCommerce standards.' )
		).toBeInTheDocument();
		expect( screen.queryByText( 'Learn more' ) ).not.toBeInTheDocument();
	} );

	it( 'announces the explanation when the popover opens', () => {
		renderWithContext(
			<QualityBadge product={ product } />,
			contextWithBadge
		);

		fireEvent.click( screen.getByText( 'Excellence Verified' ) );

		expect( speak ).toHaveBeenCalledWith(
			'Verified against WooCommerce standards.'
		);
	} );

	it( 'closes with Escape while focus is on the chip', () => {
		renderWithContext(
			<QualityBadge product={ product } />,
			contextWithBadge
		);

		const chip = screen.getByText( 'Excellence Verified' );
		fireEvent.click( chip );
		expect(
			screen.getByText( 'Verified against WooCommerce standards.' )
		).toBeInTheDocument();

		fireEvent.keyDown( chip, { key: 'Escape' } );

		expect(
			screen.queryByText( 'Verified against WooCommerce standards.' )
		).not.toBeInTheDocument();
	} );

	it( 'closes on Tab from the docs link and returns focus to the trigger', () => {
		const context = {
			iamSettings: {
				quality_badge: {
					...contextWithBadge.iamSettings.quality_badge,
					docs_url: 'https://woocommerce.com/document/excellence/',
				},
			},
		} as MarketplaceContextType;

		renderWithContext( <QualityBadge product={ product } />, context );

		const chip = screen.getByText( 'Excellence Verified' );
		fireEvent.click( chip );

		fireEvent.keyDown( screen.getByText( 'Learn more' ), { key: 'Tab' } );

		expect(
			screen.queryByText( 'Verified against WooCommerce standards.' )
		).not.toBeInTheDocument();
		expect( chip.closest( 'button' ) ).toHaveFocus();
	} );

	it( 'closes when focus moves outside the popover and its trigger', () => {
		renderWithContext(
			<QualityBadge product={ product } />,
			contextWithBadge
		);

		fireEvent.click( screen.getByText( 'Excellence Verified' ) );
		expect(
			screen.getByText( 'Verified against WooCommerce standards.' )
		).toBeInTheDocument();

		fireEvent.focusIn( document.body );

		expect(
			screen.queryByText( 'Verified against WooCommerce standards.' )
		).not.toBeInTheDocument();
	} );

	it( 'renders an inert chip when there is no tooltip copy', () => {
		const context = {
			iamSettings: {
				quality_badge: {
					enabled: true,
					label: 'Excellence Verified',
					tooltip: '',
				},
			},
		} as MarketplaceContextType;

		renderWithContext( <QualityBadge product={ product } />, context );

		expect(
			screen.getByText( 'Excellence Verified' ).closest( 'button' )
		).toBeNull();
	} );
} );

describe( 'isQualityBadgeFilterActive', () => {
	it( 'is active only when the param is set and the API has the badge enabled', () => {
		expect(
			isQualityBadgeFilterActive(
				{ quality_badge: '1' },
				contextWithBadge.iamSettings
			)
		).toBe( true );
	} );

	it( 'ignores a stale param when the badge is disabled', () => {
		expect(
			isQualityBadgeFilterActive(
				{ quality_badge: '1' },
				contextWithBadgeDisabled.iamSettings
			)
		).toBe( false );
	} );

	it( 'ignores a stale param when IAM settings are empty (failed fetch)', () => {
		expect( isQualityBadgeFilterActive( { quality_badge: '1' }, {} ) ).toBe(
			false
		);
	} );

	it( 'ignores the param when the badge is enabled but has no label', () => {
		expect(
			isQualityBadgeFilterActive(
				{ quality_badge: '1' },
				{ quality_badge: { enabled: true, label: '', tooltip: '' } }
			)
		).toBe( false );
	} );

	it( 'is inactive without the param', () => {
		expect(
			isQualityBadgeFilterActive( {}, contextWithBadge.iamSettings )
		).toBe( false );
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
