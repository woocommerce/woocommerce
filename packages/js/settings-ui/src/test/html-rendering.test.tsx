/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
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

/**
 * Internal dependencies
 */
import { SettingsUIErrorBoundary, SettingsUIPage } from '../settings-ui-page';
import { __resetRegistry, registerSettingsExtension } from '../registry';
import type { SettingsUIField, SettingsUISchema } from '../types';

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

const createSingleFieldSchema = (
	field: SettingsUIField,
	overrides: Partial< SettingsUISchema > = {}
): SettingsUISchema => ( {
	id: 'test-page',
	title: 'Test page',
	section: 'default',
	save: { adapter: 'none' },
	...overrides,
	groups: {
		general: {
			id: 'general',
			fields: [ field ],
		},
	},
} );

const renderElementInMainForm = ( element: JSX.Element ) => {
	const form = document.createElement( 'form' );
	form.id = 'mainform';
	document.body.appendChild( form );

	const container = document.createElement( 'div' );
	form.appendChild( container );
	const root = createRoot( container );

	act( () => {
		root.render( element );
	} );

	return { container, form, root };
};

const changeTextInput = ( input: HTMLInputElement, value: string ) => {
	const valueSetter = Object.getOwnPropertyDescriptor(
		HTMLInputElement.prototype,
		'value'
	)?.set;

	if ( ! valueSetter ) {
		throw new Error( 'Expected HTMLInputElement value setter.' );
	}

	valueSetter.call( input, value );
	input.dispatchEvent(
		new Event( 'input', { bubbles: true, cancelable: true } )
	);
};

const changeSelect = ( select: HTMLSelectElement, values: string[] ) => {
	Array.from( select.options ).forEach( ( option ) => {
		option.selected = values.includes( option.value );
	} );
	select.dispatchEvent(
		new Event( 'change', { bubbles: true, cancelable: true } )
	);
};

