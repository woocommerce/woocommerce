/**
 * Internal dependencies
 */
import PaymentMethodConfig from '../payment-method-config';

const MockComponent = () => null;
const mockReactElement = <MockComponent />;

const baseConfig = {
	name: 'test-payment-method',
	label: mockReactElement,
	ariaLabel: 'Test Payment Method',
	content: mockReactElement,
	edit: mockReactElement,
	canMakePayment: () => true,
};

describe( 'PaymentMethodConfig', () => {
	it( 'accepts a valid string for placeOrderButtonLabel', () => {
		let config = {};
		expect( () => {
			config = new PaymentMethodConfig( {
				...baseConfig,
				placeOrderButtonLabel: 'Custom Label',
			} );
		} ).not.toThrow();
		expect( config.placeOrderButtonLabel ).toBe( 'Custom Label' );
	} );

	it( 'accepts undefined for placeOrderButtonLabel', () => {
		let config = {};
		expect( () => {
			config = new PaymentMethodConfig( {
				...baseConfig,
				placeOrderButtonLabel: undefined,
			} );
		} ).not.toThrow();
		expect( config.placeOrderButtonLabel ).toBe( undefined );
	} );

	it( 'throws TypeError when placeOrderButtonLabel is not a string', () => {
		expect( () => {
			new PaymentMethodConfig( {
				...baseConfig,
				// @ts-expect-error Testing runtime validation of invalid type
				placeOrderButtonLabel: 123,
			} );
		} ).toThrow(
			'The placeOrderButtonLabel property for the payment method must be a string'
		);
	} );

	it( 'accepts a valid function for placeOrderButton', () => {
		const CustomButton = () => null;
		let config = {};
		expect( () => {
			config = new PaymentMethodConfig( {
				...baseConfig,
				placeOrderButton: CustomButton,
			} );
		} ).not.toThrow();
		expect( config.placeOrderButton ).toBe( CustomButton );
	} );

	it( 'accepts undefined for placeOrderButton', () => {
		expect( () => {
			new PaymentMethodConfig( {
				...baseConfig,
				placeOrderButton: undefined,
			} );
		} ).not.toThrow();
	} );

	it( 'throws TypeError when placeOrderButton is not a function', () => {
		expect( () => {
			new PaymentMethodConfig( {
				...baseConfig,
				// @ts-expect-error Testing runtime validation of invalid type
				placeOrderButton: 'not-a-function',
			} );
		} ).toThrow(
			'The placeOrderButton property for the payment method must be a React component (function)'
		);
		expect( () => {
			new PaymentMethodConfig( {
				...baseConfig,
				// @ts-expect-error Testing runtime validation of invalid type
				placeOrderButton: 123,
			} );
		} ).toThrow( TypeError );
		expect( () => {
			new PaymentMethodConfig( {
				...baseConfig,
				// @ts-expect-error Testing runtime validation of invalid type
				placeOrderButton: { render: () => null },
			} );
		} ).toThrow( TypeError );
	} );

	it( 'logs a warning when both placeOrderButton and placeOrderButtonLabel are provided', () => {
		const CustomButton = () => null;
		new PaymentMethodConfig( {
			...baseConfig,
			placeOrderButton: CustomButton,
			placeOrderButtonLabel: 'Custom Label',
		} );

		expect( console ).toHaveWarnedWith(
			'Payment method "test-payment-method" provided both placeOrderButton and placeOrderButtonLabel. Using placeOrderButton.'
		);
	} );
} );
