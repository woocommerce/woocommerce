/**
 * External dependencies
 */
import { act, fireEvent, render, screen } from '@testing-library/react';
import { createElement } from '@wordpress/element';
import {
	DndContext,
	KeyboardSensor,
	MouseSensor,
	TouchSensor,
	useSensor,
} from '@dnd-kit/core';
import {
	SortableContext,
	horizontalListSortingStrategy,
	sortableKeyboardCoordinates,
	useSortable,
	verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import {
	restrictToHorizontalAxis,
	restrictToVerticalAxis,
} from '@dnd-kit/modifiers';

/**
 * Internal dependencies
 */
import {
	SortableList,
	SortableListDefaultHandle,
	SortableListHandle,
	SortableListItem,
} from '../';
import { moveSortableItem } from '../utils';

const mockPointerDown = jest.fn();
let mockDndContextProps: Record< string, any > = {};
let mockSortableContextProps: Record< string, any > = {};

jest.mock( '@dnd-kit/core', () => ( {
	DndContext: jest.fn( ( props ) => {
		mockDndContextProps = props;
		return <div data-testid="dnd-context">{ props.children }</div>;
	} ),
	KeyboardSensor: jest.fn(),
	MouseSensor: jest.fn(),
	TouchSensor: jest.fn(),
	closestCenter: jest.fn(),
	useSensor: jest.fn( ( sensor, options ) => ( { options, sensor } ) ),
	useSensors: jest.fn( ( ...sensors ) => sensors ),
} ) );

jest.mock( '@dnd-kit/modifiers', () => ( {
	restrictToHorizontalAxis: jest.fn(),
	restrictToVerticalAxis: jest.fn(),
} ) );

jest.mock( '@dnd-kit/sortable', () => ( {
	SortableContext: jest.fn( ( props ) => {
		mockSortableContextProps = props;
		return <div data-testid="sortable-context">{ props.children }</div>;
	} ),
	arrayMove: jest.fn( ( items, oldIndex, newIndex ) => {
		const nextItems = [ ...items ];
		const [ item ] = nextItems.splice( oldIndex, 1 );
		nextItems.splice( newIndex, 0, item );
		return nextItems;
	} ),
	horizontalListSortingStrategy: jest.fn(),
	sortableKeyboardCoordinates: jest.fn(),
	useSortable: jest.fn( ( { disabled, id } ) => ( {
		attributes: { 'aria-describedby': `instructions-${ id }` },
		isDragging: id === 'dragging',
		listeners: disabled ? undefined : { onPointerDown: mockPointerDown },
		setActivatorNodeRef: jest.fn(),
		setNodeRef: jest.fn(),
		transform: null,
		transition: undefined,
	} ) ),
	verticalListSortingStrategy: jest.fn(),
} ) );

type TestItem = {
	id: string;
	label: string;
	locked?: boolean;
};

const items: TestItem[] = [
	{ id: 'card', label: 'Credit card' },
	{ id: 'bank', label: 'Direct bank transfer' },
	{ id: 'cod', label: 'Cash on delivery' },
];

const renderSortableList = (
	props: Partial<
		React.ComponentProps< typeof SortableList< TestItem > >
	> = {}
) => {
	const onChange = jest.fn();

	render(
		<SortableList
			items={ items }
			onChange={ onChange }
			getItemId={ ( item ) => item.id }
			getItemLabel={ ( item ) => item.label }
			{ ...props }
		>
			{ ( { items: renderedItems, getItemId, getItemDisabled } ) =>
				renderedItems.map( ( item ) => (
					<SortableListItem
						key={ item.id }
						id={ getItemId( item ) }
						disabled={ getItemDisabled( item ) }
					>
						<SortableListDefaultHandle />
						<span>{ item.label }</span>
					</SortableListItem>
				) )
			}
		</SortableList>
	);

	return { onChange };
};

describe( 'SortableList', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		mockDndContextProps = {};
		mockSortableContextProps = {};
	} );

	it( 'renders items and custom children', () => {
		renderSortableList();

		expect( screen.getByText( 'Credit card' ) ).toBeInTheDocument();
		expect(
			screen.getByText( 'Direct bank transfer' )
		).toBeInTheDocument();
		expect( screen.getByText( 'Cash on delivery' ) ).toBeInTheDocument();
	} );

	it( 'calls onChange with reordered data on drop', () => {
		const { onChange } = renderSortableList();

		act( () => {
			mockDndContextProps.onDragEnd( {
				active: { id: 'card' },
				over: { id: 'cod' },
			} );
		} );

		expect( onChange ).toHaveBeenCalledWith( [
			items[ 1 ],
			items[ 2 ],
			items[ 0 ],
		] );
		expect(
			mockDndContextProps.accessibility.announcements.onDragEnd( {
				active: { id: 'card' },
				over: { id: 'cod' },
			} )
		).toBe( 'Credit card dropped.' );
	} );

	it( 'does not call onChange when dropped on itself', () => {
		const { onChange } = renderSortableList();

		act( () => {
			mockDndContextProps.onDragEnd( {
				active: { id: 'card' },
				over: { id: 'card' },
			} );
		} );

		expect( onChange ).not.toHaveBeenCalled();
	} );

	it( 'does not call onChange when dropped without a target', () => {
		const { onChange } = renderSortableList();

		act( () => {
			mockDndContextProps.onDragEnd( {
				active: { id: 'card' },
				over: null,
			} );
		} );

		expect( onChange ).not.toHaveBeenCalled();
	} );

	it( 'keeps disabled items fixed in place', () => {
		const fixedItems = [
			items[ 0 ],
			{ id: 'locked', label: 'Locked', locked: true },
			items[ 1 ],
			items[ 2 ],
		];

		expect(
			moveSortableItem( {
				activeId: 'cod',
				getItemDisabled: ( item: TestItem ) => !! item.locked,
				getItemId: ( item: TestItem ) => item.id,
				items: fixedItems,
				overId: 'card',
			} )
		).toEqual( [
			items[ 2 ],
			{ id: 'locked', label: 'Locked', locked: true },
			items[ 0 ],
			items[ 1 ],
		] );
	} );

	it( 'does not reorder when a disabled item is the drop target', () => {
		const onChange = jest.fn();
		const fixedItems = [
			items[ 0 ],
			{ id: 'locked', label: 'Locked', locked: true },
			items[ 1 ],
		];

		render(
			<SortableList
				items={ fixedItems }
				onChange={ onChange }
				getItemId={ ( item ) => item.id }
				getItemDisabled={ ( item ) => !! item.locked }
			>
				{ fixedItems.map( ( item ) => (
					<SortableListItem key={ item.id } id={ item.id }>
						<SortableListDefaultHandle />
						{ item.label }
					</SortableListItem>
				) ) }
			</SortableList>
		);

		act( () => {
			mockDndContextProps.onDragEnd( {
				active: { id: 'card' },
				over: { id: 'locked' },
			} );
		} );

		expect( onChange ).not.toHaveBeenCalled();
		expect( useSortable ).toHaveBeenCalledWith(
			expect.objectContaining( { disabled: true, id: 'locked' } )
		);
	} );

	it( 'attaches drag listeners to custom handles', () => {
		render(
			<SortableList
				items={ items }
				onChange={ jest.fn() }
				getItemId={ ( item ) => item.id }
			>
				<SortableListItem id="card">
					<SortableListHandle>Move</SortableListHandle>
					Credit card
				</SortableListItem>
			</SortableList>
		);

		fireEvent.pointerDown(
			screen.getByRole( 'button', { name: 'Drag to reorder' } )
		);

		expect( mockPointerDown ).toHaveBeenCalled();
	} );

	it( 'renders the default handle with an accessible label', () => {
		renderSortableList();

		expect(
			screen.getAllByRole( 'button', { name: 'Drag to reorder' } )
		).toHaveLength( 3 );
	} );

	it( 'configures the keyboard sensor', () => {
		renderSortableList();

		expect( useSensor ).toHaveBeenCalledWith( KeyboardSensor, {
			coordinateGetter: sortableKeyboardCoordinates,
		} );
		expect( useSensor ).toHaveBeenCalledWith( MouseSensor, {} );
		expect( useSensor ).toHaveBeenCalledWith( TouchSensor, {} );
	} );

	it( 'uses horizontal strategy and modifier for horizontal orientation', () => {
		renderSortableList( { orientation: 'horizontal' } );

		expect( mockSortableContextProps.strategy ).toBe(
			horizontalListSortingStrategy
		);
		expect( mockDndContextProps.modifiers ).toEqual( [
			restrictToHorizontalAxis,
		] );
	} );

	it( 'uses vertical strategy and modifier by default', () => {
		renderSortableList();

		expect( mockSortableContextProps.strategy ).toBe(
			verticalListSortingStrategy
		);
		expect( mockDndContextProps.modifiers ).toEqual( [
			restrictToVerticalAxis,
		] );
	} );

	it( 'calls drag start and end callbacks', () => {
		const onDragStart = jest.fn();
		const onDragEnd = jest.fn();
		renderSortableList( { onDragStart, onDragEnd } );

		const startEvent = { active: { id: 'card' } };
		const endEvent = { active: { id: 'card' }, over: { id: 'bank' } };

		act( () => {
			mockDndContextProps.onDragStart( startEvent );
			mockDndContextProps.onDragEnd( endEvent );
		} );

		expect( onDragStart ).toHaveBeenCalledWith( startEvent );
		expect( onDragEnd ).toHaveBeenCalledWith( endEvent );
		expect(
			mockDndContextProps.accessibility.announcements.onDragStart( {
				active: { id: 'card' },
			} )
		).toBe( 'Credit card picked up.' );
	} );

	it( 'overrides dnd-kit default announcements with localized strings', () => {
		renderSortableList();

		const { announcements } = mockDndContextProps.accessibility;

		// Supplying announcements is what suppresses dnd-kit's built-in
		// English defaults, preventing duplicate screen reader announcements.
		expect( announcements ).toBeDefined();
		expect(
			announcements.onDragOver( {
				active: { id: 'card' },
				over: { id: 'bank' },
			} )
		).toBe( 'Credit card moved near Direct bank transfer.' );
		expect(
			announcements.onDragOver( {
				active: { id: 'card' },
				over: { id: 'card' },
			} )
		).toBeUndefined();
		expect( announcements.onDragCancel( { active: { id: 'card' } } ) ).toBe(
			'Credit card was dropped. Reordering cancelled.'
		);
	} );

	it( 'passes localized keyboard instructions to DndContext', () => {
		renderSortableList( { instructions: 'Use the keyboard to reorder.' } );

		expect( DndContext ).toHaveBeenCalled();
		expect(
			mockDndContextProps.accessibility.screenReaderInstructions.draggable
		).toBe( 'Use the keyboard to reorder.' );
	} );

	it( 'passes item ids to SortableContext', () => {
		renderSortableList();

		expect( SortableContext ).toHaveBeenCalled();
		expect( mockSortableContextProps.items ).toEqual( [
			'card',
			'bank',
			'cod',
		] );
	} );
} );
