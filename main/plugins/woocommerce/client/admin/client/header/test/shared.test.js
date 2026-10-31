/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import { useSlot } from '@woocommerce/experimental';
import React from 'react';

/**
 * Internal dependencies
 */
import { getPageTitle, BaseHeader } from '../shared';

// Mock dependencies
jest.mock( '@woocommerce/experimental', () => ( {
	useSlot: jest.fn(),
	Text: ( { children, className, as } ) => {
		// Create the element with the proper role based on the 'as' prop
		const Element = as || 'div';
		return (
			<Element
				className={ className }
				role={ as === 'h1' ? 'heading' : undefined }
				aria-level={ as === 'h1' ? 1 : undefined }
			>
				{ children }
			</Element>
		);
	},
} ) );

jest.mock( '@wordpress/html-entities', () => ( {
	decodeEntities: ( content ) => content,
} ) );

jest.mock( '@woocommerce/admin-layout', () => ( {
	WC_HEADER_SLOT_NAME: 'wc-header',
	WC_HEADER_PAGE_TITLE_SLOT_NAME: 'wc-header-page-title',
	WooHeaderNavigationItem: {
		Slot: ( { fillProps } ) => (
			<div
				data-testid="navigation-slot"
				data-props={ JSON.stringify( fillProps ) }
			/>
		),
	},
	WooHeaderItem: {
		Slot: ( { fillProps } ) => (
			<div
				data-testid="header-item-slot"
				data-props={ JSON.stringify( fillProps ) }
			/>
		),
	},
	WooHeaderPageTitle: {
		Slot: ( { fillProps } ) => (
			<div
				data-testid="page-title-slot"
				data-props={ JSON.stringify( fillProps ) }
			/>
		),
	},
} ) );

describe( 'getPageTitle', () => {
	test( 'should get page title as the last item if section length is less than 3', () => {
		const sections = [ 'Payments' ];
		expect( getPageTitle( sections ) ).toBe( 'Payments' );
	} );

	test( "should get page title as the second item's second element if section length is 3 or more and second item has a second element", () => {
		const sections = [
			[ 'admin.php?page=wc-admin', 'WooCommerce' ],
			[ 'admin.php?page=wc-settings', 'Settings' ],
			'Payments',
		];
		expect( getPageTitle( sections ) ).toBe( 'Settings' );
	} );

	test( "should get page title as the last item if section length is 3 or more but second item doesn't have a second element", () => {
		const sections = [
			[ 'admin.php?page=wc-admin', 'WooCommerce' ],
			'Payments',
		];
		expect( getPageTitle( sections ) ).toBe( 'Payments' );
	} );

	test( 'should handle all pagesWithTabs correctly', () => {
		// Test wc-settings
		const settingsSections = [
			[ 'admin.php?page=wc-admin', 'WooCommerce' ],
			[ 'admin.php?page=wc-settings', 'Settings' ],
			'General',
		];
		expect( getPageTitle( settingsSections ) ).toBe( 'Settings' );

		// Test wc-reports
		const reportsSections = [
			[ 'admin.php?page=wc-admin', 'WooCommerce' ],
			[ 'admin.php?page=wc-reports', 'Reports' ],
			'Sales',
		];
		expect( getPageTitle( reportsSections ) ).toBe( 'Reports' );

		// Test wc-status
		const statusSections = [
			[ 'admin.php?page=wc-admin', 'WooCommerce' ],
			[ 'admin.php?page=wc-status', 'Status' ],
			'System Status',
		];
		expect( getPageTitle( statusSections ) ).toBe( 'Status' );
	} );
} );

describe( 'BaseHeader', () => {
	beforeEach( () => {
		// Setup mocks
		useSlot.mockImplementation( ( slotName ) => {
			if ( slotName === 'wc-header-page-title' ) {
				return { fills: [] };
			}
			return { fills: [] };
		} );
	} );

	afterEach( () => {
		jest.clearAllMocks();
	} );

	test( 'should render with default props', () => {
		const props = {
			isEmbedded: false,
			query: {},
			sections: [ 'WooCommerce' ],
			leftAlign: true,
		};

		render( <BaseHeader { ...props } /> );

		// Check header class
		const header = screen.getByRole( 'heading', { level: 1 } );
		expect( header ).toHaveClass( 'woocommerce-layout__header-heading' );
		expect( header ).toHaveClass( 'woocommerce-layout__header-left-align' );

		// Check page title
		expect( header.textContent ).toBe( 'WooCommerce' );
	} );

	test( 'should render with right alignment when leftAlign is false', () => {
		const props = {
			isEmbedded: false,
			query: {},
			sections: [ 'WooCommerce' ],
			leftAlign: false,
		};

		render( <BaseHeader { ...props } /> );

		// Check header doesn't have left-align class
		const header = screen.getByRole( 'heading', { level: 1 } );
		expect( header ).not.toHaveClass(
			'woocommerce-layout__header-left-align'
		);
	} );

	test( 'should render page title slot when fills are available', () => {
		// Mock useSlot to return fills
		useSlot.mockImplementation( ( slotName ) => {
			if ( slotName === 'wc-header-page-title' ) {
				return { fills: [ 'some-fill' ] };
			}
			return { fills: [] };
		} );

		const props = {
			isEmbedded: false,
			query: {},
			sections: [ 'WooCommerce' ],
		};

		render( <BaseHeader { ...props } /> );

		// Check page title slot is rendered
		expect( screen.getByTestId( 'page-title-slot' ) ).toBeInTheDocument();
	} );

	test( 'should pass correct props to slots', () => {
		const props = {
			isEmbedded: true,
			query: { page: 'wc-admin' },
			sections: [ 'WooCommerce' ],
		};

		render( <BaseHeader { ...props } /> );

		// Check props passed to navigation slot
		const navigationSlot = screen.getByTestId( 'navigation-slot' );
		expect(
			JSON.parse( navigationSlot.getAttribute( 'data-props' ) )
		).toEqual( {
			isEmbedded: true,
			query: { page: 'wc-admin' },
		} );

		// Check props passed to header item slot
		const headerItemSlot = screen.getByTestId( 'header-item-slot' );
		expect(
			JSON.parse( headerItemSlot.getAttribute( 'data-props' ) )
		).toEqual( {
			isEmbedded: true,
			query: { page: 'wc-admin' },
		} );
	} );

	test( 'should render children when provided', () => {
		const props = {
			isEmbedded: false,
			query: {},
			sections: [ 'WooCommerce' ],
			children: <div data-testid="child-component">Child Component</div>,
		};

		render( <BaseHeader { ...props } /> );

		// Check children are rendered
		expect( screen.getByTestId( 'child-component' ) ).toBeInTheDocument();
		expect( screen.getByTestId( 'child-component' ).textContent ).toBe(
			'Child Component'
		);
	} );
} );
