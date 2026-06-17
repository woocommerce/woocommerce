/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { act } from 'react';
import { createRoot } from 'react-dom/client';
import type { ReactNode } from 'react';

jest.mock( '@wordpress/admin-ui', () => ( {
	Page: ( {
		children,
		className,
	}: {
		children: ReactNode;
		className?: string;
	} ) => <div className={ className }>{ children }</div>,
} ) );

/**
 * Internal dependencies
 */
import { SettingsUIPage } from '../settings-ui-page';
import { __resetRegistry, registerSettingsExtension } from '../registry';
import type { SettingsUISchema } from '../types';

globalThis.IS_REACT_ACT_ENVIRONMENT = true;

const unsafeDescription =
	'<strong>Safe</strong><script>alert("x")</script><img src=x onerror=alert(1)><a href="javascript:alert(1)" onclick="alert(1)">Link</a><iframe src="https://example.com"></iframe>';

const renderElement = ( element: JSX.Element ) => {
	const container = document.createElement( 'div' );
	document.body.appendChild( container );
	const root = createRoot( container );

	act( () => {
		root.render( element );
	} );

	return { container, root };
};

const expectUnsafeMarkupRemoved = ( container: HTMLElement ) => {
	expect( container.querySelector( 'strong' )?.textContent ).toBe( 'Safe' );
	expect( container.querySelector( 'script' ) ).toBeNull();
	expect( container.querySelector( 'img' ) ).toBeNull();
	expect( container.querySelector( 'iframe' ) ).toBeNull();
	expect( container.innerHTML ).not.toContain( 'onerror' );
	expect( container.innerHTML ).not.toContain( 'onclick' );
	expect( container.innerHTML ).not.toContain( 'javascript:' );
};

