/**
 * Internal dependencies
 */
import type { ChipsStore } from '../frontend';

const mockGetContext = jest.fn();
const mockGetElement = jest.fn();
const mockParentToggle = jest.fn();
const mockGetClosestColor = jest.fn();

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
		getElement: mockGetElement,
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

jest.mock( '../../../utils/get-closest-color', () => ( {
	getClosestColor: ( ...args: unknown[] ) => mockGetClosestColor( ...args ),
} ) );

describe( 'product filter chips interactivity store', () => {
	beforeEach( () => {
		jest.resetModules();
		mockGetContext.mockReset();
		mockGetElement.mockReset();
		mockParentToggle.mockReset();
		mockGetClosestColor.mockReset();
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

		mockGetElement.mockReturnValue( { ref: element } );
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

		mockGetElement.mockReturnValue( { ref: element } );

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

		mockGetElement.mockReturnValue( { ref: element } );

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
