/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { Configure, validateFields } from '../Configure';

const mockGateway = {
	id: 'mock-gateway',
	title: 'Mock Gateway',
	connectionUrl: 'http://mockgateway.com/connect',
	setupHelpText: 'Help text',
	settingsUrl:
		'https://example.com/wp-admin/admin.php?page=wc-settings&tab=checkout&section=mock-gateway',
	requiredSettings: [
		{
			id: 'api_key',
			label: 'API key',
			type: 'text',
			default: '',
		},
		{
			id: 'api_secret',
			label: 'API secret',
			type: 'text',
			default: '',
		},
	],
};

const defaultProps = {
	markConfigured: () => {},
	recordConnectStartEvent: () => {},
	paymentGateway: mockGateway,
};

describe( 'Configure', () => {
	it( 'should show help text', () => {
		const { queryByText } = render( <Configure { ...defaultProps } /> );

		expect( queryByText( 'Help text' ) ).toBeInTheDocument();
	} );

	it( 'should render a button with the connection URL', () => {
		const { container } = render( <Configure { ...defaultProps } /> );

		const button = container.querySelector( 'a' );
		expect( button.textContent ).toBe( 'Connect' );
		expect( button.href ).toBe( mockGateway.connectionUrl );
	} );

	it( 'should render fields when no connection URL exists', () => {
		const props = {
			...defaultProps,
			paymentGateway: {
				...mockGateway,
				connectionUrl: null,
			},
		};
		const { container } = render( <Configure { ...props } /> );

		const inputs = container.querySelectorAll( 'input' );
		expect( inputs.length ).toBe( 2 );
		expect( inputs[ 0 ].placeholder ).toBe( 'API key' );
		expect( inputs[ 1 ].placeholder ).toBe( 'API secret' );
	} );

	it( 'should render the set up button when no connection URL or fields exist', () => {
		const props = {
			...defaultProps,
			paymentGateway: {
				...mockGateway,
				connectionUrl: null,
				requiredSettings: [],
			},
		};
		const { container } = render( <Configure { ...props } /> );

		const button = container.querySelector( 'a' );
		expect( button.textContent ).toBe( 'Get started' );
		expect( button.href ).toBe( mockGateway.settingsUrl );
	} );
} );

describe( 'validateFields', () => {
	it( 'should return an empty object when no errors exist', () => {
		const values = {
			api_key: '123',
			api_secret: '123',
		};
		const errors = validateFields( values, mockGateway.requiredSettings );

		expect( errors ).toMatchObject( {} );
	} );

	it( 'should return the errors using field labels when errors exist', () => {
		const values = {
			api_key: '123',
			api_secret: null,
		};
		const errors = validateFields( values, mockGateway.requiredSettings );

		expect( errors ).toMatchObject( {
			api_secret: 'Please enter your API secret',
		} );
	} );
} );
