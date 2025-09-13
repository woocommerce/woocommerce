/**
 * Tests for the PaymentMethodConfig class with placeOrderButton support.
 */

import PaymentMethodConfig from '../../client/blocks/assets/js/blocks-registry/payment-methods/payment-method-config';

// Mock React components
const MockContent = () => <div>Payment Method Content</div>;
const MockEdit = () => <div>Payment Method Edit</div>;
const MockSavedToken = () => <div>Saved Token Component</div>;
const MockPlaceOrderButton = () => <button>Custom Place Order</button>;

describe( 'PaymentMethodConfig', () => {
	const baseConfig = {
		name: 'test-payment-method',
		label: 'Test Payment Method',
		ariaLabel: 'Test Payment Method',
		content: <MockContent />,
		edit: <MockEdit />,
		canMakePayment: jest.fn( () => true ),
	};

	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'creates a valid payment method config with minimal required properties', () => {
		const config = new PaymentMethodConfig( baseConfig );

		expect( config.name ).toBe( 'test-payment-method' );
		expect( config.label ).toBe( 'Test Payment Method' );
		expect( config.ariaLabel ).toBe( 'Test Payment Method' );
		expect( config.content ).toBe( <MockContent /> );
		expect( config.edit ).toBe( <MockEdit /> );
		expect( config.canMakePaymentFromConfig ).toBe( baseConfig.canMakePayment );
		expect( config.placeOrderButtonLabel ).toBeUndefined();
		expect( config.placeOrderButton ).toBeUndefined();
	} );

	it( 'creates a payment method config with placeOrderButtonLabel', () => {
		const configWithLabel = {
			...baseConfig,
			placeOrderButtonLabel: 'Pay with Test Method',
		};

		const config = new PaymentMethodConfig( configWithLabel );

		expect( config.placeOrderButtonLabel ).toBe( 'Pay with Test Method' );
	} );

	it( 'creates a payment method config with placeOrderButton function', () => {
		const configWithButton = {
			...baseConfig,
			placeOrderButton: MockPlaceOrderButton,
		};

		const config = new PaymentMethodConfig( configWithButton );

		expect( config.placeOrderButton ).toBe( MockPlaceOrderButton );
	} );

	it( 'creates a payment method config with both placeOrderButtonLabel and placeOrderButton', () => {
		const configWithBoth = {
			...baseConfig,
			placeOrderButtonLabel: 'Pay with Test Method',
			placeOrderButton: MockPlaceOrderButton,
		};

		const config = new PaymentMethodConfig( configWithBoth );

		expect( config.placeOrderButtonLabel ).toBe( 'Pay with Test Method' );
		expect( config.placeOrderButton ).toBe( MockPlaceOrderButton );
	} );

	it( 'sets default savedTokenComponent when not provided', () => {
		const config = new PaymentMethodConfig( baseConfig );

		expect( config.savedTokenComponent ).toBeDefined();
		expect( config.savedTokenComponent ).not.toBeNull();
	} );

	it( 'uses provided savedTokenComponent', () => {
		const configWithSavedToken = {
			...baseConfig,
			savedTokenComponent: <MockSavedToken />,
		};

		const config = new PaymentMethodConfig( configWithSavedToken );

		expect( config.savedTokenComponent ).toBe( <MockSavedToken /> );
	} );

	it( 'sets paymentMethodId to name when not provided', () => {
		const config = new PaymentMethodConfig( baseConfig );

		expect( config.paymentMethodId ).toBe( 'test-payment-method' );
	} );

	it( 'uses provided paymentMethodId', () => {
		const configWithId = {
			...baseConfig,
			paymentMethodId: 'custom-payment-method-id',
		};

		const config = new PaymentMethodConfig( configWithId );

		expect( config.paymentMethodId ).toBe( 'custom-payment-method-id' );
	} );

	it( 'sets default supports configuration', () => {
		const config = new PaymentMethodConfig( baseConfig );

		expect( config.supports ).toEqual( {
			showSavedCards: false,
			showSaveOption: false,
			features: [ 'products' ],
		} );
	} );

	it( 'uses provided supports configuration', () => {
		const configWithSupports = {
			...baseConfig,
			supports: {
				showSavedCards: true,
				showSaveOption: true,
				features: [ 'products', 'subscriptions' ],
			},
		};

		const config = new PaymentMethodConfig( configWithSupports );

		expect( config.supports ).toEqual( {
			showSavedCards: true,
			showSaveOption: true,
			features: [ 'products', 'subscriptions' ],
		} );
	} );

	it( 'sets default icons to null when not provided', () => {
		const config = new PaymentMethodConfig( baseConfig );

		expect( config.icons ).toBeNull();
	} );

	it( 'uses provided icons', () => {
		const icons = [ 'visa', 'mastercard' ];
		const configWithIcons = {
			...baseConfig,
			icons,
		};

		const config = new PaymentMethodConfig( configWithIcons );

		expect( config.icons ).toBe( icons );
	} );

	it( 'validates required properties', () => {
		expect( () => {
			new PaymentMethodConfig( {
				// Missing name
				label: 'Test Payment Method',
				ariaLabel: 'Test Payment Method',
				content: <MockContent />,
				edit: <MockEdit />,
				canMakePayment: jest.fn( () => true ),
			} );
		} ).toThrow( 'The name property for the payment method must be a string' );
	} );

	it( 'validates placeOrderButtonLabel type', () => {
		expect( () => {
			new PaymentMethodConfig( {
				...baseConfig,
				placeOrderButtonLabel: 123, // Should be string
			} );
		} ).toThrow( 'The placeOrderButtonLabel property for the payment method must be a string' );
	} );

	it( 'validates placeOrderButton type', () => {
		expect( () => {
			new PaymentMethodConfig( {
				...baseConfig,
				placeOrderButton: 'not-a-function', // Should be function
			} );
		} ).toThrow( 'The placeOrderButton property for the payment method must be a function that returns a React component' );
	} );

	it( 'validates icons type', () => {
		expect( () => {
			new PaymentMethodConfig( {
				...baseConfig,
				icons: 'not-an-array', // Should be array or null
			} );
		} ).toThrow( 'The icons property for the payment method must be an array or null.' );
	} );

	it( 'validates paymentMethodId type', () => {
		expect( () => {
			new PaymentMethodConfig( {
				...baseConfig,
				paymentMethodId: 123, // Should be string
			} );
		} ).toThrow( 'The paymentMethodId property for the payment method must be a string or undefined' );
	} );

	it( 'validates ariaLabel type', () => {
		expect( () => {
			new PaymentMethodConfig( {
				...baseConfig,
				ariaLabel: 123, // Should be string
			} );
		} ).toThrow( 'The ariaLabel property for the payment method must be a string' );
	} );

	it( 'validates canMakePayment type', () => {
		expect( () => {
			new PaymentMethodConfig( {
				...baseConfig,
				canMakePayment: 'not-a-function', // Should be function
			} );
		} ).toThrow( 'The canMakePayment property for the payment method must be a function.' );
	} );

	it( 'validates supports.showSavedCards type', () => {
		expect( () => {
			new PaymentMethodConfig( {
				...baseConfig,
				supports: {
					showSavedCards: 'not-a-boolean', // Should be boolean
				},
			} );
		} ).toThrow( 'If the payment method includes the `supports.showSavedCards` property, it must be a boolean' );
	} );

	it( 'validates supports.showSaveOption type', () => {
		expect( () => {
			new PaymentMethodConfig( {
				...baseConfig,
				supports: {
					showSaveOption: 'not-a-boolean', // Should be boolean
				},
			} );
		} ).toThrow( 'If the payment method includes the `supports.showSaveOption` property, it must be a boolean' );
	} );

	it( 'validates supports.features type', () => {
		expect( () => {
			new PaymentMethodConfig( {
				...baseConfig,
				supports: {
					features: 'not-an-array', // Should be array
				},
			} );
		} ).toThrow( 'The features property for the payment method must be an array or undefined.' );
	} );

	it( 'handles backward compatibility for savePaymentInfo', () => {
		const consoleSpy = jest.spyOn( console, 'warn' ).mockImplementation( () => {} );

		const configWithSavePaymentInfo = {
			...baseConfig,
			supports: {
				savePaymentInfo: true, // Deprecated property
			},
		};

		const config = new PaymentMethodConfig( configWithSavePaymentInfo );

		expect( config.supports.showSavedCards ).toBe( true );
		expect( consoleSpy ).toHaveBeenCalledWith(
			expect.stringContaining( 'Passing savePaymentInfo when registering a payment method.' )
		);

		consoleSpy.mockRestore();
	} );
} );
