/**
 * External dependencies
 */
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { List } from '../List';

jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn(),
} ) );

const mockGateway = {
	id: 'mock-gateway',
	title: 'Mock Gateway',
	plugins: [],
	postInstallScripts: [],
	requiredSettings: [],
	installed: false,
	needsSetup: false,
	enabled: false,
};

const defaultProps = {
	heading: 'Test heading',
	markConfigured: jest.fn(),
	recommendation: 'testId',
	paymentGateways: [ mockGateway ],
};

describe( 'PaymentGatewaySuggestions > List', () => {
	it( 'should display correct heading', () => {
		const { queryByText } = render( <List { ...defaultProps } /> );

		expect( queryByText( defaultProps.heading ) ).toBeInTheDocument();
	} );

	it( 'should display gateway title', () => {
		const { queryByText } = render( <List { ...defaultProps } /> );

		expect( queryByText( mockGateway.title ) ).toBeInTheDocument();
	} );

	it( 'should display the "Enable" button when setup is NOT required', () => {
		const { queryByRole } = render( <List { ...defaultProps } /> );

		expect( queryByRole( 'button' ) ).toHaveTextContent( 'Enable' );
	} );

	it( 'should display the "Get started" button when setup is required', () => {
		const props = {
			...defaultProps,
			paymentGateways: [
				{
					...mockGateway,
					needsSetup: true,
					plugins: [ 'test-plugins' ],
				},
			],
		};

		const { queryByRole } = render( <List { ...props } /> );

		expect( queryByRole( 'button' ) ).toHaveTextContent( 'Get started' );
	} );

	it( 'should display the SetupRequired component when appropriate', () => {
		const props = {
			...defaultProps,
			paymentGateways: [
				{
					...mockGateway,
					needsSetup: true,
					installed: true,
					plugins: [ 'test-plugin' ],
				},
			],
		};

		const { queryByText } = render( <List { ...props } /> );

		expect( queryByText( 'Setup required' ) ).toBeInTheDocument();
	} );

	it( 'should not display the SetupRequired component when not appropriate', () => {
		const props = {
			...defaultProps,
			paymentGateways: [
				{
					...mockGateway,
					needsSetup: true,
					installed: false,
					plugins: [ 'test-plugin' ],
				},
			],
		};

		const { queryByText } = render( <List { ...props } /> );

		expect( queryByText( 'Setup required' ) ).not.toBeInTheDocument();
	} );

	it( 'should display the Recommended ribbon when appropriate', () => {
		const props = {
			...defaultProps,
			recommendation: 'mock-gateway',
			paymentGateways: [
				{
					...mockGateway,
					id: 'mock-gateway',
					needsSetup: true,
				},
			],
		};

		const { queryByText } = render( <List { ...props } /> );

		expect( queryByText( 'Recommended' ) ).toBeInTheDocument();
	} );

	it( 'should not display the Recommended ribbon when gateway id does not match', () => {
		const props = {
			...defaultProps,
			recommendation: 'mock-gateway',
			paymentGateways: [
				{
					...mockGateway,
					id: 'mock-gateway-other',
					needsSetup: true,
				},
			],
		};

		const { queryByText } = render( <List { ...props } /> );

		expect( queryByText( 'Recommended' ) ).not.toBeInTheDocument();
	} );

	it( 'should display Manage button if enabled and does have setup', () => {
		const props = {
			...defaultProps,
			paymentGateways: [
				{
					...mockGateway,
					enabled: true,
				},
			],
		};

		const { queryByRole } = render( <List { ...props } /> );

		expect( queryByRole( 'button' ) ).toHaveTextContent( 'Manage' );
	} );

	it( 'should display Manage button for core plugins that are enabled', () => {
		const props = {
			...defaultProps,
			paymentGateways: [
				{
					...mockGateway,
					requiredSettings: [ 'just', 'kidding' ],
					enabled: true,
					plugins: [],
				},
			],
		};

		const { queryByRole } = render( <List { ...props } /> );

		expect( queryByRole( 'button' ) ).toHaveTextContent( 'Manage' );
	} );

	it( 'should display Manage button if it does have plugins and does not need setup', () => {
		const props = {
			...defaultProps,
			paymentGateways: [
				{
					...mockGateway,
					plugins: [ 'nope' ],
					needsSetup: false,
					enabled: true,
				},
			],
		};

		const { queryByRole } = render( <List { ...props } /> );

		expect( queryByRole( 'button' ) ).toHaveTextContent( 'Manage' );
	} );

	it( 'should display Finish Setup button when installed but not setup', () => {
		const props = {
			...defaultProps,
			paymentGateways: [
				{
					...mockGateway,
					plugins: [ 'nope' ],
					needsSetup: true,
					installed: true,
				},
			],
		};

		const { queryByRole } = render( <List { ...props } /> );

		expect( queryByRole( 'button' ) ).toHaveTextContent( 'Finish setup' );
	} );
} );