const getUnsavedChangesActionButton = ( label: string ): HTMLButtonElement => {
	const button = Array.from(
		document.body.querySelectorAll< HTMLButtonElement >(
			'.wc-settings-ui__unsaved-changes-actions button'
		)
	).find( ( candidate ) => candidate.textContent?.trim() === label );

	if ( ! ( button instanceof HTMLButtonElement ) ) {
		throw new Error(
			`Expected unsaved changes action button "${ label }".`
		);
	}

	return button;
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
	const originalUrl = window.location.href;

	afterEach( () => {
		__resetRegistry();
		jest.restoreAllMocks();
		window.history.replaceState( {}, '', originalUrl );
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

		expect( container.querySelector( '.wc-settings-ui' ) ).not.toBeNull();
		expect(
			container.querySelector( '.dataforms-layouts__wrapper' )
		).not.toBeNull();
		expect(
			container.querySelector( '.wc-settings-ui__section-card' )
		).toBeNull();
		expect( container.textContent ).toContain( 'General settings' );
		expect( container.textContent ).toContain( 'Test field' );
		expect( container.textContent ).toContain(
			'Shown as field description.'
		);

		act( () => root.unmount() );
		container.remove();
	} );

	it( 'normalizes the default schema section to default-section scope', () => {
		const DefaultSectionField = jest.fn( () => (
			<div>Default section field</div>
		) );
		registerSettingsExtension( {
			scope: { page: 'test-page', section: '' },
			fieldOverrides: {
				test_field: DefaultSectionField,
			},
		} );

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
						},
					],
				},
			},
		};

		const { container, root } = renderElement(
			<SettingsUIPage schema={ schema } />
		);

		expect( container.textContent ).toContain( 'Default section field' );
		expect( DefaultSectionField.mock.calls[ 0 ][ 0 ].field.id ).toBe(
			'test_field'
		);

		act( () => root.unmount() );
		container.remove();
	} );

	it( 'fails closed when an explicit component is not registered', () => {
		window.history.replaceState(
			{},
			'',
			'/wp-admin/admin.php?page=wc-settings&tab=products&section=advanced&preserved=yes#wc-settings'
		);
		jest.spyOn( console, 'warn' ).mockImplementation( () => undefined );
		jest.spyOn( console, 'error' ).mockImplementation( () => undefined );
		const schema = createSingleFieldSchema(
			{
				id: 'test_field',
				label: 'Test field',
				type: 'text',
				component: 'test/missing-component',
			},
			{
				id: 'products',
				title: 'Products',
				section: 'advanced',
				save: { adapter: 'form_post' },
			}
		);

		const { container, root } = renderElement(
			<SettingsUIErrorBoundary>
				<SettingsUIPage schema={ schema } />
			</SettingsUIErrorBoundary>
		);

		expect( container.textContent ).toContain(
			'Something went wrong while rendering this settings page.'
		);
		expect( container.querySelector( 'input' ) ).toBeNull();
		expect(
			container.querySelector( '.woocommerce-save-button' )
		).toBeNull();
		const classicAction = Array.from(
			container.querySelectorAll( 'a' )
		).find(
			( link ) => link.textContent?.trim() === 'Use classic settings'
		);
		expect( classicAction ).toBeDefined();
		expect(
			classicAction?.closest( '.components-notice__actions' )
		).not.toBeNull();
		expect(
			container.querySelector( '.components-notice__content' )?.firstChild
				?.textContent
		).toBe( 'Something went wrong while rendering this settings page.' );
		const classicUrl = new URL( classicAction?.href || '' );
		expect( classicUrl.searchParams.getAll( 'wc_settings_ui' ) ).toEqual( [
			'classic',
		] );
		expect( classicUrl.searchParams.get( 'page' ) ).toBe( 'wc-settings' );
		expect( classicUrl.searchParams.get( 'tab' ) ).toBe( 'products' );
		expect( classicUrl.searchParams.get( 'section' ) ).toBe( 'advanced' );
		expect( classicUrl.searchParams.get( 'preserved' ) ).toBe( 'yes' );
		expect( classicUrl.hash ).toBe( '#wc-settings' );

		act( () => root.unmount() );
		container.remove();
	} );

	it( 'uses a field override when an explicit component is not registered', () => {
		const FieldOverride = () => <div>Extension field override</div>;
		registerSettingsExtension( {
			scope: { page: 'test-page' },
			fieldOverrides: { test_field: FieldOverride },
		} );
		const schema = createSingleFieldSchema( {
			id: 'test_field',
			label: 'Test field',
			type: 'text',
			component: 'test/missing-component',
		} );

		const { container, root } = renderElement(
			<SettingsUIErrorBoundary>
				<SettingsUIPage schema={ schema } />
			</SettingsUIErrorBoundary>
		);

		expect( container.textContent ).toContain( 'Extension field override' );
		expect( container.textContent ).not.toContain(
			'Something went wrong while rendering this settings page.'
		);

		act( () => root.unmount() );
		container.remove();
	} );

	it( 'renders extension-defined types through registered type renderers', () => {
		const TypeRenderer = () => <div>Extension type renderer</div>;
		registerSettingsExtension( {
			scope: { page: 'test-page' },
			typeRenderers: { extension_defined: TypeRenderer },
		} );
		const schema = createSingleFieldSchema( {
			id: 'test_field',
			label: 'Test field',
			type: 'extension_defined',
		} );

		const { container, root } = renderElement(
			<SettingsUIErrorBoundary>
				<SettingsUIPage schema={ schema } />
			</SettingsUIErrorBoundary>
		);

		expect( container.textContent ).toContain( 'Extension type renderer' );
		expect( container.querySelector( 'input' ) ).toBeNull();

		act( () => root.unmount() );
		container.remove();
	} );

	it( 'fails closed and focuses the error region for an unrenderable type', () => {
		jest.spyOn( console, 'error' ).mockImplementation( () => undefined );
		const schema = createSingleFieldSchema( {
			id: 'test_field',
			label: 'Test field',
			type: 'extension_defined',
		} );

		const { container, root } = renderElement(
			<SettingsUIErrorBoundary>
				<SettingsUIPage schema={ schema } />
			</SettingsUIErrorBoundary>
		);

		const errorRegion = container.querySelector( '.wc-settings-ui__error' );
		expect( errorRegion ).toHaveAttribute( 'role', 'region' );
		expect( errorRegion ).toHaveAttribute( 'tabindex', '-1' );
		expect( errorRegion?.ownerDocument.activeElement ).toBe( errorRegion );
		expect( container.querySelector( 'input' ) ).toBeNull();
		expect(
			container.querySelector( '.woocommerce-save-button' )
		).toBeNull();

		act( () => root.unmount() );
		container.remove();
	} );

	it( 'sanitizes field descriptions before rendering', () => {
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

	it( 'hides fields with unmet schema visibility rules', () => {
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

		const input = container.querySelector( 'input:not([type="hidden"])' );
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

	it( 'prompts before navigating away through the classic section links', () => {
		const schema: SettingsUISchema = {
			id: 'test-page',
			title: 'Test page',
			section: 'default',
			save: { adapter: 'form_post' },
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

		const { container, form, root } = renderElementInMainForm(
			<SettingsUIPage schema={ schema } />
		);

		// Classic section links render inside #mainform but outside the shell.
		const sectionLinks = document.createElement( 'ul' );
		sectionLinks.className = 'subsubsub';
		sectionLinks.innerHTML =
			'<li><a href="https://example.com/inventory">Inventory</a></li>';
		form.insertBefore( sectionLinks, container );

		try {
			const input = container.querySelector(
				'input:not([type="hidden"])'
			);
			const link = sectionLinks.querySelector( 'a' );

			expect( input ).toBeInstanceOf( HTMLInputElement );
			expect( link ).not.toBeNull();

			act( () => {
				changeTextInput( input as HTMLInputElement, 'Changed value' );
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
		} finally {
			act( () => root.unmount() );
			form.remove();
		}
	} );

	it( 'submits form-post saves with the pending destination', () => {
		const requestSubmit = jest
			.spyOn( HTMLFormElement.prototype, 'requestSubmit' )
			.mockImplementation( () => undefined );

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

		const { container, form, root } = renderElementInMainForm(
			<SettingsUIPage schema={ schema } />
		);

		try {
			const input = container.querySelector(
				'input:not([type="hidden"])'
			);
			const link = container.querySelector(
				'a[href="https://example.com/next"]'
			);

			expect( input ).toBeInstanceOf( HTMLInputElement );
			expect( link ).not.toBeNull();

			act( () => {
				changeTextInput( input as HTMLInputElement, 'Changed value' );
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

			const saveButton = getUnsavedChangesActionButton( 'Save' );

			act( () => {
				saveButton.dispatchEvent(
					new MouseEvent( 'click', {
						bubbles: true,
						cancelable: true,
						button: 0,
					} )
				);
			} );

			const redirectInput = form.querySelector(
				'input[name="wc_settings_ui_redirect_to"]'
			);

			expect( redirectInput ).toBeInstanceOf( HTMLInputElement );
			expect( redirectInput ).toHaveAttribute(
				'value',
				'https://example.com/next'
			);
			expect( requestSubmit ).toHaveBeenCalledWith(
				container.querySelector( '.woocommerce-save-button' )
			);
		} finally {
			act( () => root.unmount() );
			form.remove();
			requestSubmit.mockRestore();
		}
	} );

	it( 'keeps unload protection when custom save before navigation fails', async () => {
		const saveHandler = jest
			.fn()
			.mockRejectedValue( new Error( 'Save failed.' ) );

		registerSettingsExtension( {
			scope: { page: 'test-page', section: '' },
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

		const input = container.querySelector( 'input:not([type="hidden"])' );
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

		const saveButton = getUnsavedChangesActionButton( 'Save' );

		await act( async () => {
			saveButton.dispatchEvent(
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

	it( 'prevents dismissing the navigation modal while a custom save is pending', async () => {
		let rejectSave: ( error: Error ) => void = () => undefined;
		const saveHandler = jest.fn(
			() =>
				new Promise< never >( ( _resolve, reject ) => {
					rejectSave = reject;
				} )
		);

		registerSettingsExtension( {
			scope: { page: 'test-page', section: '' },
			saveHandlers: {
				pending: saveHandler,
			},
		} );

		const schema: SettingsUISchema = {
			id: 'test-page',
			title: 'Test page',
			section: 'default',
			save: { adapter: 'custom', handler: 'pending' },
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

		const input = container.querySelector( 'input:not([type="hidden"])' );
		const link = container.querySelector(
			'a[href="https://example.com/next"]'
		);

		act( () => {
			changeTextInput( input as HTMLInputElement, 'Changed value' );
			link?.dispatchEvent(
				new MouseEvent( 'click', {
					bubbles: true,
					cancelable: true,
					button: 0,
				} )
			);
		} );

		await act( async () => {
			getUnsavedChangesActionButton( 'Save' ).click();
			await Promise.resolve();
		} );

		const discardButton = getUnsavedChangesActionButton( 'Discard' );
		const saveButton = getUnsavedChangesActionButton( 'Save' );
		const modal = document.body.querySelector(
			'.wc-settings-ui__unsaved-changes-modal'
		);

		expect( saveHandler ).toHaveBeenCalledTimes( 1 );
		expect( discardButton ).toBeDisabled();
		expect( saveButton ).toHaveAttribute( 'aria-disabled', 'true' );
		expect( saveButton ).not.toBeDisabled();
		expect(
			document.body.querySelector( 'button[aria-label="Close"]' )
		).toBeNull();

		act( () => {
			discardButton.click();
			modal?.dispatchEvent(
				new KeyboardEvent( 'keydown', {
					bubbles: true,
					cancelable: true,
					key: 'Escape',
				} )
			);
		} );

		expect( document.body.textContent ).toContain(
			'You have unsaved changes'
		);

		await act( async () => {
			rejectSave( new Error( 'Expected save failure.' ) );
			await Promise.resolve();
		} );

		act( () => root.unmount() );
		container.remove();
	} );

	it( 'routes registered control edits into the page values', () => {
		registerSettingsExtension( {
			scope: { page: 'test-page' },
			components: {
				'test/custom-field': ( { data, field, onChange } ) => (
					<button
						onClick={ () =>
							onChange( { [ field.id ]: 'clicked' } )
						}
					>
						{ `Custom control: ${ String(
							data[ field.id ] ?? ''
						) }` }
					</button>
				),
			},
		} );

		const schema: SettingsUISchema = {
			id: 'test-page',
			title: 'Test page',
			section: 'default',
			save: { adapter: 'form_post' },
			groups: {
				general: {
					id: 'general',
					fields: [
						{
							id: 'test_field',
							label: 'Test field',
							type: 'text',
							value: 'initial',
							component: 'test/custom-field',
						},
					],
				},
			},
		};

		const { container, form, root } = renderElementInMainForm(
			<SettingsUIPage schema={ schema } />
		);

		try {
			expect( container.textContent ).toContain(
				'Custom control: initial'
			);

			act( () => {
				container.querySelector( 'button' )?.click();
			} );

			expect( container.textContent ).toContain(
				'Custom control: clicked'
			);
			expect(
				form.querySelector( 'input[name="test_field"]' )
			).toHaveAttribute( 'value', 'clicked' );
		} finally {
			act( () => root.unmount() );
			form.remove();
		}
	} );

	it( 'serializes edits from built-in controls into the form-post hidden inputs', () => {
		const schema: SettingsUISchema = {
			id: 'test-page',
			title: 'Test page',
			section: 'default',
			save: { adapter: 'form_post' },
			groups: {
				general: {
					id: 'general',
					fields: [
						{
							id: 'flag',
							label: 'Flag',
							type: 'checkbox',
							value: false,
						},
						{
							id: 'unit',
							label: 'Unit',
							type: 'select',
							value: 'kg',
							options: [
								{ label: 'kg', value: 'kg' },
								{ label: 'lbs', value: 'lbs' },
							],
						},
						{
							id: 'amount',
							label: 'Amount',
							type: 'number',
							value: '1',
						},
						{
							id: 'countries',
							label: 'Countries',
							type: 'array',
							value: [ 'FR' ],
							options: [
								{ label: 'France', value: 'FR' },
								{ label: 'Spain', value: 'ES' },
							],
						},
					],
				},
			},
		};

		const { container, form, root } = renderElementInMainForm(
			<SettingsUIPage schema={ schema } />
		);
		const hiddenValues = ( name: string ) =>
			Array.from(
				form.querySelectorAll< HTMLInputElement >(
					`input[type="hidden"][name="${ name }"]`
				)
			).map( ( input ) => input.value );

		try {
			expect( hiddenValues( 'flag' ) ).toEqual( [ 'no' ] );
			expect( hiddenValues( 'unit' ) ).toEqual( [ 'kg' ] );
			expect( hiddenValues( 'amount' ) ).toEqual( [ '1' ] );
			expect( hiddenValues( 'countries[]' ) ).toEqual( [ 'FR' ] );

			const checkbox = container.querySelector< HTMLInputElement >(
				'input[type="checkbox"]'
			);
			const selects =
				container.querySelectorAll< HTMLSelectElement >( 'select' );
			const number = container.querySelector< HTMLInputElement >(
				'input[type="number"]'
			);
			if ( ! checkbox || selects.length !== 2 || ! number ) {
				throw new Error( 'Expected one control per built-in type.' );
			}

			act( () => checkbox.click() );
			act( () => changeSelect( selects[ 0 ], [ 'lbs' ] ) );
			act( () => changeTextInput( number, '5' ) );
			act( () => changeSelect( selects[ 1 ], [ 'FR', 'ES' ] ) );

			expect( hiddenValues( 'flag' ) ).toEqual( [ 'yes' ] );
			expect( hiddenValues( 'unit' ) ).toEqual( [ 'lbs' ] );
			expect( hiddenValues( 'amount' ) ).toEqual( [ '5' ] );
			expect( hiddenValues( 'countries[]' ) ).toEqual( [ 'FR', 'ES' ] );
		} finally {
			act( () => root.unmount() );
			form.remove();
		}
	} );

	it( 'fails closed when a declared component is not registered', () => {
		jest.spyOn( console, 'warn' ).mockImplementation( () => undefined );
		jest.spyOn( console, 'error' ).mockImplementation( () => undefined );

		const schema: SettingsUISchema = {
			id: 'test-page',
			title: 'Test page',
			section: 'default',
			save: { adapter: 'form_post' },
			groups: {
				general: {
					id: 'general',
					fields: [
						{
							id: 'test_field',
							label: 'Test field',
							type: 'text',
							component: 'test/missing-component',
						},
					],
				},
			},
		};

		const { container, root } = renderElement(
			<SettingsUIErrorBoundary>
				<SettingsUIPage schema={ schema } />
			</SettingsUIErrorBoundary>
		);

		expect( container.textContent ).toContain(
			'Something went wrong while rendering this settings page.'
		);
		expect( container.querySelector( 'input' ) ).toBeNull();
		expect(
			container.querySelector( '.woocommerce-save-button' )
		).toBeNull();

		act( () => root.unmount() );
		container.remove();
	} );

	it( 'fails closed when no renderer resolves for a field type', () => {
		jest.spyOn( console, 'warn' ).mockImplementation( () => undefined );
		jest.spyOn( console, 'error' ).mockImplementation( () => undefined );

		const schema: SettingsUISchema = {
			id: 'test-page',
			title: 'Test page',
			section: 'default',
			save: { adapter: 'form_post' },
			groups: {
				general: {
					id: 'general',
					fields: [
						{
							id: 'test_field',
							label: 'Test field',
							type: 'extension_defined',
							options: [ { label: 'One', value: 'one' } ],
						},
					],
				},
			},
		};

		const { container, root } = renderElement(
			<SettingsUIErrorBoundary>
				<SettingsUIPage schema={ schema } />
			</SettingsUIErrorBoundary>
		);

		// A type nothing can draw has to be as loud as a missing component,
		// rather than dropping the field beside a live Save button.
		expect( container.textContent ).toContain(
			'Something went wrong while rendering this settings page.'
		);
		expect( container.querySelector( 'select' ) ).toBeNull();
		expect(
			container.querySelector( '.woocommerce-save-button' )
		).toBeNull();

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

		// DataForm paints the label for a read-only field, so the info
		// renderer must not repeat it.
		expect(
			( container.textContent ?? '' ).split( 'Info field' )
		).toHaveLength( 2 );

		// The info description keeps sanitized markup while the group
		// description and the DataForm field label render as plain text.
		const strongTexts = Array.from(
			container.querySelectorAll( 'strong' )
		).map( ( el ) => el.textContent );
		expect( strongTexts ).toEqual( [ 'Safe' ] );
		expect( container.querySelector( 'script' ) ).toBeNull();
		expect( container.querySelector( 'img' ) ).toBeNull();
		expect( container.querySelector( 'iframe' ) ).toBeNull();
		expect( container.innerHTML ).not.toContain( 'onerror' );
		expect( container.innerHTML ).not.toContain( 'onclick' );
		expect( container.innerHTML ).not.toContain( 'javascript:' );

		act( () => root.unmount() );
		container.remove();
	} );
} );
