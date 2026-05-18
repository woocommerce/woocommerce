/**
 * Internal dependencies
 */
import type { ChipsStore } from '../frontend';

const mockGetContext = jest.fn();
const mockParentToggle = jest.fn();

let mockRegisteredStore: ChipsStore | null = null;

const mockParentStore = {
	state: {
		selectableItems: [
			{
				id: 'attribute-blue',
				label: 'Blue',
				value: 'blue',
				selected: false,
			},
			{
				id: 'attribute-red',
				label: 'Red',
				value: 'red',
				selected: true,
			},
		],
	},
	actions: {
		toggle: mockParentToggle,
	},
};

jest.mock(
	'@wordpress/interactivity',
	() => ( {
		getContext: mockGetContext,
		store: jest.fn( ( _name, definition ) => {
			if ( definition ) {
				mockRegisteredStore = definition;
				return mockRegisteredStore;
			}
			return mockParentStore;
		} ),
	} ),
	{ virtual: true }
);

describe( 'product filter chips interactivity store', () => {
	beforeEach( () => {
		jest.resetModules();
		mockGetContext.mockReset();
		mockParentToggle.mockReset();
		mockRegisteredStore = null;

		jest.isolateModules( () => {
			require( '../frontend' );
		} );
	} );

	it( 'mirrors parent selectable items with child-owned index metadata', () => {
		if ( ! mockRegisteredStore ) {
			throw new Error( 'Chips store was not registered.' );
		}

		mockGetContext.mockReturnValue( {
			storeNamespace: 'woocommerce/product-filters',
		} );

		expect( mockRegisteredStore.state.items ).toEqual( [
			{
				id: 'attribute-blue',
				label: 'Blue',
				value: 'blue',
				selected: false,
				index: 0,
			},
			{
				id: 'attribute-red',
				label: 'Red',
				value: 'red',
				selected: true,
				index: 1,
			},
		] );
	} );

	it( 'forwards toggle to parent store with current item', () => {
		if ( ! mockRegisteredStore ) {
			throw new Error( 'Chips store was not registered.' );
		}

		const item = {
			id: 'attribute-blue',
			label: 'Blue',
			value: 'blue',
			selected: false,
			index: 0,
		};

		mockGetContext.mockReturnValue( {
			storeNamespace: 'woocommerce/product-filters',
			item,
		} );

		mockRegisteredStore.actions.toggle();

		expect( mockParentToggle ).toHaveBeenCalledWith( item );
	} );
} );
