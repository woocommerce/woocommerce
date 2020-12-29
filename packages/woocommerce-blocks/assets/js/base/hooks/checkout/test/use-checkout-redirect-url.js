/**
 * External dependencies
 */
import TestRenderer, { act } from 'react-test-renderer';
import { createRegistry, RegistryProvider } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { useCheckoutRedirectUrl } from '../use-checkout-redirect-url';

const mockRedirectUrl = 'https://www.example.com/mock-check-out';
const mockUseCheckoutContext = {
	redirectUrl: mockRedirectUrl,
	dispatchActions: {
		setRedirectUrl: jest.fn(),
	},
};
jest.mock( '@woocommerce/base-context', () => ( {
	useCheckoutContext: () => mockUseCheckoutContext,
} ) );

describe( 'useCheckoutRedirectUrl', () => {
	let registry, renderer;

	const getWrappedComponents = ( Component ) => (
		<RegistryProvider value={ registry }>
			<Component />
		</RegistryProvider>
	);

	const getTestComponent = () => () => {
		const data = useCheckoutRedirectUrl();
		return <div { ...data } />;
	};

	beforeEach( () => {
		registry = createRegistry();
		renderer = null;
	} );

	it( 'redirectUrl matches the value provided by the checkout context', () => {
		const TestComponent = getTestComponent();

		act( () => {
			renderer = TestRenderer.create(
				getWrappedComponents( TestComponent )
			);
		} );

		const { redirectUrl } = renderer.root.findByType( 'div' ).props;

		expect( redirectUrl ).toBe( mockRedirectUrl );
	} );

	it( 'setRedirectUrl calls the correct action in the checkout context', () => {
		const checkoutUrl = 'https://www.example.com/check-out';
		const TestComponent = getTestComponent();

		act( () => {
			renderer = TestRenderer.create(
				getWrappedComponents( TestComponent )
			);
		} );

		const { setRedirectUrl } = renderer.root.findByType( 'div' ).props;

		setRedirectUrl( checkoutUrl );

		expect(
			mockUseCheckoutContext.dispatchActions.setRedirectUrl
		).toHaveBeenCalledWith( checkoutUrl );
	} );
} );
