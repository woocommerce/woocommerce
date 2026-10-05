import { beforeEach, describe, expect, it, vi } from 'vitest';
let {
	mockGetContext,
	mockGetElement,
	mockRegisteredStore,
	mockParentStore,
	mockGetClosestColor,
	mockParentToggle,
} = vi.hoisted( () => {
	const mockParentToggle = vi.fn();
	const mockGetContext = vi.fn();
	const mockGetElement = vi.fn();
	const mockRegisteredStore = null;
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
	const mockGetClosestColor = vi.fn();
	return {
		mockGetContext,
		mockGetElement,
		mockRegisteredStore,
		mockParentStore,
		mockGetClosestColor,
		mockParentToggle,
	};
} );

/**
 * Internal dependencies
 */
import type { ChipsStore } from '../frontend';
vi.mock(
	'@wordpress/interactivity',
	() => {
		const mock = {
			getContext: mockGetContext,
			getElement: mockGetElement,
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
vi.mock( '../../../utils/get-closest-color', () => {
	const mock = {
		getClosestColor: ( ...args: unknown[] ) =>
			mockGetClosestColor( ...args ),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
describe( 'product filter chips interactivity store', () => {
	beforeEach( async () => {
		vi.resetModules();
		mockGetContext.mockReset();
		mockGetElement.mockReset();
		mockParentToggle.mockReset();
		mockGetClosestColor.mockReset();
		mockRegisteredStore = null;
		vi.resetModules(),
			await ( async () => {
				await import( '../frontend' );
			} )();
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
				hidden: false,
			},
			{
				id: 'attribute-red',
				label: 'Red',
				value: 'red',
				selected: true,
				index: 1,
				hidden: false,
			},
		] );
	} );
	it( 'uses the default display limit when context limit is invalid', () => {
		if ( ! mockRegisteredStore ) {
			throw new Error( 'Chips store was not registered.' );
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
	it( 'returns empty items when parent store data is missing', () => {
		if ( ! mockRegisteredStore ) {
			throw new Error( 'Chips store was not registered.' );
		}
		mockGetContext.mockReturnValue( {} );
		expect( mockRegisteredStore.state.items ).toEqual( [] );
	} );
	it( 'does not forward toggle without current item', () => {
		if ( ! mockRegisteredStore ) {
			throw new Error( 'Chips store was not registered.' );
		}
		mockGetContext.mockReturnValue( {
			storeNamespace: 'woocommerce/product-filters',
		} );
		mockRegisteredStore.actions.toggle();
		expect( mockParentToggle ).not.toHaveBeenCalled();
	} );
	it( 'sets chip CSS variables when not already defined', () => {
		if ( ! mockRegisteredStore ) {
			throw new Error( 'Chips store was not registered.' );
		}
		const element = document.createElement( 'div' );
		mockGetElement.mockReturnValue( {
			ref: element,
		} );
		mockGetClosestColor.mockImplementation(
			(
				_el: Element,
				colorType: 'color' | 'backgroundColor'
			): string | null => {
				return colorType === 'backgroundColor'
					? 'rgb(255, 255, 255)'
					: 'rgb(0, 0, 0)';
			}
		);
		mockRegisteredStore.callbacks.initColors();
		expect(
			element.style.getPropertyValue(
				'--wc-product-filter-chips-background'
			)
		).toBe( 'rgb(255, 255, 255)' );
		expect(
			element.style.getPropertyValue( '--wc-product-filter-chips-text' )
		).toBe( 'rgb(0, 0, 0)' );
	} );
	it( 'does not calculate chip colors when already defined', () => {
		if ( ! mockRegisteredStore ) {
			throw new Error( 'Chips store was not registered.' );
		}
		const element = document.createElement( 'div' );
		element.style.setProperty(
			'--wc-product-filter-chips-text',
			'var(--wp--preset--color--contrast)'
		);
		element.style.setProperty(
			'--wc-product-filter-chips-background',
			'var(--wp--preset--color--base)'
		);
		mockGetElement.mockReturnValue( {
			ref: element,
		} );
		mockRegisteredStore.callbacks.initColors();
		expect( mockGetClosestColor ).not.toHaveBeenCalled();
		expect(
			element.style.getPropertyValue( '--wc-product-filter-chips-text' )
		).toBe( 'var(--wp--preset--color--contrast)' );
		expect(
			element.style.getPropertyValue(
				'--wc-product-filter-chips-background'
			)
		).toBe( 'var(--wp--preset--color--base)' );
	} );
	it( 'does not override theme contrast CSS variables from stylesheets', () => {
		if ( ! mockRegisteredStore ) {
			throw new Error( 'Chips store was not registered.' );
		}
		const style = document.createElement( 'style' );
		style.textContent = `.has-theme-vars {
			--wc-product-filter-chips-background: rgb(1, 2, 3);
			--wc-product-filter-chips-text: rgb(4, 5, 6);
		}`;
		document.head.appendChild( style );
		const element = document.createElement( 'div' );
		element.className = 'has-theme-vars';
		document.body.appendChild( element );
		mockGetElement.mockReturnValue( {
			ref: element,
		} );
		mockRegisteredStore.callbacks.initColors();
		expect( mockGetClosestColor ).not.toHaveBeenCalled();
		expect(
			element.style.getPropertyValue(
				'--wc-product-filter-chips-background'
			)
		).toBe( '' );
		expect(
			element.style.getPropertyValue( '--wc-product-filter-chips-text' )
		).toBe( '' );
		element.remove();
		style.remove();
	} );
} );
