/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { useDispatch, useSelect } from '@wordpress/data';
import userEvent from '@testing-library/user-event';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import FavoriteButton from '../';

jest.mock( '@wordpress/data', () => {
	// Require the original module to not be mocked...
	const originalModule = jest.requireActual( '@wordpress/data' );

	return {
		__esModule: true, // Use it when dealing with esModules
		...originalModule,
		useDispatch: jest.fn().mockReturnValue( {} ),
		useSelect: jest.fn().mockReturnValue( {} ),
	};
} );

describe( 'FavoriteButton', () => {
	test( 'should not show when favorites are still resolving', () => {
		useSelect.mockImplementation( () => ( {
			favorites: [],
			isResolving: true,
		} ) );

		const { container } = render( <FavoriteButton id="my-item" /> );

		expect(
			container.querySelector( '.woocommerce-navigation-favorite-button' )
		).toBeNull();
	} );

	test( 'should show the empty star when item is not favorited', () => {
		useSelect.mockImplementation( () => ( {
			favorites: [],
			isResolving: false,
		} ) );

		const { container } = render( <FavoriteButton id="my-item" /> );

		expect( container.querySelector( '.star-empty-icon' ) ).not.toBeNull();
	} );

	test( 'should show the filled star when item is favorited', () => {
		useSelect.mockImplementation( () => ( {
			favorites: [ 'my-item' ],
			isResolving: false,
		} ) );

		const { container } = render( <FavoriteButton id="my-item" /> );

		expect( container.querySelector( '.star-filled-icon' ) ).not.toBeNull();
	} );

	test( 'should remove the favorite when toggling a favorited item', () => {
		useSelect.mockImplementation( () => ( {
			favorites: [ 'my-item' ],
			isResolving: false,
		} ) );

		const addFavorite = jest.fn();
		const removeFavorite = jest.fn();

		useDispatch.mockReturnValue( {
			addFavorite,
			removeFavorite,
		} );

		const { container } = render( <FavoriteButton id="my-item" /> );

		userEvent.click(
			container.querySelector( '.woocommerce-navigation-favorite-button' )
		);

		expect( addFavorite ).not.toHaveBeenCalled();
		expect( removeFavorite ).toHaveBeenCalled();
	} );

	test( 'should add the favorite when toggling a unfavorited item', () => {
		useSelect.mockImplementation( () => ( {
			favorites: [],
			isResolving: false,
		} ) );

		const addFavorite = jest.fn();
		const removeFavorite = jest.fn();

		useDispatch.mockReturnValue( {
			addFavorite,
			removeFavorite,
		} );

		const { container } = render( <FavoriteButton id="my-item" /> );

		userEvent.click(
			container.querySelector( '.woocommerce-navigation-favorite-button' )
		);

		expect( addFavorite ).toHaveBeenCalled();
		expect( removeFavorite ).not.toHaveBeenCalled();
	} );
} );
