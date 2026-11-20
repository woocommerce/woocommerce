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
		expect( screen.queryByText( /add WordPress/ ) ).not.toBeInTheDocument();
		submit.mockRestore();
		form?.remove();
	} );

	it( 'shows the success notice and what to do next after connecting, without the button', () => {
		window.history.replaceState(
			null,
			'',
			'?page=wc-settings&wc_ai_connect=connected'
		);
		render(
			<AgentConnect
				connect={ {
					connected: true,
					notice: 'connected',
					url: '',
					action: '',
					nonce: '',
				} }
			/>
		);

		expect(
			screen.getByText( 'Connection successful' )
		).toBeInTheDocument();
		expect(
			screen.getByText( /add WordPress in the AI app/ )
		).toBeInTheDocument();
		expect(
			screen.queryByRole( 'button', { name: 'Connect to WordPress.com' } )
		).not.toBeInTheDocument();

		fireEvent.click(
			screen.getByRole( 'button', { name: /close|dismiss/i } )
		);
		expect(
			screen.queryByText( 'Connection successful' )
		).not.toBeInTheDocument();
		expect(
			screen.getByText( /add WordPress in the AI app/ )
		).toBeInTheDocument();
		expect( window.location.search ).not.toContain( 'wc_ai_connect' );
	} );

	it( 'keeps the button next to the error when connecting failed', () => {
		render( <AgentConnect connect={ { ...connect, notice: 'error' } } /> );

		expect(
			document.querySelector( '.components-notice.is-error' )
		).toHaveTextContent( /couldn’t connect/ );
		expect(
			screen.getByRole( 'button', { name: 'Connect to WordPress.com' } )
		).toBeInTheDocument();
	} );
} );
