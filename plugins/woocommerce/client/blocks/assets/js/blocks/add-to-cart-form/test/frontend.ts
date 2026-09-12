type QuantityActions = {
	increaseQuantity: ( event: { target: HTMLButtonElement } ) => void;
	decreaseQuantity: ( event: { target: HTMLButtonElement } ) => void;
};

let mockRegisteredActions: QuantityActions | null = null;

jest.mock(
	'@wordpress/interactivity',
	() => ( {
		store: jest.fn( ( name: string, definition ) => {
			if ( name === 'woocommerce/add-to-cart-form' ) {
				mockRegisteredActions = definition.actions;
			}

			return definition;
		} ),
	} ),
	{ virtual: true }
);

function loadActions(): QuantityActions {
	mockRegisteredActions = null;
	jest.isolateModules( () => jest.requireActual( '../frontend' ) );

	if ( ! mockRegisteredActions ) {
		throw new Error( 'Add to Cart Form store was not registered.' );
	}

	return mockRegisteredActions;
}

function createQuantityControl( {
	value,
	min,
	max,
	step,
	inputType = 'number',
}: {
	value?: string;
	min?: string;
	max?: string;
	step?: string;
	inputType?: string;
} = {} ) {
	const wrapper = document.createElement( 'div' );
	const decreaseButton = document.createElement( 'button' );
	const input = document.createElement( 'input' );
	const increaseButton = document.createElement( 'button' );
	const changeEvents: Event[] = [];

	input.type = inputType;
	input.className = 'wc-block-components-quantity-selector__input';
	if ( value !== undefined ) {
		input.value = value;
	}
	if ( min !== undefined ) {
		input.min = min;
	}
	if ( max !== undefined ) {
		input.max = max;
	}
	if ( step !== undefined ) {
		input.step = step;
	}

	wrapper.append( decreaseButton, input, increaseButton );
	wrapper.addEventListener( 'change', ( event ) => {
		changeEvents.push( event );
	} );

	return {
		wrapper,
		decreaseButton,
		input,
		increaseButton,
		changeEvents,
	};
}

describe( 'Add to Cart Form interactivity store', () => {
	beforeEach( () => {
		jest.resetModules();
	} );

	it.each( [
		{ label: 'increase', action: 'increaseQuantity' },
		{ label: 'decrease', action: 'decreaseQuantity' },
	] as const )(
		'ignores $label events without a quantity input',
		( { action } ) => {
			const actions = loadActions();
			const button = document.createElement( 'button' );
			const wrapper = document.createElement( 'div' );
			const changeEvents: Event[] = [];

			wrapper.append( button );
			wrapper.addEventListener( 'change', ( event ) => {
				changeEvents.push( event );
			} );

			expect( actions[ action ]( { target: button } ) ).toBeUndefined();
			expect( changeEvents ).toHaveLength( 0 );
			expect( wrapper.querySelector( 'input' ) ).toBeNull();
		}
	);

	it.each( [
		{
			label: 'absent numeric values',
			control: {},
		},
		{
			label: 'invalid numeric values',
			control: {
				value: 'invalid',
				min: 'invalid',
				max: 'invalid',
				step: 'invalid',
				inputType: 'text',
			},
		},
	] )( 'uses defaults for $label', ( { control } ) => {
		const actions = loadActions();
		const { increaseButton, input, changeEvents } =
			createQuantityControl( control );

		actions.increaseQuantity( { target: increaseButton } );

		expect( input.value ).toBe( '1' );
		expect( changeEvents ).toHaveLength( 1 );
		expect( changeEvents[ 0 ].bubbles ).toBe( true );
		expect( changeEvents[ 0 ].target ).toBe( input );
	} );

	it( 'rounds decimal steps to the input precision', () => {
		const actions = loadActions();
		const { increaseButton, input, changeEvents } = createQuantityControl( {
			value: '0.1',
			min: '0.1',
			max: '0.3',
			step: '0.1',
		} );

		actions.increaseQuantity( { target: increaseButton } );

		expect( input.value ).toBe( '0.2' );
		expect( changeEvents ).toHaveLength( 1 );
	} );

	it( 'increases exactly to max and dispatches one bubbling change event', () => {
		const actions = loadActions();
		const { increaseButton, input, changeEvents } = createQuantityControl( {
			value: '8',
			min: '2',
			max: '10',
			step: '2',
		} );

		actions.increaseQuantity( { target: increaseButton } );

		expect( input.value ).toBe( '10' );
		expect( changeEvents ).toHaveLength( 1 );
		expect( changeEvents[ 0 ].bubbles ).toBe( true );
		expect( changeEvents[ 0 ].target ).toBe( input );
	} );

	it( 'decreases exactly to min and dispatches one bubbling change event', () => {
		const actions = loadActions();
		const { decreaseButton, input, changeEvents } = createQuantityControl( {
			value: '4',
			min: '2',
			max: '10',
			step: '2',
		} );

		actions.decreaseQuantity( { target: decreaseButton } );

		expect( input.value ).toBe( '2' );
		expect( changeEvents ).toHaveLength( 1 );
		expect( changeEvents[ 0 ].bubbles ).toBe( true );
		expect( changeEvents[ 0 ].target ).toBe( input );
	} );

	it( 'rejects an increase above max without changing the input or dispatching an event', () => {
		const actions = loadActions();
		const { increaseButton, input, changeEvents } = createQuantityControl( {
			value: '10',
			min: '2',
			max: '10',
			step: '2',
		} );

		actions.increaseQuantity( { target: increaseButton } );

		expect( input.value ).toBe( '10' );
		expect( changeEvents ).toHaveLength( 0 );
	} );

	it( 'rejects a decrease below min without changing the input or dispatching an event', () => {
		const actions = loadActions();
		const { decreaseButton, input, changeEvents } = createQuantityControl( {
			value: '2',
			min: '2',
			max: '10',
			step: '2',
		} );

		actions.decreaseQuantity( { target: decreaseButton } );

		expect( input.value ).toBe( '2' );
		expect( changeEvents ).toHaveLength( 0 );
	} );
} );
