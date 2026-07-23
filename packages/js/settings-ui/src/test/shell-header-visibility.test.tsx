/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { act } from 'react';
import { createRoot } from 'react-dom/client';
import type { ReactNode } from 'react';

// Mirror the real admin-ui NavigableRegion, which wraps the shell in a labeled region.
jest.mock( '@wordpress/admin-ui', () => ( {
	NavigableRegion: ( {
		children,
		className,
		ariaLabel,
	}: {
		children: ReactNode;
		className?: string;
		ariaLabel?: string;
	} ) => (
		<div className={ className } role="region" aria-label={ ariaLabel }>
			{ children }
		</div>
	),
} ) );

/**
 * Internal dependencies
 */
import { SettingsUIPage } from '../settings-ui-page';
import { __resetRegistry } from '../registry';
import type { SettingsUISchema } from '../types';

globalThis.IS_REACT_ACT_ENVIRONMENT = true;

const renderElement = ( element: JSX.Element ) => {
	const container = document.createElement( 'div' );
	document.body.appendChild( container );
	const root = createRoot( container );

	act( () => {
		root.render( element );
	} );

	return { container, root };
};

const baseSchema = (
	shell: SettingsUISchema[ 'shell' ],
	saveAdapter: 'form_post' | 'none' = 'form_post'
): SettingsUISchema => ( {
	id: 'test_page',
	title: 'Test page',
	save: { adapter: saveAdapter },
	shell,
	groups: {
		main: {
			id: 'main',
			title: 'Main',
			fields: [
				{
					id: 'field_a',
					label: 'Field A',
					type: 'text',
					value: '',
					save: { adapter: saveAdapter },
				},
			],
		},
	},
} );

describe( 'settings UI shell header visibility', () => {
	afterEach( () => {
		__resetRegistry();
		document.body.innerHTML = '';
	} );

	it( 'labels the shell region with a fallback when the schema has no title', () => {
		const schema = baseSchema( {} );
		delete schema.title;

		const { container, root } = renderElement(
			<SettingsUIPage schema={ schema } page="test_page" />
		);

		expect(
			container
				.querySelector( '.wc-settings-ui-shell' )
				?.getAttribute( 'aria-label' )
		).toBe( 'Settings' );

		act( () => {
			root.unmount();
		} );
	} );

	it( 'hides the header and saves from the page footer by default', () => {
		const { container, root } = renderElement(
			<SettingsUIPage
				schema={ baseSchema( { title: 'Test page' } ) }
				page="test_page"
			/>
		);

		expect(
			container.querySelector( '.wc-settings-ui-shell__header' )
		).toBeNull();
		expect( container.textContent ).not.toContain( 'Test page' );
		expect(
			container
				.querySelector( '.wc-settings-ui-shell' )
				?.getAttribute( 'aria-label' )
		).toBe( 'Test page' );

		const footerSaveButton = container.querySelector(
			'.wc-settings-ui .wc-settings-ui__footer-actions .woocommerce-save-button'
		);
		expect( footerSaveButton ).not.toBeNull();

		act( () => root.unmount() );
		container.remove();
	} );

	it( 'shows the header with the top save button when the shell opts in', () => {
		const { container, root } = renderElement(
			<SettingsUIPage
				schema={ baseSchema( {
					header: 'visible',
					title: 'Test page',
				} ) }
				page="test_page"
			/>
		);

		const header = container.querySelector(
			'.wc-settings-ui-shell__header'
		);
		expect( header ).not.toBeNull();
		expect( header?.textContent ).toContain( 'Test page' );
		expect(
			header?.querySelector( '.woocommerce-save-button' )
		).not.toBeNull();
		expect(
			container.querySelector( '.wc-settings-ui__footer-actions' )
		).toBeNull();

		act( () => root.unmount() );
		container.remove();
	} );

	it( 'renders no save button anywhere when the save adapter is none', () => {
		const { container, root } = renderElement(
			<SettingsUIPage
				schema={ baseSchema( { title: 'Test page' }, 'none' ) }
				page="test_page"
			/>
		);

		expect(
			container.querySelector( '.woocommerce-save-button' )
		).toBeNull();
		expect(
			container.querySelector( '.wc-settings-ui__footer-actions' )
		).toBeNull();

		act( () => root.unmount() );
		container.remove();
	} );
} );
