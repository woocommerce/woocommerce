/**
 * External dependencies
 */
import TestRenderer, { act } from 'react-test-renderer';
import { createRegistry, RegistryProvider } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { useCheckoutSubmit } from '../use-checkout-submit';

const mockSubmitLabel = 'Submit!';
const mockUseCheckoutContext = {
	submitLabel: mockSubmitLabel,
	onSubmit: jest.fn(),
};
jest.mock( '@woocommerce/base-context', () => ( {
	useCheckoutContext: () => mockUseCheckoutContext,
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

	it( 'submitLabel matches the value provided by the checkout context', () => {
		const TestComponent = getTestComponent();

		act( () => {
			renderer = TestRenderer.create(
				getWrappedComponents( TestComponent )
			);
		} );

		const { submitLabel } = renderer.root.findByType( 'div' ).props;

		expect( submitLabel ).toBe( mockSubmitLabel );
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
