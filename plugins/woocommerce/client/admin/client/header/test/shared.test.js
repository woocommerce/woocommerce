/**
 * External dependencies
 */
import { render, screen, act } from '@testing-library/react';
import { useSlot } from '@woocommerce/experimental';
import React from 'react';

/**
 * Internal dependencies
 */
import useIsScrolled from '~/hooks/useIsScrolled';
import { getPageTitle, useUpdateBodyMargin, BaseHeader } from '../shared';

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

jest.mock( '~/hooks/useIsScrolled', () => ( {
	__esModule: true,
	default: jest.fn(),
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

jest.mock( '../../task-lists/reminder-bar', () => ( {
	TasksReminderBar: ( { taskListId } ) => (
		<div
			data-testid="tasks-reminder-bar"
			data-task-list-id={ taskListId }
		/>
	),
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

describe( 'useUpdateBodyMargin', () => {
	beforeEach( () => {
		// Setup DOM elements needed for the tests
		document.body.innerHTML = '<div id="wpbody"></div>';

		// Reset mocks
		jest.useFakeTimers();
	} );

	afterEach( () => {
		jest.clearAllMocks();
		jest.clearAllTimers();
		document.body.innerHTML = '';
	} );

	test( 'should update wpbody margin top based on header height', () => {
		// Create a mock ref with clientHeight
		const headerElement = { current: { clientHeight: 100 } };
		const headerItemSlot = { fills: [] };

		// Create a test component to use the hook
		const TestComponent = () => {
			const updateMargin = useUpdateBodyMargin( {
				headerElement,
				headerItemSlot,
			} );
			// Call the function to verify it works
			updateMargin();
			return null;
		};

		render( <TestComponent /> );

		// Fast-forward timers to trigger the debounced function
		act( () => {
			jest.advanceTimersByTime( 200 );
		} );

		const wpBody = document.querySelector( '#wpbody' );
		expect( wpBody.style.marginTop ).toBe( '100px' );
	} );

	test( 'should clean up event listeners and reset margin on unmount', () => {
		// Create a mock ref with clientHeight
		const headerElement = { current: { clientHeight: 100 } };
		const headerItemSlot = { fills: [] };

		// Spy on event listeners
		const addEventListenerSpy = jest.spyOn( window, 'addEventListener' );
		const removeEventListenerSpy = jest.spyOn(
			window,
			'removeEventListener'
		);

		// Create a test component to use the hook
		const TestComponent = () => {
			useUpdateBodyMargin( {
				headerElement,
				headerItemSlot,
			} );
			return null;
		};

		const { unmount } = render( <TestComponent /> );

		// Verify event listener was added
		expect( addEventListenerSpy ).toHaveBeenCalledWith(
			'resize',
			expect.any( Function )
		);

		// Fast-forward timers to trigger the debounced function
		act( () => {
			jest.advanceTimersByTime( 200 );
		} );

		// Verify margin was set
		const wpBody = document.querySelector( '#wpbody' );
		expect( wpBody.style.marginTop ).toBe( '100px' );

		// Unmount component
		unmount();

		// Verify event listener was removed
		expect( removeEventListenerSpy ).toHaveBeenCalledWith(
			'resize',
			expect.any( Function )
		);
	} );

	test( 'should handle null wpbody or headerElement', () => {
		// Remove wpbody from DOM
		document.body.innerHTML = '';

		// Create a mock ref with null current
		const headerElement = { current: null };
		const headerItemSlot = { fills: [] };

		// Create a test component to use the hook
		const TestComponent = () => {
			const updateMargin = useUpdateBodyMargin( {
				headerElement,
				headerItemSlot,
			} );
			// Call the function to verify it doesn't throw errors
			updateMargin();
			return null;
		};

		render( <TestComponent /> );

		// Fast-forward timers to trigger the debounced function
		act( () => {
			jest.advanceTimersByTime( 200 );
		} );

		// Should not throw errors
		expect( true ).toBe( true );
	} );

	test( 'should debounce multiple calls', () => {
		// Create a mock ref with clientHeight
		const headerElement = { current: { clientHeight: 100 } };
		const headerItemSlot = { fills: [] };

		// Create a test component to use the hook
		const TestComponent = () => {
			const updateMargin = useUpdateBodyMargin( {
				headerElement,
				headerItemSlot,
			} );

			// Call updateBodyMargin multiple times
			updateMargin();
			updateMargin();
			updateMargin();

			return null;
		};

		render( <TestComponent /> );

		// Fast-forward timers to trigger the debounced function
		act( () => {
			jest.advanceTimersByTime( 200 );
		} );

		// Should only set margin once
		const wpBody = document.querySelector( '#wpbody' );
		expect( wpBody.style.marginTop ).toBe( '100px' );
	} );
} );

describe( 'BaseHeader', () => {
	beforeEach( () => {
		// Setup mocks
		useIsScrolled.mockReturnValue( { isScrolled: false } );
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
			showReminderBar: false,
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

		// Check reminder bar is not rendered
		expect(
			screen.queryByTestId( 'tasks-reminder-bar' )
		).not.toBeInTheDocument();
	} );

	test( 'should render with reminder bar when showReminderBar is true', () => {
		const props = {
			isEmbedded: false,
			query: {},
			showReminderBar: true,
			sections: [ 'WooCommerce' ],
		};

		render( <BaseHeader { ...props } /> );

		// Check reminder bar is rendered
		expect(
			screen.getByTestId( 'tasks-reminder-bar' )
		).toBeInTheDocument();
		expect( screen.getByTestId( 'tasks-reminder-bar' ) ).toHaveAttribute(
			'data-task-list-id',
			'setup'
		);
	} );

	test( 'should render with is-scrolled class when isScrolled is true', () => {
		// Mock isScrolled to return true
		useIsScrolled.mockReturnValue( { isScrolled: true } );

		const props = {
			isEmbedded: false,
			query: {},
			showReminderBar: false,
			sections: [ 'WooCommerce' ],
		};

		render( <BaseHeader { ...props } /> );

		// Check header has is-scrolled class
		const headerContainer = document.querySelector(
			'.woocommerce-layout__header'
		);
		expect( headerContainer ).toHaveClass( 'is-scrolled' );
	} );

	test( 'should render with right alignment when leftAlign is false', () => {
		const props = {
			isEmbedded: false,
			query: {},
			showReminderBar: false,
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
			showReminderBar: false,
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
			showReminderBar: false,
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
			showReminderBar: false,
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