describe( 'settings HTML rendering', () => {
	afterEach( () => {
		__resetRegistry();
	} );

	it( 'renders settings as centered sections and cards', () => {
		const schema: SettingsUISchema = {
			id: 'test-page',
			title: 'Test page',
			section: 'default',
			save: { adapter: 'none' },
			groups: {
				general: {
					id: 'general',
					title: 'General settings',
					description: 'Configure the basics.',
					fields: [
						{
							id: 'test_field',
							label: 'Test field',
							type: 'text',
							description: 'Shown as field description.',
						},
					],
				},
			},
		};

		const { container, root } = renderElement(
			<SettingsUIPage schema={ schema } />
		);

		expect(
			container.querySelector( '.wc-settings-ui__section' )
		).not.toBeNull();
		expect(
			container.querySelector( '.wc-settings-ui__section-card' )
		).not.toBeNull();
		expect(
			container.querySelector( '.wc-settings-ui__section-fields' )
		).not.toBeNull();
		expect( container.querySelector( '.wc-settings-ui__row' ) ).toBeNull();
		expect(
			container.querySelector( '.wc-settings-ui__group-panel' )
		).toBeNull();
		expect(
			container.querySelector( '.wc-settings-ui__group-header' )
		).toBeNull();
		expect( container.textContent ).toContain( 'General settings' );
		expect( container.textContent ).toContain( 'Test field' );
		expect( container.textContent ).toContain(
			'Shown as field description.'
		);

		act( () => root.unmount() );
		container.remove();
	} );

	it( 'sanitizes native field descriptions before rendering', () => {
		const schema: SettingsUISchema = {
			id: 'test-page',
			title: 'Test page',
			section: 'default',
			save: { adapter: 'none' },
			groups: {
				general: {
					id: 'general',
					fields: [
						{
							id: 'test_field',
							label: 'Test field',
							type: 'text',
							description: unsafeDescription,
						},
					],
				},
			},
		};

		const { container, root } = renderElement(
			<SettingsUIPage schema={ schema } />
		);

		expectUnsafeMarkupRemoved( container );

		act( () => root.unmount() );
		container.remove();
	} );

	it( 'hides fields with unmet native visibility rules', () => {
		const schema: SettingsUISchema = {
			id: 'test-page',
			title: 'Test page',
			section: 'default',
			save: { adapter: 'none' },
			groups: {
				general: {
					id: 'general',
					fields: [
						{
							id: 'controller',
							label: 'Controller',
							type: 'checkbox',
							value: false,
						},
						{
							id: 'dependent',
							label: 'Dependent field',
							type: 'text',
							visibility: {
								controller: 'controller',
								value: true,
							},
						},
					],
				},
			},
		};

		const { container, root } = renderElement(
			<SettingsUIPage schema={ schema } />
		);

		expect( container.textContent ).toContain( 'Controller' );
		expect( container.textContent ).not.toContain( 'Dependent field' );

		act( () => root.unmount() );
		container.remove();
	} );

	it( 'prompts before navigating away with unsaved changes', () => {
		const schema: SettingsUISchema = {
			id: 'test-page',
			title: 'Test page',
			section: 'default',
			save: { adapter: 'form_post' },
			shell: {
				navigation: [
					{
						id: 'next-page',
						label: 'Next page',
						href: 'https://example.com/next',
					},
				],
			},
			groups: {
				general: {
					id: 'general',
					fields: [
						{
							id: 'test_field',
							label: 'Test field',
							type: 'text',
							value: 'Initial value',
						},
					],
				},
			},
		};

		const { container, root } = renderElement(
			<SettingsUIPage schema={ schema } />
		);

		const input = container.querySelector( 'input[type="text"]' );
		const link = container.querySelector(
			'a[href="https://example.com/next"]'
		);

		expect( input ).not.toBeNull();
		expect( link ).not.toBeNull();

		act( () => {
			if ( input instanceof HTMLInputElement ) {
				const valueSetter = Object.getOwnPropertyDescriptor(
					HTMLInputElement.prototype,
					'value'
				)?.set;

				valueSetter?.call( input, 'Changed value' );
				input.dispatchEvent(
					new Event( 'input', { bubbles: true, cancelable: true } )
				);
			}
		} );

		act( () => {
			link?.dispatchEvent(
				new MouseEvent( 'click', {
					bubbles: true,
					cancelable: true,
					button: 0,
				} )
			);
		} );

		expect( document.body.textContent ).toContain(
			'You have unsaved changes'
		);
		expect( document.body.textContent ).toContain(
			"If you leave now, your changes won't be saved."
		);
		expect( document.body.textContent ).toContain( 'Discard' );
		expect( document.body.textContent ).toContain( 'Save' );

		act( () => root.unmount() );
		container.remove();
	} );

	it( 'keeps unload protection when custom save before navigation fails', async () => {
		const saveHandler = jest
			.fn()
			.mockRejectedValue( new Error( 'Save failed.' ) );

		registerSettingsExtension( {
			scope: { page: 'test-page', section: 'default' },
			saveHandlers: {
				fail: saveHandler,
			},
		} );

		const schema: SettingsUISchema = {
			id: 'test-page',
			title: 'Test page',
			section: 'default',
			save: { adapter: 'custom', handler: 'fail' },
			shell: {
				navigation: [
					{
						id: 'next-page',
						label: 'Next page',
						href: 'https://example.com/next',
					},
				],
			},
			groups: {
				general: {
					id: 'general',
					fields: [
						{
							id: 'test_field',
							label: 'Test field',
							type: 'text',
							value: 'Initial value',
						},
					],
				},
			},
		};

		const { container, root } = renderElement(
			<SettingsUIPage schema={ schema } />
		);

		const input = container.querySelector( 'input[type="text"]' );
		const link = container.querySelector(
			'a[href="https://example.com/next"]'
		);

		act( () => {
			if ( input instanceof HTMLInputElement ) {
				const valueSetter = Object.getOwnPropertyDescriptor(
					HTMLInputElement.prototype,
					'value'
				)?.set;

				valueSetter?.call( input, 'Changed value' );
				input.dispatchEvent(
					new Event( 'input', { bubbles: true, cancelable: true } )
				);
			}
		} );

		act( () => {
			link?.dispatchEvent(
				new MouseEvent( 'click', {
					bubbles: true,
					cancelable: true,
					button: 0,
				} )
			);
		} );

		const saveButton = Array.from(
			document.body.querySelectorAll(
				'.wc-settings-ui__unsaved-changes-actions button'
			)
		).find( ( button ) => button.textContent === 'Save' );

		await act( async () => {
			saveButton?.dispatchEvent(
				new MouseEvent( 'click', {
					bubbles: true,
					cancelable: true,
					button: 0,
				} )
			);
		} );

		const beforeUnloadEvent = new Event( 'beforeunload', {
			cancelable: true,
		} );

		window.dispatchEvent( beforeUnloadEvent );

		expect( saveHandler ).toHaveBeenCalledTimes( 1 );
		expect( beforeUnloadEvent.defaultPrevented ).toBe( true );
		expect( document.body.textContent ).toContain( 'Save failed.' );

		act( () => root.unmount() );
		container.remove();
	} );

	it( 'sanitizes info fields and group descriptions before rendering', () => {
		const schema: SettingsUISchema = {
			id: 'test-page',
			title: 'Test page',
			section: 'default',
			save: { adapter: 'none' },
			groups: {
				general: {
					id: 'general',
					title: 'General',
					description: unsafeDescription,
					fields: [
						{
							id: 'info_field',
							label: 'Info field',
							type: 'info',
							description: unsafeDescription,
						},
					],
				},
			},
		};

		const { container, root } = renderElement(
			<SettingsUIPage schema={ schema } />
		);

		expect( container.textContent ).toContain( 'Info field' );
		expectUnsafeMarkupRemoved( container );

		act( () => root.unmount() );
		container.remove();
	} );
} );
