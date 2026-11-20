/**
 * External dependencies
 */
import { fireEvent, render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { AgentConnect } from '../components/agent-connect';
import { trackReadiness } from '../tracks';

jest.mock( '../tracks', () => ( { trackReadiness: jest.fn() } ) );

const connect = {
	connected: false as const,
	notice: '' as const,
	url: 'https://example.com/wp-admin/admin-post.php',
	action: 'woocommerce_ai_tools_connect',
	nonce: 'abc',
};

describe( 'AgentConnect', () => {
	afterEach( () => {
		jest.restoreAllMocks();
		document.body.querySelector( 'form' )?.remove();
	} );

	it( 'posts Connect to WordPress.com to the connect action and tracks the click', () => {
		const submit = jest
			.spyOn( window.HTMLFormElement.prototype, 'submit' )
			.mockImplementation( () => {} );
		render( <AgentConnect connect={ connect } /> );

		fireEvent.click(
			screen.getByRole( 'button', { name: 'Connect to WordPress.com' } )
		);
		const form = document.body.querySelector( 'form' );
		expect( form ).toHaveAttribute( 'method', 'post' );
		expect( form ).toHaveAttribute( 'action', connect.url );
		expect( form?.querySelector( 'input[name="action"]' ) ).toHaveValue(
			connect.action
		);
		expect( form?.querySelector( 'input[name="_wpnonce"]' ) ).toHaveValue(
			connect.nonce
		);
		expect( submit ).toHaveBeenCalled();
		expect( trackReadiness ).toHaveBeenCalledWith( 'link_clicked', {
			surface: 'overview',
			area: 'agent_connect',
		} );
		expect(
			screen.queryByText( 'add WordPress in the AI app', {
				exact: false,
			} )
		).not.toBeInTheDocument();
	} );

	it( 'shows the success notice once, then the next step, without the button', () => {
		const connected = {
			connected: true as const,
			notice: 'connected' as const,
			url: '',
			action: '',
			nonce: '',
		};
		window.history.replaceState(
			null,
			'',
			'?page=wc-settings&wc_ai_connect=connected'
		);
		const { unmount } = render( <AgentConnect connect={ connected } /> );

		expect(
			screen.getByText( 'Connection successful' )
		).toBeInTheDocument();
		expect(
			screen.getByText( 'add WordPress in the AI app', {
				exact: false,
			} )
		).toBeInTheDocument();
		expect(
			screen.queryByRole( 'button', { name: 'Connect to WordPress.com' } )
		).not.toBeInTheDocument();

		fireEvent.click( screen.getByRole( 'button', { name: 'Close' } ) );
		expect(
			screen.queryByText( 'Connection successful' )
		).not.toBeInTheDocument();
		expect(
			screen.getByText( 'add WordPress in the AI app', {
				exact: false,
			} )
		).toBeInTheDocument();
		expect( window.location.search ).toBe( '?page=wc-settings' );

		unmount();
		render( <AgentConnect connect={ { ...connected, notice: '' } } /> );
		expect(
			screen.queryByText( 'Connection successful' )
		).not.toBeInTheDocument();
		expect(
			screen.getByText( 'add WordPress in the AI app', {
				exact: false,
			} )
		).toBeInTheDocument();
	} );

	it( 'keeps the button next to the error when connecting failed', () => {
		render( <AgentConnect connect={ { ...connect, notice: 'error' } } /> );

		expect(
			screen.getByText(
				'Your store couldn’t connect to WordPress.com. Try again.',
				{ selector: '.components-notice__content' }
			)
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: 'Connect to WordPress.com' } )
		).toBeInTheDocument();
	} );
} );
