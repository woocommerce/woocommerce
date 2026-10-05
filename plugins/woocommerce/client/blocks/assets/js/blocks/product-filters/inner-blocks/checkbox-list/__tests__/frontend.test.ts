import { beforeEach, describe, expect, it, vi } from 'vitest';
let { mockGetContext, mockRegisteredStore, mockParentStore, mockParentToggle } =
	vi.hoisted( () => {
		const mockParentToggle = vi.fn();
		const mockGetContext = vi.fn();
		const mockRegisteredStore = null;
		const mockParentStore = {
			state: {
				selectableItems: [
					{
						id: 'attribute-blue',
						label: 'Blue',
						value: 'blue',
						selected: false,
						count: 4,
					},
					{
						id: 'attribute-red',
						label: 'Red',
						value: 'red',
						selected: true,
						count: 7,
					},
				],
			},
			actions: {
				toggle: mockParentToggle,
			},
		};
		return {
			mockGetContext,
			mockRegisteredStore,
			mockParentStore,
			mockParentToggle,
		};
	} );

/**
 * Internal dependencies
 */
import type { CheckboxListStore } from '../frontend';
vi.mock(
	'@wordpress/interactivity',
	() => {
		const mock = {
			getContext: mockGetContext,
			store: vi.fn( ( _name, definition ) => {
				if ( definition ) {
					mockRegisteredStore = definition;
					return mockRegisteredStore;
				}
				return mockParentStore;
			} ),
		};
		return Object.defineProperties(
			{
				default: mock,
			},
			Object.getOwnPropertyDescriptors( mock )
		);
	},
	{
		virtual: true,
	}
);
describe( 'product filter checkbox list interactivity store', () => {
	beforeEach( async () => {
		vi.resetModules();
		mockGetContext.mockReset();
		mockParentToggle.mockReset();
		mockRegisteredStore = null;
		vi.resetModules(),
			await ( async () => {
				await import( '../frontend' );
			} )();
	} );
	it( 'mirrors parent selectable items with child-owned index metadata', () => {
		if ( ! mockRegisteredStore ) {
			throw new Error( 'Checkbox list store was not registered.' );
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
				count: 4,
				index: 0,
				hidden: false,
			},
			{
				id: 'attribute-red',
				label: 'Red',
				value: 'red',
				selected: true,
				count: 7,
				index: 1,
				hidden: false,
			},
		] );
	} );
	it( 'uses the default display limit when context limit is invalid', () => {
		if ( ! mockRegisteredStore ) {
			throw new Error( 'Checkbox list store was not registered.' );
		}
		mockGetContext.mockReturnValue( {
			storeNamespace: 'woocommerce/product-filters',
			displayLimit: -1,
			isExpanded: false,
		} );
		expect( mockRegisteredStore.state.items[ 0 ].hidden ).toBe( false );
	} );
	it( 'forwards toggle to parent store with current item', () => {
		if ( ! mockRegisteredStore ) {
			throw new Error( 'Checkbox list store was not registered.' );
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
	it( 'returns empty items when parent store data is missing', () => {
		if ( ! mockRegisteredStore ) {
			throw new Error( 'Checkbox list store was not registered.' );
		}
		mockGetContext.mockReturnValue( {} );
		expect( mockRegisteredStore.state.items ).toEqual( [] );
	} );
	it( 'does not forward toggle without current item', () => {
		if ( ! mockRegisteredStore ) {
			throw new Error( 'Checkbox list store was not registered.' );
		}
		mockGetContext.mockReturnValue( {
			storeNamespace: 'woocommerce/product-filters',
		} );
		mockRegisteredStore.actions.toggle();
		expect( mockParentToggle ).not.toHaveBeenCalled();
	} );
} );
