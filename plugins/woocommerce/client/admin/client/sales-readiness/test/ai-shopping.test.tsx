/**
 * External dependencies
 */
import {
	act,
	fireEvent,
	render,
	screen,
	waitFor,
} from '@testing-library/react';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import AiShopping from '../ai-shopping';
import type { ShoppingView } from '../types';

jest.mock( '@wordpress/api-fetch', () => jest.fn() );
jest.mock( '@wordpress/a11y', () => ( { speak: jest.fn() } ) );

const mockedFetch = apiFetch as unknown as jest.Mock;

const step = (
	fields: Partial< ShoppingView[ 'checklist' ][ 'steps' ][ number ] >
) => ( {
	id: 'https',
	state: 'required' as const,
	title: 'Use a secure connection (HTTPS)',
	badge: { intent: 'low' as const, text: 'Required' },
	lines: [ 'AI assistants only connect to stores with a secure connection.' ],
	link: {
		href: '/wp-admin/site-health.php',
		text: 'View Site Health',
		sr: '',
	},
	...fields,
} );

const pages = [
	{ value: '', label: 'Choose a page' },
	{ value: '12', label: 'Returns' },
];

const view = ( fields: Partial< ShoppingView > = {} ): ShoppingView => ( {
	parent: {
		href: '/wp-admin/admin.php?page=wc-settings&tab=agentic-commerce',
		text: 'AI tools',
	},
	title: 'AI shopping',
	description: 'Control how AI assistants shop your store.',
	ucp: {
		enabled: false,
		blocked: true,
		title: 'Allow AI assistants to shop your store',
		description: 'Your products can be found by AI assistants.',
		learn_more: { href: 'https://ucp.dev/', text: 'Learn how it works' },
		message: '',
		path: '/wc/v3/agent-readiness/ucp',
		error_on:
			'AI shopping wasn’t turned on. Reload the page and try again.',
		error_off:
			'AI shopping wasn’t turned off. Reload the page and try again.',
		unsaved: 'Save your changes to see the updated status.',
	},
	coming_soon: null,
	visibility: {
		title: 'Product visibility',
		description: 'AI assistants can see the same products as shoppers.',
		empty: null,
		counts: [
			{
				id: 'available',
				label: 'Visible',
				count: '8,400',
				tip: '',
				tip_label: '',
				link: {
					href: '/edit.php?agent_readiness=available',
					text: 'View products',
				},
			},
			{
				id: 'hidden',
				label: 'Hidden',
				count: '2',
				tip: 'Products set to Hidden in Catalog visibility.',
				tip_label: 'More information about hidden products',
				link: {
					href: '/edit.php?agent_readiness=hidden',
					text: 'View products',
				},
			},
		],
	},
	checklist: {
		title: 'Setup checklist',
		badge: { intent: 'none', text: '1 step left' },
		summary:
			'Finish 1 required step to get your store ready for AI shopping.',
		steps: [ step( {} ) ],
		done: [
			{
				id: 'guest_checkout',
				title: 'Allow guest checkout',
				text: 'Guest checkout lets shoppers pay.',
			},
		],
		show_done: 'Show 1 completed step',
		hide_done: 'Hide completed steps',
	},
	policies: {
		title: 'Store policies',
		description: 'AI assistants share these pages with shoppers.',
		fields: [
			{
				id: 'woocommerce_refund_returns_page_id',
				name: 'woocommerce_refund_returns_page_id',
				label: 'Refund and returns policy',
				value: '',
				options: pages,
			},
			{
				id: 'woocommerce_terms_page_id',
				name: 'woocommerce_terms_page_id',
				label: 'Terms and conditions',
				value: '12',
				options: pages,
			},
		],
	},
	connection: {
		title: 'Connection URL',
		description: 'A separate address, just for AI assistants.',
		url: 'https://example.com/.well-known/ucp',
		copy: 'Copy',
		copied: 'Copied',
		test: {
			href: 'https://ucpplayground.com',
			text: 'Test the connection in UCP Playground',
		},
	},
	...fields,
} );

const renderPage = ( data: ShoppingView ) =>
	render(
		<form id="mainform">
			<AiShopping view={ data } />
		</form>
	);

const save = () => screen.getByRole( 'button', { name: 'Save' } );
const toggle = () =>
	screen.getByRole( 'checkbox', {
		name: /Allow AI assistants to shop your store/,
	} );

