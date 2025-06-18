/* eslint-disable @woocommerce/dependency-group -- because we import mocks first, we deactivate this rule to avoid ESLint errors */
import '../../__mocks__/setup-shared-mocks';

/**
 * External dependencies
 */
import { render, screen, fireEvent } from '@testing-library/react';
import '@testing-library/jest-dom';

/**
 * Internal dependencies
 */
import { CategoryMenu } from '../category-menu';

jest.mock( '@wordpress/components', () => ( {
	MenuGroup: ( props: React.HTMLAttributes< HTMLDivElement > ) => (
		<div data-testid="menu-group" { ...props } />
	),
	MenuItem: ( props: React.ButtonHTMLAttributes< HTMLButtonElement > ) => (
		<button role="menuitem" { ...props } />
	),
} ) );

describe( 'CategoryMenu', () => {
	const groupedTags = {
		Marketing: [],
		Promotions: [],
		'Customer Data': [],
	};
	const onCategorySelect = jest.fn();

	beforeEach( () => {
		onCategorySelect.mockClear();
	} );

	it( 'should render the "All" menu item and all categories', () => {
		render(
			<CategoryMenu
				groupedTags={ groupedTags }
				activeCategory={ null }
				onCategorySelect={ onCategorySelect }
			/>
		);

		expect( screen.getByText( 'All' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Marketing' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Promotions' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Customer Data' ) ).toBeInTheDocument();
	} );

	it( 'should call onCategorySelect when "All" is clicked', () => {
		render(
			<CategoryMenu
				groupedTags={ groupedTags }
				activeCategory={ 'Marketing' }
				onCategorySelect={ onCategorySelect }
			/>
		);

		fireEvent.click( screen.getByText( 'All' ) );
		expect( onCategorySelect ).toHaveBeenCalledWith( null );
	} );

	it( 'should call onCategorySelect with correct category', () => {
		render(
			<CategoryMenu
				groupedTags={ groupedTags }
				activeCategory={ null }
				onCategorySelect={ onCategorySelect }
			/>
		);

		fireEvent.click( screen.getByText( 'Promotions' ) );
		expect( onCategorySelect ).toHaveBeenCalledWith( 'Promotions' );
	} );

	it( 'should apply active class to active category', () => {
		render(
			<CategoryMenu
				groupedTags={ groupedTags }
				activeCategory={ 'Customer Data' }
				onCategorySelect={ onCategorySelect }
			/>
		);

		const activeItem = screen.getByText( 'Customer Data' );
		expect( activeItem ).toHaveClass(
			'woocommerce-personalization-tags-modal-menu-item-active'
		);
	} );

	it( 'should render separators between categories', () => {
		render(
			<CategoryMenu
				groupedTags={ groupedTags }
				activeCategory={ null }
				onCategorySelect={ onCategorySelect }
			/>
		);

		const separators = screen.getAllByTestId(
			'woocommerce-personalization-tags-modal-menu-separator'
		);
		// 1 after "All" + 2 between 3 categories
		expect( separators ).toHaveLength( 3 );
	} );
} );
