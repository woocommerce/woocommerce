/**
 * External dependencies
 */
import { act } from 'react';
import { createRoot } from 'react-dom/client';
import type { ReactNode } from 'react';

jest.mock( '@wordpress/admin-ui', () => ( {
	NavigableRegion: ( {
		children,
		className,
	}: {
		children: ReactNode;
		className?: string;
	} ) => <div className={ className }>{ children }</div>,
} ) );

const useViewConfigRuntime = jest.fn();

jest.mock( '../view-config-runtime', () => ( {
	useViewConfigRuntime: ( request: unknown ) =>
		useViewConfigRuntime( request ),
} ) );

jest.mock( '../diagnostics', () => ( {
	error: jest.fn(),
} ) );

/**
 * Internal dependencies
 */
import { SettingsUIPage } from '../settings-ui-page';
import type { SettingsUISchema } from '../types';

globalThis.IS_REACT_ACT_ENVIRONMENT = true;

const schema: SettingsUISchema = {
	id: 'products',
	section: 'default',
	save: { adapter: 'form_post' },
	viewConfig: {
		supported: true,
		kind: 'woocommerce-settings',
		name: 'products:default',
		version: 1,
	},
	groups: {
		contact: {
			id: 'contact',
			fields: [
				{
					id: 'email',
					label: 'Email',
					type: 'text',
					value: 'old@example.com',
				},
				{
					id: 'phone',
					label: 'Phone',
					type: 'text',
					value: '555-0100',
				},
			],
		},
	},
};

const validConfig = {
	kind: 'woocommerce-settings',
	name: 'products:default',
	version: 1,
	form: { fields: [ { id: 'contact', children: [ 'email' ] } ] },
};

const renderPage = () => {
	const form = document.createElement( 'form' );
	form.id = 'mainform';
	document.body.appendChild( form );
	const container = document.createElement( 'div' );
	form.appendChild( container );
	const root = createRoot( container );

	// eslint-disable-next-line testing-library/no-unnecessary-act
	act( () => root.render( <SettingsUIPage schema={ schema } /> ) );

	return { container, form, root };
};

describe( 'SettingsUIPage View Config', () => {
	afterEach( () => {
		jest.clearAllMocks();
		document.body.innerHTML = '';
	} );

	it( 'mounts the local form after a delayed request failure', () => {
		useViewConfigRuntime.mockReturnValue( { status: 'loading' } );
		const { container, root } = renderPage();

		useViewConfigRuntime.mockReturnValue( {
			status: 'failed',
			error: new Error( 'Forbidden' ),
		} );
		// eslint-disable-next-line testing-library/no-unnecessary-act
		act( () => root.render( <SettingsUIPage schema={ schema } /> ) );

		expect(
			container.querySelectorAll( 'input:not([type="hidden"])' )
		).toHaveLength( 2 );
		expect(
			container.querySelectorAll( '.woocommerce-save-button' )
		).toHaveLength( 1 );
		expect( container ).not.toHaveTextContent(
			'This page uses View Config.'
		);
		act( () => root.unmount() );
	} );

	it( 'uses the local form when the remote layout adds an unknown field', () => {
		useViewConfigRuntime.mockReturnValue( {
			status: 'resolved',
			config: {
				...validConfig,
				form: {
					fields: [ { id: 'contact', children: [ 'unknown' ] } ],
				},
			},
		} );
		const { container, root } = renderPage();

		expect(
			container.querySelectorAll( 'input:not([type="hidden"])' )
		).toHaveLength( 2 );
		expect( container ).not.toHaveTextContent(
			'This page uses View Config.'
		);
		act( () => root.unmount() );
	} );

	it( 'keeps omitted field values in the form-post bridge', () => {
		useViewConfigRuntime.mockReturnValue( {
			status: 'resolved',
			config: validConfig,
		} );
		const { container, form, root } = renderPage();

		expect(
			container.querySelectorAll( 'input:not([type="hidden"])' )
		).toHaveLength( 1 );
		expect( form.querySelector( 'input[name="phone"]' ) ).toHaveValue(
			'555-0100'
		);
		expect( container ).toHaveTextContent( 'This page uses View Config.' );

		act( () => root.unmount() );
	} );
} );
