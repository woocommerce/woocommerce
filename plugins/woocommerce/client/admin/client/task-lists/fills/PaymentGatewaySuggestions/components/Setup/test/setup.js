/**
 * External dependencies
 */
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { Setup } from '..';
import { enqueueScript } from '~/utils/enqueue-script';

jest.mock( '@woocommerce/components', () => {
	const originalModule = jest.requireActual( '@woocommerce/components' );

	return {
		DynamicForm: () => <div />,
		Plugins: () => <div />,
		Stepper: originalModule.Stepper,
	};
} );

jest.mock( '@woocommerce/settings' );
jest.mock( '~/utils/enqueue-script' );

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
		expect( enqueueScript ).toHaveBeenCalledWith(
			'mock-post-install-script'
		);
	} );
} );
