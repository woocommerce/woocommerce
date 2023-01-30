/**
 * External dependencies
 */
import { act, render, waitFor } from '@testing-library/react';
import { getAdminLink } from '@woocommerce/settings';
import { getHistory } from '@woocommerce/navigation';
import { useSelect } from '@wordpress/data';
import userEvent from '@testing-library/user-event';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Container from '../';

jest.mock( '@wordpress/data', () => {
	// Require the original module to not be mocked...
	const originalModule = jest.requireActual( '@wordpress/data' );

	return {
		__esModule: true, // Use it when dealing with esModules
		...originalModule,
		useSelect: jest.fn().mockReturnValue( {} ),
	};
} );

const originalLocation = window.location;
global.window = Object.create( window );
window.wcNavigation = {
	rootBackLabel: 'Root Back',
	rootBackUrl: 'http://custombackbutton.com',
};

const menuItems = [
	{
		id: 'woocommerce-home',
		title: 'Home',
		parent: 'woocommerce',
		menuId: 'primary',
		url: 'admin.php?page=wc-admin',
	},
	{
		id: 'primary-category',
		title: 'Primary Category',
		isCategory: true,
		parent: 'woocommerce',
		backButtonLabel: 'Custom Back Label',
		menuId: 'primary',
	},
	{
		id: 'primary-child',
		title: 'Primary Child',
		parent: 'primary-category',
		menuId: 'primary',
		url: 'admin.php?page=wc-admin&path=/child',
	},
	{
		id: 'favorite-category',
		title: 'Favorite Category',
		isCategory: true,
		parent: 'woocommerce',
		menuId: 'favorites',
	},
	{
		id: 'favorite-child',
		title: 'Favorite Child',
		parent: 'favorite-category',
		menuId: 'plugins',
	},
	{
		id: 'plugin-category',
		title: 'Plugin Category',
		isCategory: true,
		parent: 'woocommerce',
		menuId: 'plugins',
	},
	{
		id: 'plugin-child',
		title: 'Plugin Child',
		parent: 'plugin-category',
		menuId: 'plugins',
		url: 'admin.php?page=my-plugin',
	},
	{
		id: 'secondary-category',
		title: 'Secondary Category',
		isCategory: true,
		parent: 'woocommerce',
		menuId: 'secondary',
	},
	{
		id: 'secondary-child',
		title: 'Secondary Child',
		parent: 'secondary-category',
		menuId: 'secondary',
	},
];

describe( 'Container', () => {
	beforeEach( () => {
		delete window.location;
		window.location = new URL( getAdminLink( 'admin.php?page=wc-admin' ) );

		useSelect.mockImplementation( () => ( {
			menuItems,
			favorites: [ 'favorite-category' ],
		} ) );
	} );

	afterAll( () => {
		window.location = originalLocation;
	} );

	test( 'should show the woocommerce root items initially', () => {
		const { container } = render( <Container /> );

		expect(
			container.querySelectorAll( '.components-navigation__item' ).length
		).toBe( 5 );
	} );

	test( 'should set the active item based on initial location', async () => {
		const { queryByText } = render( <Container /> );

		expect(
			queryByText( 'Home' ).parentElement.parentElement.classList
		).toContain( 'is-active' );
	} );

	test( 'should set the initial active item based on current location', () => {
		window.location = new URL( getAdminLink( 'admin.php?page=my-plugin' ) );

		const { container, queryByText } = render( <Container /> );

		expect(
			container.querySelector( '.woocommerce-navigation-category-title' )
				.textContent
		).toBe( 'Plugin Category' );
		expect(
			queryByText( 'Plugin Child' ).parentElement.parentElement.classList
		).toContain( 'is-active' );
	} );

	test( 'should update the active item and level when location changes', async () => {
		window.location = new URL( getAdminLink( 'admin.php?page=wc-admin' ) );

		const { container, queryByText } = render( <Container /> );

		expect( queryByText( 'Primary Child' ) ).toBeNull();

		// Trigger and wait for history change.
		await act( async () => {
			delete window.location;
			window.location = new URL(
				getAdminLink( 'admin.php?page=wc-admin&path=/child' )
			);
			getHistory().push( new URL( getAdminLink( '/child' ) ) );
		} );

		await waitFor( () =>
			expect(
				container.querySelector(
					'.woocommerce-navigation-category-title'
				).textContent
			).toBe( 'Primary Category' )
		);
		await waitFor( () =>
			expect(
				queryByText( 'Primary Child' ).parentElement.parentElement
					.classList
			).toContain( 'is-active' )
		);
	} );

	test( 'should update the active level when a category is clicked', () => {
		const { container, queryByText } = render( <Container /> );

		userEvent.click( queryByText( 'Secondary Category' ) );

		expect(
			container.querySelector( '.woocommerce-navigation-category-title' )
				.textContent
		).toBe( 'Secondary Category' );
	} );

	test( 'should show the back button in each category', () => {
		const { container, queryByText } = render( <Container /> );

		userEvent.click( queryByText( 'Primary Category' ) );

		const backButton = container.querySelector(
			'.components-navigation__back-button'
		);

		expect( backButton.textContent ).toBe( 'Custom Back Label' );
	} );

	test( 'should go up a level on back button click', () => {
		const { container, queryByText } = render( <Container /> );

		userEvent.click( queryByText( 'Primary Category' ) );

		const backButton = container.querySelector(
			'.components-navigation__back-button'
		);

		userEvent.click( backButton );

		expect(
			container.querySelector( '.woocommerce-navigation-category-title' )
				.textContent
		).toBe( 'WooCommerce' );
	} );

	test( 'should show the favorite items after the primary items', () => {
		const { container } = render( <Container /> );

		const navigationGroups = container.querySelectorAll(
			'.components-navigation__group'
		);

		expect(
			navigationGroups[ 0 ].querySelector( 'li:nth-child(1)' ).textContent
		).toBe( 'Home' );
		expect(
			navigationGroups[ 0 ].querySelector( 'li:nth-child(2)' ).textContent
		).toBe( 'Primary Category' );
		expect(
			navigationGroups[ 0 ].querySelector( 'li:nth-child(3)' ).textContent
		).toBe( 'Favorite Category' );
		expect(
			navigationGroups[ 1 ].querySelector( 'li:nth-child(1)' ).textContent
		).toBe( 'Plugin Category' );
	} );

	test( 'should not show multiple menus outside of the root category', () => {
		const { container, queryByText } = render( <Container /> );

		const rootNavigationGroups = container.querySelectorAll(
			'.components-navigation__group'
		);

		expect( rootNavigationGroups.length ).toBe( 3 );

		userEvent.click( queryByText( 'Primary Category' ) );

		const categoryNavigationGroups = container.querySelectorAll(
			'.components-navigation__group'
		);

		expect( categoryNavigationGroups.length ).toBe( 1 );
	} );
} );
