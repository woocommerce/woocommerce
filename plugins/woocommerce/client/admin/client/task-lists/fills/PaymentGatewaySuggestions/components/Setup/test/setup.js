/**
 * External dependencies
 */
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { dispatch } from '@wordpress/data';
import { pluginsStore } from '@woocommerce/data';
import { setLocaleData, resetLocaleData } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { Setup } from '../Setup';
import { enqueueScript } from '~/utils/enqueue-script';

jest.mock( '@woocommerce/components', () => {
	const originalModule = jest.requireActual( '@woocommerce/components' );

	return {
		DynamicForm: () => <div />,
		Plugins: originalModule.Plugins,
		Stepper: originalModule.Stepper,
	};
} );

jest.mock( '@woocommerce/settings' );
jest.mock( '~/utils/enqueue-script' );
jest.mock( '~/lib/notices', () => ( {
	createNoticesFromResponse: jest.fn(),
} ) );

let installAndActivatePlugins;

beforeEach( () => {
	installAndActivatePlugins = jest
		.spyOn( dispatch( pluginsStore ), 'installAndActivatePlugins' )
		.mockResolvedValue( {
			data: { activated: [ 'woocommerce-gateway-stripe' ] },
		} );
} );

afterEach( () => {
	resetLocaleData( {}, 'woocommerce' );
} );

const mockGateway = {
	id: 'mock-gateway',
	title: 'Mock Gateway',
	plugins: [],
	postInstallScripts: [],
	installed: false,
};

const defaultProps = {
	markConfigured: () => {},
	recordConnectStartEvent: () => {},
	paymentGateway: mockGateway,
};

describe( 'Setup', () => {
	it.each( [ 'Install & enable', 'تثبيت وتفعيل 日本語 🛒' ] )(
		'waits for a click before installing a gateway with button text %s',
		async ( buttonText ) => {
			setLocaleData(
				{ 'Install & enable': [ buttonText ] },
				'woocommerce'
			);
			render(
				<Setup
					{ ...defaultProps }
					paymentGateway={ {
						...mockGateway,
						title: 'Platby Žluťoučký kôň العربية 日本語 🛒',
						plugins: [ 'woocommerce-gateway-stripe' ],
					} }
				/>
			);

			expect( installAndActivatePlugins ).not.toHaveBeenCalled();
			fireEvent.click(
				screen.getByRole( 'button', { name: buttonText } )
			);
			await waitFor( () => {
				expect( installAndActivatePlugins ).toHaveBeenCalledTimes( 1 );
			} );
			expect( installAndActivatePlugins ).toHaveBeenCalledWith( [
				'woocommerce-gateway-stripe',
			] );
		}
	);

	it( 'should show a configure step', () => {
		const { queryByText } = render( <Setup { ...defaultProps } /> );

		expect(
			queryByText( 'Configure your Mock Gateway account' )
		).toBeInTheDocument();
	} );

	it( 'should not show install step when no plugins are needed', () => {
		const { queryByText } = render( <Setup { ...defaultProps } /> );

		expect( queryByText( 'Install' ) ).not.toBeInTheDocument();
	} );

	it( 'should show install step when plugins are needed', () => {
		const props = {
			...defaultProps,
			paymentGateway: { ...mockGateway, plugins: [ 'mock-plugin' ] },
		};

		const { queryByText } = render( <Setup { ...props } /> );

		expect( queryByText( 'Install Mock Gateway' ) ).toBeInTheDocument();
	} );

	it( 'should enqueue post install scripts when plugin installation completes', async () => {
		const props = {
			...defaultProps,
			paymentGateway: {
				...mockGateway,
				postInstallScripts: [ 'mock-post-install-script' ],
			},
		};

		render( <Setup { ...props } /> );

		expect( enqueueScript ).toHaveBeenCalledTimes( 1 );
		await waitFor( () =>
			expect( enqueueScript ).toHaveBeenCalledWith(
				'mock-post-install-script'
			)
		);
	} );
} );
