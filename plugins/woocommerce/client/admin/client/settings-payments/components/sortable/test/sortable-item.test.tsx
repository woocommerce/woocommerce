import { beforeEach, describe, expect, it, vi, type Mock } from 'vitest';

/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import { useSortable } from '@dnd-kit/sortable';

/**
 * Internal dependencies
 */
import { SortableItem } from '../sortable-item';
vi.mock( '@dnd-kit/sortable', () => {
	const mock = {
		useSortable: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
describe( 'SortableItem', () => {
	const mockUseSortable = {
		attributes: {
			'aria-role': 'button',
		},
		listeners: {
			onClick: vi.fn(),
		},
		setNodeRef: vi.fn(),
		transform: null,
		transition: 'transform 250ms ease',
		isDragging: false,
	};
	beforeEach( () => {
		( useSortable as Mock ).mockReturnValue( mockUseSortable );
	} );
	it( 'should render children correctly', () => {
		render(
			<SortableItem id="test-id">
				<div>Test Content</div>
			</SortableItem>
		);
		expect( screen.getByText( 'Test Content' ) ).toBeInTheDocument();
	} );
	it( 'should apply supplied className', () => {
		const { container } = render(
			<SortableItem id="test-id" className="custom-class">
				<div>Test Content</div>
			</SortableItem>
		);
		expect( container.firstChild ).toHaveClass( 'custom-class' );
		expect( container.firstChild ).toHaveClass( 'sortable-item' );
	} );
	it( 'should apply dragging class when isDragging is true', () => {
		( useSortable as Mock ).mockReturnValue( {
			...mockUseSortable,
			isDragging: true,
		} );
		const { container } = render(
			<SortableItem id="test-id">
				<div>Test Content</div>
			</SortableItem>
		);
		expect( container.firstChild ).toHaveClass( 'is-dragging' );
	} );
	it( 'should pass additional props to the container div', () => {
		render(
			<SortableItem id="test-id" data-testid="sort-item">
				<div>Test Content</div>
			</SortableItem>
		);
		expect( screen.getByTestId( 'sort-item' ) ).toBeInTheDocument();
	} );
} );
