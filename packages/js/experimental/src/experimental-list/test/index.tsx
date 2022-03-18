/**
 * External dependencies
 */
import { act, render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ExperimentalList } from '../experimental-list';
import { ExperimentalListItem } from '../experimental-list-item';
import { ExperimentalCollapsibleList } from '../collapsible-list';

jest.mock( 'react-transition-group', () => {
	const EmptyTransition: React.FC< { component?: string } > = ( {
		children,
		component,
	} ) => {
		if ( component === 'ul' ) {
			return <ul>{ children }</ul>;
		}
		if ( component === 'ol' ) {
			return <ol>{ children }</ol>;
		}
		return <div>{ children }</div>;
	};
	return {
		...jest.requireActual( 'react-transition-group' ),
		TransitionGroup: EmptyTransition,
		CSSTransition: EmptyTransition,
	};
} );

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
				<ExperimentalListItem animation="none">
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
				'.woocommerce-experimental-list__item'
			);

			if ( listItem ) {
				userEvent.click( listItem );

				// it doesn't actually matter what key you hit here while handleKeyDown is mocked.
				userEvent.type( listItem, '{enter}' );
			}

			// TODO check that the button role was added.
			expect( queryByRole( 'button' ) ).toBeInTheDocument();
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

			const listItem = container.querySelector( '.list-item-collapse' );

			if ( listItem ) {
				userEvent.click( listItem );
			}
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
			const listItem = container.querySelector( '.list-item-collapse' );

			if ( listItem ) {
				userEvent.click( listItem );
			}
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

			if ( listItem ) {
				userEvent.click( listItem );
			}
			expect( container ).toHaveTextContent( 'Test' );
			expect( container ).toHaveTextContent( 'Test 2' );
			expect( container ).not.toHaveTextContent( 'Show more items' );
			expect( container ).toHaveTextContent( 'Show less' );

			listItem = container.querySelector( '.list-item-collapse' );

			if ( listItem ) {
				userEvent.click( listItem );
			}
			expect( container ).toHaveTextContent( 'Show more items' );
			expect( container ).not.toHaveTextContent( 'Show less' );
			expect( onExpand ).toHaveBeenCalledTimes( 1 );
			expect( onCollapse ).toHaveBeenCalledTimes( 1 );
		} );

		describe( 'staggering transition', () => {
			const StaggerTestComponent = ( { list }: { list: string[] } ) => {
				return (
					<ExperimentalCollapsibleList
						collapseLabel="Show less"
						expandLabel="Show more items"
						show={ 2 }
					>
						{ list.map( ( item ) => (
							<div key={ item }>{ item }</div>
						) ) }
					</ExperimentalCollapsibleList>
				);
			};

			beforeEach( () => {
				jest.useFakeTimers();
			} );

			afterEach( () => {
				jest.runOnlyPendingTimers();
				jest.useRealTimers();
			} );

			it( 'should only update the shown items at first', () => {
				const { queryByText, rerender } = render(
					<StaggerTestComponent
						list={ [ 'item-1', 'item-2', 'item-3' ] }
					/>
				);

				expect( queryByText( 'Show more items' ) ).toBeInTheDocument();

				act( () =>
					rerender(
						<StaggerTestComponent list={ [ 'item-1', 'item-3' ] } />
					)
				);

				expect( queryByText( 'Show more items' ) ).toBeInTheDocument();
				expect( queryByText( 'item-2' ) ).not.toBeInTheDocument();

				act( () => {
					jest.runAllTimers();
				} );
			} );

			it( 'should update the hidden items as well after a 500ms timeout', () => {
				const { queryByText, rerender } = render(
					<StaggerTestComponent
						list={ [ 'item-1', 'item-2', 'item-3' ] }
					/>
				);

				expect( queryByText( 'Show more items' ) ).toBeInTheDocument();

				act( () =>
					rerender(
						<StaggerTestComponent list={ [ 'item-1', 'item-3' ] } />
					)
				);

				expect( queryByText( 'item-3' ) ).not.toBeInTheDocument();

				act( () => {
					jest.advanceTimersByTime( 500 );
				} );
				expect(
					queryByText( 'Show more items' )
				).not.toBeInTheDocument();
				expect( queryByText( 'item-3' ) ).toBeInTheDocument();
			} );
		} );
	} );
} );
