jest.mock( '../list-item', () => ( {
	__esModule: true,
	...jest.requireActual( '../list-item' ),
	handleKeyDown: jest.fn(),
} ) );

/**
 * External dependencies
 */
import {
	render,
	screen,
	waitForElementToBeRemoved,
} from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import List, {
	ExperimentalList,
	ExperimentalListItem,
	ExperimentalCollapsibleList,
} from '../index';
import { handleKeyDown } from '../list-item';

describe( 'List', () => {
	describe( 'Experimental List', () => {
		it( 'should render the new List which defaults to a ul component if items are not passed in', () => {
			const { container } = render(
				<ExperimentalList>
					<div>Test</div>
				</ExperimentalList>
			);

			expect( container.querySelector( 'ul' ) ).toBeInTheDocument();
		} );

		it( 'should render children passed in', () => {
			const { container } = render(
				<ExperimentalList>
					<div>Test</div>
				</ExperimentalList>
			);

			expect( container ).toHaveTextContent( 'Test' );
		} );

		it( 'should allow overriding the list type, and passing in arbitrary element props', () => {
			const { container } = render(
				<ExperimentalList listType="ol" role="menu">
					<div>Test</div>
				</ExperimentalList>
			);

			expect( container.querySelector( 'ol' ) ).toBeInTheDocument();
		} );

		describe( 'ExperimentalListItem', () => {
			it( 'should render children passed in', () => {
				const { container } = render(
					<ExperimentalListItem>
						<div>Test</div>
					</ExperimentalListItem>
				);

				expect( container ).toHaveTextContent( 'Test' );
			} );

			it( 'allows disabling the gutter styling', () => {
				const { container } = render(
					<ExperimentalListItem disableGutters>
						<div>Test</div>
					</ExperimentalListItem>
				);

				expect(
					container.querySelector( '.has-gutters' )
				).not.toBeInTheDocument();
			} );

			it( 'should disable animations by default and for unsupported values', () => {
				// disabled by default
				const { container, rerender } = render(
					<ExperimentalListItem>
						<div>Test</div>
					</ExperimentalListItem>
				);

				expect(
					container.querySelector( '.transitions-disabled' )
				).toBeInTheDocument();

				// invalid value
				rerender(
					<ExperimentalListItem animation="bounce-up-and-down">
						<div>Test</div>
					</ExperimentalListItem>
				);

				expect(
					container.querySelector( '.transitions-disabled' )
				).toBeInTheDocument();
			} );

			it( 'should not disable animations if you provide a valid animation value', () => {
				const { container } = render(
					<ExperimentalListItem animation="slide-right">
						<div>Test</div>
					</ExperimentalListItem>
				);

				expect(
					container.querySelector( '.transitions-disabled' )
				).not.toBeInTheDocument();
			} );

			it( 'supports onClick on the list item, and handles keyboard events', () => {
				const dummyOnClick = jest.fn();

				const { container, queryByRole } = render(
					<ExperimentalListItem onClick={ dummyOnClick }>
						<div>Test</div>
					</ExperimentalListItem>
				);

				const listItem = container.querySelector(
					'.woocommerce-list__item'
				);

				userEvent.click( listItem );

				// it doesn't actually matter what key you hit here while handleKeyDown is mocked.
				userEvent.type( listItem, '{enter}' );

				// TODO check that the button role was added.
				expect( queryByRole( 'button' ) ).toBeInTheDocument();
				expect( handleKeyDown ).toHaveBeenCalled();
				expect( dummyOnClick ).toHaveBeenCalled();
			} );

			it( 'includes correct ARIA roles and a11y attributes when the item has an action', () => {
				const clickHandler = jest.fn();
				render(
					<ExperimentalListItem onClick={ clickHandler }>
						<div>Test</div>
					</ExperimentalListItem>
				);

				const item = screen.getByRole( 'button' );
				expect( item ).toBeInTheDocument();
				expect( item ).toHaveAttribute( 'role', 'button' );
				expect( item ).toHaveAttribute( 'tabindex', '0' );
			} );
		} );

		describe( 'ExperimentalListItemCollapse', () => {
			it( 'should not render its children intially, but an extra list footer with show text', () => {
				const { container } = render(
					<ExperimentalCollapsibleList
						collapseLabel="Show less"
						expandLabel="Show more items"
					>
						<div>Test</div>
					</ExperimentalCollapsibleList>
				);

				expect( container ).not.toHaveTextContent( 'Test' );
				expect( container ).toHaveTextContent( 'Show more items' );
			} );

			it( 'should render list items when footer is clicked and trigger onExpand', () => {
				const onExpand = jest.fn();
				const onCollapse = jest.fn();
				const { container } = render(
					<ExperimentalCollapsibleList
						collapseLabel="Show less"
						expandLabel="Show more items"
						onExpand={ onExpand }
						onCollapse={ onCollapse }
					>
						<div>Test</div>
						<div>Test 2</div>
					</ExperimentalCollapsibleList>
				);

				const listItem = container.querySelector(
					'.list-item-collapse'
				);

				userEvent.click( listItem );
				expect( container ).toHaveTextContent( 'Test' );
				expect( container ).toHaveTextContent( 'Test 2' );
				expect( container ).not.toHaveTextContent( 'Show more items' );
				expect( container ).toHaveTextContent( 'Show less' );
				expect( onExpand ).toHaveBeenCalled();
				expect( onCollapse ).not.toHaveBeenCalled();
			} );

			it( 'should render minimum children if minChildrenToShow is set and show the rest on expand', () => {
				const onExpand = jest.fn();
				const onCollapse = jest.fn();
				const { container } = render(
					<ExperimentalCollapsibleList
						collapseLabel="Show less"
						expandLabel="Show more items"
						onExpand={ onExpand }
						onCollapse={ onCollapse }
						show={ 2 }
					>
						<div>Test</div>
						<div>Test 2</div>
						<div>Test 3</div>
						<div>Test 4</div>
					</ExperimentalCollapsibleList>
				);

				expect( container ).toHaveTextContent( 'Test' );
				expect( container ).toHaveTextContent( 'Test 2' );
				expect( container ).not.toHaveTextContent( 'Test 3' );
				expect( container ).not.toHaveTextContent( 'Test 4' );
				const listItem = container.querySelector(
					'.list-item-collapse'
				);

				userEvent.click( listItem );
				expect( container ).toHaveTextContent( 'Test' );
				expect( container ).toHaveTextContent( 'Test 2' );
				expect( container ).toHaveTextContent( 'Test 3' );
				expect( container ).toHaveTextContent( 'Test 4' );
				expect( container ).not.toHaveTextContent( 'Show more items' );
				expect( container ).toHaveTextContent( 'Show less' );
				expect( onExpand ).toHaveBeenCalled();
				expect( onCollapse ).not.toHaveBeenCalled();
			} );

			it( 'should correctly toggle the list', async () => {
				const onExpand = jest.fn();
				const onCollapse = jest.fn();
				const { container } = render(
					<ExperimentalCollapsibleList
						collapseLabel="Show less"
						expandLabel="Show more items"
						onExpand={ onExpand }
						onCollapse={ onCollapse }
					>
						<div id="test">Test</div>
						<div>Test 2</div>
					</ExperimentalCollapsibleList>
				);

				let listItem = container.querySelector( '.list-item-collapse' );

				userEvent.click( listItem );
				expect( container ).toHaveTextContent( 'Test' );
				expect( container ).toHaveTextContent( 'Test 2' );
				expect( container ).not.toHaveTextContent( 'Show more items' );
				expect( container ).toHaveTextContent( 'Show less' );

				listItem = container.querySelector( '.list-item-collapse' );

				userEvent.click( listItem );
				await waitForElementToBeRemoved(
					container.querySelector( '#test' )
				);
				expect( container ).not.toHaveTextContent( 'Test' );
				expect( container ).not.toHaveTextContent( 'Test 2' );
				expect( container ).toHaveTextContent( 'Show more items' );
				expect( container ).not.toHaveTextContent( 'Show less' );
				expect( onExpand ).toHaveBeenCalledTimes( 1 );
				expect( onCollapse ).toHaveBeenCalledTimes( 1 );
			} );
		} );
	} );

	describe( 'Legacy List', () => {
		it( 'should have aria roles for items', () => {
			const clickHandler = jest.fn();
			const listItems = [
				{
					title: 'WooCommerce.com',
					href: 'https://woocommerce.com',
				},
				{
					title: 'Click me!',
					onClick: clickHandler,
				},
			];

			render( <List items={ listItems } /> );

			expect( screen.getAllByRole( 'menuitem' ) ).toHaveLength( 2 );
		} );

		it( 'should support `onClick` for items', () => {
			const clickHandler = jest.fn();
			const listItems = [
				{
					title: 'WooCommerce.com',
					href: 'https://woocommerce.com',
				},
				{
					title: 'Click me!',
					onClick: clickHandler,
				},
			];

			render( <List items={ listItems } /> );

			userEvent.click(
				screen.getByRole( 'menuitem', { name: 'Click me!' } )
			);

			expect( clickHandler ).toHaveBeenCalled();
		} );

		it( 'should set `data-link-type` on items', () => {
			const listItems = [
				{
					title: 'Add products',
					href: '/post-new.php?post_type=product',
					linkType: 'wp-admin',
				},
				{
					title: 'Market my store',
					href: '/admin.php?page=wc-admin&path=%2Fmarketing',
					linkType: 'wc-admin',
				},
				{
					title: 'WooCommerce.com',
					href: 'https://woocommerce.com',
					linkType: 'external',
				},
				{
					title: 'WordPress.org',
					href: 'https://wordpress.org',
				},
			];

			render( <List items={ listItems } /> );

			expect(
				screen.getByRole( 'menuitem', { name: 'Add products' } ).dataset
					.linkType
			).toBe( 'wp-admin' );
			expect(
				screen.getByRole( 'menuitem', { name: 'Market my store' } )
					.dataset.linkType
			).toBe( 'wc-admin' );
			expect(
				screen.getByRole( 'menuitem', { name: 'WooCommerce.com' } )
					.dataset.linkType
			).toBe( 'external' );
			expect(
				screen.getByRole( 'menuitem', { name: 'WordPress.org' } )
					.dataset.linkType
			).toBe( 'external' );
		} );

		it( 'should set `data-list-item-tag` on items', () => {
			const listItems = [
				{
					title: 'Add products',
					href: '/post-new.php?post_type=product',
					linkType: 'wp-admin',
					listItemTag: 'add-product',
				},
				{
					title: 'Market my store',
					href: '/admin.php?page=wc-admin&path=%2Fmarketing',
					linkType: 'wc-admin',
					listItemTag: 'marketing',
				},
				{
					title: 'WooCommerce.com',
					href: 'https://woocommerce.com',
					linkType: 'external',
					listItemTag: 'woocommerce.com-site',
				},
				{
					title: 'WordPress.org',
					href: 'https://wordpress.org',
				},
			];

			render( <List items={ listItems } /> );

			expect(
				screen.getByRole( 'menuitem', { name: 'Add products' } ).dataset
					.listItemTag
			).toBe( 'add-product' );
			expect(
				screen.getByRole( 'menuitem', { name: 'Market my store' } )
					.dataset.listItemTag
			).toBe( 'marketing' );
			expect(
				screen.getByRole( 'menuitem', { name: 'WooCommerce.com' } )
					.dataset.listItemTag
			).toBe( 'woocommerce.com-site' );
			expect(
				screen.getByRole( 'menuitem', { name: 'WordPress.org' } )
					.dataset.listItemTag
			).toBeUndefined();
		} );
	} );
} );
