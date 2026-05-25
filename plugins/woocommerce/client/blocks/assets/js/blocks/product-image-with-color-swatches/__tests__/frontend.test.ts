/**
 * Internal dependencies
 */
import type { ProductImageWithColorSwatchesStore } from '../frontend';

const mockGetContext = jest.fn();
let mockRegisteredStore: ProductImageWithColorSwatchesStore | null = null;

jest.mock(
	'@wordpress/interactivity',
	() => ( {
		getContext: mockGetContext,
		store: jest.fn( ( _name, definition ) => {
			mockRegisteredStore = definition;
			return mockRegisteredStore;
		} ),
	} ),
	{ virtual: true }
);

const defaultImage = {
	id: 10,
	src: 'default.jpg',
	srcset: '',
	sizes: '',
	alt: 'Default',
	width: '300',
	height: '300',
};

const blueImage = {
	id: 11,
	src: 'blue.jpg',
	srcset: '',
	sizes: '',
	alt: 'Blue',
	width: '300',
	height: '300',
};

const blueItem = {
	id: 'blue',
	label: 'Blue',
	value: 'blue',
	image: blueImage,
};

const redItem = {
	id: 'red',
	label: 'Red',
	value: 'red',
	image: {
		...blueImage,
		id: 12,
		src: 'red.jpg',
		alt: 'Red',
	},
};

describe( 'product image with color swatches interactivity store', () => {
	beforeEach( () => {
		jest.resetModules();
		mockGetContext.mockReset();
		mockRegisteredStore = null;

		jest.isolateModules( () => {
			require( '../frontend' );
		} );
	} );

	it( 'provides selectable items with selected state from context', () => {
		if ( ! mockRegisteredStore ) {
			throw new Error( 'Store was not registered.' );
		}

		mockGetContext.mockReturnValue( {
			items: [ blueItem, redItem ],
			selectedItemId: 'red',
			defaultImage,
		} );

		expect( mockRegisteredStore.state.selectableItems ).toEqual( [
			{ ...blueItem, selected: false },
			{ ...redItem, selected: true },
		] );
	} );

	it( 'returns selected image data when a swatch is selected', () => {
		if ( ! mockRegisteredStore ) {
			throw new Error( 'Store was not registered.' );
		}

		mockGetContext.mockReturnValue( {
			items: [ blueItem, redItem ],
			selectedItemId: 'blue',
			defaultImage,
		} );

		expect( mockRegisteredStore.state.currentImage ).toEqual( blueImage );
	} );

	it( 'falls back to default image when no swatch is selected', () => {
		if ( ! mockRegisteredStore ) {
			throw new Error( 'Store was not registered.' );
		}

		mockGetContext.mockReturnValue( {
			items: [ blueItem ],
			selectedItemId: null,
			defaultImage,
		} );

		expect( mockRegisteredStore.state.currentImage ).toEqual( defaultImage );
	} );

	it( 'toggles selected swatch id', () => {
		if ( ! mockRegisteredStore ) {
			throw new Error( 'Store was not registered.' );
		}

		const context = {
			items: [ blueItem ],
			selectedItemId: null,
			defaultImage,
		};
		mockGetContext.mockReturnValue( context );

		mockRegisteredStore.actions.toggle( blueItem );
		expect( context.selectedItemId ).toBe( 'blue' );

		mockRegisteredStore.actions.toggle( blueItem );
		expect( context.selectedItemId ).toBeNull();
	} );

	it( 'does not select disabled items', () => {
		if ( ! mockRegisteredStore ) {
			throw new Error( 'Store was not registered.' );
		}

		const context = {
			items: [ blueItem ],
			selectedItemId: null,
			defaultImage,
		};
		mockGetContext.mockReturnValue( context );

		mockRegisteredStore.actions.toggle( {
			...blueItem,
			disabled: true,
		} );

		expect( context.selectedItemId ).toBeNull();
	} );
} );
