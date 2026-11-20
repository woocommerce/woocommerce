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

const url =
	'https://example.com/wp-admin/admin-post.php?action=woocommerce_ai_tools_connect&_wpnonce=abc';

describe( 'AgentConnect', () => {
	it( 'links Connect to WordPress.com to the connect action and tracks the click', () => {
		render(
			<AgentConnect connect={ { connected: false, notice: '', url } } />
		);

		const link = screen.getByRole( 'link', {
			name: 'Connect to WordPress.com',
		} );
		expect( link ).toHaveAttribute( 'href', url );
		fireEvent.click( link );
		expect( trackReadiness ).toHaveBeenCalledWith( 'link_clicked', {
			surface: 'overview',
			area: 'agent_connect',
		} );
		expect( screen.queryByText( /add WordPress/ ) ).not.toBeInTheDocument();
	} );

	it( 'shows the success notice and what to do next after connecting, without the button', () => {
		render(
			<AgentConnect
				connect={ { connected: true, notice: 'connected', url: '' } }
			/>
		);

		expect(
			screen.getByText( 'Connection successful' )
		).toBeInTheDocument();
		expect(
			screen.getByText( /add WordPress in the AI app/ )
		).toBeInTheDocument();
		expect(
			screen.queryByRole( 'link', { name: 'Connect to WordPress.com' } )
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
	} );

	it( 'keeps the button next to the error when connecting failed', () => {
		render(
			<AgentConnect
				connect={ { connected: false, notice: 'error', url } }
			/>
		);

		expect(
			document.querySelector( '.components-notice.is-error' )
		).toHaveTextContent( /couldn’t connect/ );
		expect(
			screen.getByRole( 'link', { name: 'Connect to WordPress.com' } )
		).toBeInTheDocument();
	} );
} );
