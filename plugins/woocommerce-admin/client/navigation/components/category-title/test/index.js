/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import CategoryTitle from '../';

jest.mock( '@wordpress/data', () => {
	const originalModule = jest.requireActual( '@wordpress/data' );
	return {
		...Object.keys( originalModule ).reduce( ( mocked, key ) => {
			try {
				mocked[ key ] = originalModule[ key ];
			} catch ( e ) {
				mocked[ key ] = jest.fn();
			}
			return mocked;
		}, {} ),
		useDispatch: jest.fn().mockReturnValue( {
			addFavorite: jest.fn(),
			removeFavorite: jest.fn(),
		} ),
		useSelect: jest.fn().mockReturnValue( {
			favorites: [],
		} ),
	};
} );

describe( 'CategoryTitle', () => {
	test( 'should render the category title without the option to favorite for the primary menu', () => {
		const { container, queryByText } = render(
			<CategoryTitle
				category={ {
					id: 'my-category',
					menuId: 'primary',
					title: 'Category Title',
				} }
			/>
		);

		expect(
			container.querySelector( '.woocommerce-navigation-favorite-button' )
		).toBeNull();
		expect( queryByText( 'Category Title' ) ).not.toBeNull();
	} );

	test( 'should render the category title without the option to favorite for any other menus', () => {
		const { container, queryByText } = render(
			<CategoryTitle
				category={ {
					id: 'my-category',
					menuId: 'new-menu',
					title: 'Category Title',
				} }
			/>
		);

		expect(
			container.querySelector( '.woocommerce-navigation-favorite-button' )
		).toBeNull();
		expect( queryByText( 'Category Title' ) ).not.toBeNull();
	} );

	test( 'should render the category title and favorite button for plugins', () => {
		const { container, queryByText } = render(
			<CategoryTitle
				category={ {
					id: 'my-category',
					menuId: 'plugins',
					title: 'Category Title',
				} }
			/>
		);

		expect(
			container.querySelector( '.woocommerce-navigation-favorite-button' )
		).not.toBeNull();
		expect( queryByText( 'Category Title' ) ).not.toBeNull();
	} );

	test( 'should render the category title and unfavorite button for favorites', () => {
		const { container, queryByText } = render(
			<CategoryTitle
				category={ {
					id: 'my-category',
					menuId: 'favorites',
					title: 'Category Title',
				} }
			/>
		);

		expect(
			container.querySelector( '.woocommerce-navigation-favorite-button' )
		).not.toBeNull();
		expect( queryByText( 'Category Title' ) ).not.toBeNull();
	} );
} );
