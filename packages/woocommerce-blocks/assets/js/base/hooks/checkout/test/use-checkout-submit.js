/**
 * External dependencies
 */
import TestRenderer, { act } from 'react-test-renderer';
import { createRegistry, RegistryProvider } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { useCheckoutSubmit } from '../use-checkout-submit';

const mockUseCheckoutContext = {
	onSubmit: jest.fn(),
};
const mockUsePaymentMethodDataContext = {};
const mockUsePaymentMethods = {
	paymentMethods: {},
};
jest.mock( '@woocommerce/base-context', () => ( {
	useCheckoutContext: () => mockUseCheckoutContext,
	usePaymentMethodDataContext: () => mockUsePaymentMethodDataContext,
} ) );
jest.mock( '@woocommerce/base-hooks', () => ( {
	usePaymentMethods: () => mockUsePaymentMethods,
} ) );

describe( 'useCheckoutSubmit', () => {
	let registry, renderer;

	const getWrappedComponents = ( Component ) => (
		<RegistryProvider value={ registry }>
			<Component />
		</RegistryProvider>
	);

	const getTestComponent = () => () => {
		const data = useCheckoutSubmit();
		return <div { ...data } />;
	};

	beforeEach( () => {
		registry = createRegistry();
		renderer = null;
	} );

	it( 'onSubmit calls the correct action in the checkout context', () => {
		const TestComponent = getTestComponent();

		act( () => {
			renderer = TestRenderer.create(
				getWrappedComponents( TestComponent )
			);
		} );

		const { onSubmit } = renderer.root.findByType( 'div' ).props;

		onSubmit();

		expect( mockUseCheckoutContext.onSubmit ).toHaveBeenCalledTimes( 1 );
	} );
} );