describe( 'AiShopping', () => {
	const { location } = window;

	beforeEach( () => {
		mockedFetch.mockReset();
		Object.defineProperty( window, 'location', {
			configurable: true,
			value: {
				...location,
				href: 'https://example.com/wp-admin/admin.php?page=wc-settings#x',
				hash: '',
				assign: jest.fn(),
			},
		} );
	} );

	afterEach( () => {
		window.onbeforeunload = null;
		Object.defineProperty( window, 'location', {
			configurable: true,
			value: location,
		} );
	} );

	it( 'shows the trail back to AI tools, and Save, off until a field changes, posting the settings form', () => {
		renderPage( view() );

		expect(
			screen.getByRole( 'link', { name: 'AI tools' } )
		).toHaveAttribute( 'href', view().parent.href );
		expect(
			screen.getByRole( 'heading', { level: 1, name: 'AI shopping' } )
		).toBeInTheDocument();
		expect( save() ).toHaveAttribute( 'form', 'mainform' );
		expect( save() ).toHaveAttribute( 'name', 'save' );
		expect( save() ).toHaveAttribute( 'aria-disabled', 'true' );
	} );

	it( 'keeps the switch off while required steps are left, and lists them with Required and where to fix them', () => {
		renderPage( view() );

		expect( toggle() ).not.toBeChecked();
		expect( toggle() ).toBeDisabled();
		expect(
			screen.getByText(
				'Finish 1 required step to get your store ready for AI shopping.'
			)
		).toBeInTheDocument();
		expect( screen.getByText( '1 step left' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Required' ) ).toBeInTheDocument();
		expect(
			screen.getByRole( 'link', { name: 'View Site Health' } )
		).toHaveAttribute( 'href', '/wp-admin/site-health.php' );
		expect( screen.queryByText( /sales readiness/i ) ).toBeNull();
	} );

	it( 'names the policy a draft page’s Edit page button is for, for screen readers', () => {
		renderPage(
			view( {
				checklist: {
					...view().checklist,
					steps: [
						step( {
							id: 'refund_returns_page',
							state: 'open',
							badge: null,
							title: 'Publish your refund and returns policy page',
							link: {
								href: '/post.php?post=5&action=edit',
								text: 'Edit page',
								sr: 'Edit your refund and returns policy page',
							},
						} ),
					],
				},
			} )
		);

		expect(
			screen.getByRole( 'link', {
				name: 'Edit your refund and returns policy page',
			} )
		).toHaveAttribute( 'href', '/post.php?post=5&action=edit' );
		expect( screen.queryByText( 'Required' ) ).toBeNull();
	} );

	it( 'says what to do in coming soon mode, with a way to change store visibility', () => {
		renderPage(
			view( {
				coming_soon: {
					text: 'Your store is in coming soon mode, so AI assistants can’t shop it yet.',
					link: {
						href: '/wp-admin/admin.php?page=wc-settings&tab=site-visibility',
						text: 'Change store visibility',
					},
				},
			} )
		);

		expect(
			screen.getByText( /coming soon mode/, { selector: 'span' } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'link', { name: 'Change store visibility' } )
		).toHaveAttribute(
			'href',
			'/wp-admin/admin.php?page=wc-settings&tab=site-visibility'
		);
	} );

	it( 'with every step done and AI shopping on, has no steps left and folds the completed ones', () => {
		renderPage(
			view( {
				ucp: {
					...view().ucp,
					enabled: true,
					blocked: false,
					message:
						'AI shopping is on. AI assistants can now find and shop your store.',
				},
				checklist: {
					...view().checklist,
					badge: null,
					summary: 'Your store is ready for AI shopping.',
					steps: [],
				},
			} )
		);

		expect( toggle() ).toBeChecked();
		expect( toggle() ).not.toBeDisabled();
		expect(
			screen.getByText(
				'AI shopping is on. AI assistants can now find and shop your store.'
			)
		).toBeInTheDocument();
		expect( screen.queryByText( /step left/ ) ).toBeNull();

		fireEvent.click(
			screen.getByRole( 'button', { name: 'Show 1 completed step' } )
		);
		expect(
			screen.getByText( 'Allow guest checkout' )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: 'Hide completed steps' } )
		).toBeInTheDocument();
	} );

	it( 'says product counts come after the first check, before there is a report', () => {
		renderPage(
			view( {
				visibility: {
					...view().visibility,
					empty: 'Product counts show after the first AI sales readiness check.',
					counts: [],
				},
			} )
		);

		expect(
			screen.getByText(
				'Product counts show after the first AI sales readiness check.'
			)
		).toBeInTheDocument();
		expect( screen.queryByText( 'View products' ) ).toBeNull();
	} );

	it( 'shows each count with a link to its products, and explains the ones that need it in a popover button', () => {
		renderPage( view() );

		expect( screen.getByText( '8,400' ) ).toBeInTheDocument();
		expect(
			screen.getAllByRole( 'link', { name: 'View products' } )
		).toHaveLength( 2 );
		expect(
			screen.getByRole( 'button', {
				name: 'More information about hidden products',
			} )
		).toBeInTheDocument();
	} );

	it( 'submits the policy pages as the settings fields, and warns before leaving once one changes', () => {
		const { container } = renderPage(
			view( {
				policies: {
					...view().policies,
					// No published pages to choose from.
					fields: view().policies.fields.map( ( field ) => ( {
						...field,
						value: '',
						options: [ pages[ 0 ] ],
					} ) ),
				},
			} )
		);
		const input = ( id: string ) =>
			container.querySelector( `input[type="hidden"][name="${ id }"]` );

		expect(
			input( 'woocommerce_refund_returns_page_id' )?.getAttribute(
				'value'
			)
		).toBe( '' );
		expect(
			input( 'woocommerce_terms_page_id' )?.getAttribute( 'value' )
		).toBe( '' );
		expect(
			screen.getByRole( 'combobox', {
				name: 'Refund and returns policy',
			} )
		).toBeInTheDocument();
		expect( window.onbeforeunload ).toBeNull();
	} );

	it( 'marks the form changed when a policy page is chosen, and lets Save leave without the prompt', () => {
		const { container } = renderPage( view() );

		fireEvent.change(
			screen.getByRole( 'combobox', {
				name: 'Refund and returns policy',
			} ),
			{ target: { value: '12' } }
		);

		expect(
			container.querySelector(
				'input[name="woocommerce_refund_returns_page_id"]'
			)?.value
		).toBe( '12' );
		expect(
			container.querySelector( 'input[name="woocommerce_terms_page_id"]' )
				?.value
		).toBe( '12' );
		expect( save() ).not.toHaveAttribute( 'aria-disabled', 'true' );
		expect( typeof window.onbeforeunload ).toBe( 'function' );

		save().addEventListener( 'click', ( event ) => event.preventDefault() );
		fireEvent.click( save() );
		expect( window.onbeforeunload ).toBeNull();
	} );

	it( 'saves the switch on its own and reloads the page', async () => {
		mockedFetch.mockResolvedValue( { enabled: true } );
		renderPage( view( { ucp: { ...view().ucp, blocked: false } } ) );

		fireEvent.click( toggle() );
		await waitFor( () => expect( mockedFetch ).toHaveBeenCalled() );
		await screen.findByRole( 'checkbox', { name: /Allow AI assistants/ } );

		expect( mockedFetch ).toHaveBeenCalledWith( {
			path: '/wc/v3/agent-readiness/ucp',
			method: 'POST',
			data: { enabled: true },
		} );
		expect( window.location.assign ).toHaveBeenCalledWith(
			'https://example.com/wp-admin/admin.php?page=wc-settings'
		);
	} );

	it( 'does not reload over unsaved fields, and says to save them', async () => {
		mockedFetch.mockResolvedValue( { enabled: false } );
		renderPage(
			view( { ucp: { ...view().ucp, enabled: true, blocked: false } } )
		);
		fireEvent.change(
			screen.getByRole( 'combobox', {
				name: 'Refund and returns policy',
			} ),
			{ target: { value: '12' } }
		);

		fireEvent.click( toggle() );
		await waitFor( () => expect( mockedFetch ).toHaveBeenCalled() );
		await screen.findByRole( 'checkbox', { name: /Allow AI assistants/ } );

		expect( window.location.assign ).not.toHaveBeenCalled();
		expect( toggle() ).not.toBeChecked();
		expect(
			screen.getByText( 'Save your changes to see the updated status.' )
		).toBeInTheDocument();
	} );

	it( 'puts the switch back and says why when it can’t be turned on', async () => {
		mockedFetch.mockRejectedValue( {
			code: 'woocommerce_ucp_required_checks_failing',
			message: 'Finish the required steps first.',
			data: { status: 409 },
		} );
		renderPage( view( { ucp: { ...view().ucp, blocked: false } } ) );

		fireEvent.click( toggle() );
		await waitFor( () => expect( mockedFetch ).toHaveBeenCalled() );
		await screen.findByRole( 'checkbox', { name: /Allow AI assistants/ } );

		expect( toggle() ).not.toBeChecked();
		expect( toggle() ).not.toBeDisabled();
		expect(
			screen.getByText( 'Finish the required steps first.' )
		).toBeInTheDocument();
		expect( window.location.assign ).not.toHaveBeenCalled();
	} );

	it( 'says which way the switch failed when there is no reason to give, such as when offline', async () => {
		mockedFetch.mockRejectedValue( {
			code: 'fetch_error',
			message: 'You are probably offline.',
		} );
		renderPage( view( { ucp: { ...view().ucp, blocked: false } } ) );

		fireEvent.click( toggle() );
		await screen.findByText(
			'AI shopping wasn’t turned on. Reload the page and try again.'
		);
		expect( toggle() ).not.toBeChecked();
	} );

	it( 'says AI shopping was not turned off when turning it off fails', async () => {
		mockedFetch.mockRejectedValue( {
			code: 'internal_server_error',
			message: 'There has been a critical error.',
		} );
		renderPage(
			view( { ucp: { ...view().ucp, enabled: true, blocked: false } } )
		);

		fireEvent.click( toggle() );
		await screen.findByText(
			'AI shopping wasn’t turned off. Reload the page and try again.'
		);
		expect( toggle() ).toBeChecked();
	} );

	it( 'shows the connection URL read only, with Copy and a link to test it', () => {
		renderPage( view() );

		const url = screen.getByRole( 'textbox', { name: 'Connection URL' } );
		expect( url ).toHaveValue( 'https://example.com/.well-known/ucp' );
		expect( url ).toHaveAttribute( 'readonly' );
		expect(
			screen.getByRole( 'button', { name: 'Copy' } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'link', {
				name: /Test the connection in UCP Playground/,
			} )
		).toHaveAttribute( 'href', 'https://ucpplayground.com' );
		expect(
			screen.getByRole( 'link', { name: /Learn how it works/ } )
		).toHaveAttribute( 'href', 'https://ucp.dev/' );
	} );

	it( 'goes to a policy field from a link, on load and from the Setup checklist on this page', () => {
		const scroll = jest.fn();
		Element.prototype.scrollIntoView = scroll;
		window.location.hash = '#woocommerce_terms_page_id';
		renderPage( view() );

		expect( scroll ).toHaveBeenCalled();
		expect( document.activeElement ).toBe(
			screen.getByLabelText( 'Terms and conditions' )
		);

		scroll.mockClear();
		window.location.hash = '#woocommerce_refund_returns_page_id';
		act( () => {
			window.dispatchEvent( new HashChangeEvent( 'hashchange' ) );
		} );

		expect( scroll ).toHaveBeenCalled();
		expect( document.activeElement ).toBe(
			screen.getByLabelText( 'Refund and returns policy' )
		);
		window.location.hash = '';
	} );

	it( 'ignores a hash that is not a valid encoded id', () => {
		window.location.hash = '#%E0%A4%A';
		renderPage( view() );

		expect(
			screen.getByLabelText( 'Terms and conditions' )
		).toBeInTheDocument();
		window.location.hash = '';
	} );

	it( 'copies the connection URL, says Copied, then goes back to Copy', async () => {
		jest.useFakeTimers();
		const writeText = jest.fn().mockResolvedValue( undefined );
		Object.defineProperty( navigator, 'clipboard', {
			value: { writeText },
			configurable: true,
		} );
		renderPage( view() );

		fireEvent.click( screen.getByRole( 'button', { name: 'Copy' } ) );

		expect( writeText ).toHaveBeenCalledWith(
			'https://example.com/.well-known/ucp'
		);
		expect(
			await screen.findByRole( 'button', { name: 'Copied' } )
		).toBeInTheDocument();
		act( () => {
			jest.advanceTimersByTime( 2000 );
		} );
		expect(
			screen.getByRole( 'button', { name: 'Copy' } )
		).toBeInTheDocument();
		jest.useRealTimers();
	} );

	it( 'keeps Copy when the browser has no clipboard or refuses to write', async () => {
		Object.defineProperty( navigator, 'clipboard', {
			value: undefined,
			configurable: true,
		} );
		renderPage( view() );
		fireEvent.click( screen.getByRole( 'button', { name: 'Copy' } ) );
		expect(
			screen.getByRole( 'button', { name: 'Copy' } )
		).toBeInTheDocument();

		Object.defineProperty( navigator, 'clipboard', {
			value: {
				writeText: jest.fn().mockRejectedValue( new Error( 'denied' ) ),
			},
			configurable: true,
		} );
		fireEvent.click( screen.getByRole( 'button', { name: 'Copy' } ) );
		await waitFor( () =>
			expect(
				screen.getByRole( 'button', { name: 'Copy' } )
			).toBeInTheDocument()
		);
		expect(
			screen.queryByRole( 'button', { name: 'Copied' } )
		).not.toBeInTheDocument();
	} );
} );
