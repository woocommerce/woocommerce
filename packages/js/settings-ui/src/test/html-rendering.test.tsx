/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { act } from 'react';
import { createRoot } from 'react-dom/client';
import type { ReactNode } from 'react';

jest.mock( '@wordpress/admin-ui', () => ( {
	Page: ( {
		actions,
		children,
		className,
	}: {
		actions?: ReactNode;
		children: ReactNode;
		className?: string;
	} ) => (
		<div className={ className }>
			{ actions }
			{ children }
		</div>
	),
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
		expect( DefaultSectionField.mock.calls[ 0 ][ 0 ].context.section ).toBe(
			''
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

	it( 'keeps checkbox values in the schema vocabulary for visibility predicates and dirty tracking', () => {
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
							id: 'env',
							label: 'Enable environment',
							type: 'checkbox',
							value: 'no',
						},
						{
							id: 'dependent_field',
							label: 'Dependent field',
							type: 'text',
							value: 'abc',
						},
					],
				},
			},
		};

		registerSettingsExtension( {
			scope: { page: 'test-page' },
			fieldVisibility: {
				dependent_field: ( { values } ) => values.env === 'yes',
			},
		} );

		const { container, root } = renderElement(
			<SettingsUIPage schema={ schema } page="test-page" />
		);

		const getSaveButton = () => {
			const saveButton = container.querySelector< HTMLButtonElement >(
				'.woocommerce-save-button'
			);

			if ( ! saveButton ) {
				throw new Error( 'Expected a save button.' );
			}

			return saveButton;
		};

		const clickCheckbox = () => {
			const checkbox = container.querySelector< HTMLInputElement >(
				'input[type="checkbox"]'
			);

			act( () => {
				checkbox?.click();
			} );
		};

		// Initially: predicate unmet, nothing dirty.
		expect( container.textContent ).not.toContain( 'Dependent field' );
		expect( getSaveButton().disabled ).toBe( true );

		// Toggle on: the predicate sees 'yes' (not a raw boolean) and the
		// change is a real edit, so Save enables.
		clickCheckbox();
		expect( container.textContent ).toContain( 'Dependent field' );
		expect( getSaveButton().disabled ).toBe( false );

		// Toggle back off: the value returns to the initial 'no', so the
		// form is clean again and Save disables.
		clickCheckbox();
		expect( container.textContent ).not.toContain( 'Dependent field' );
		expect( getSaveButton().disabled ).toBe( true );

		act( () => root.unmount() );
		container.remove();
	} );

	it( 'restores non-string schema values when an edit reverts to the initial value', () => {
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
							id: 'threshold',
							label: 'Threshold',
							type: 'number',
							value: 5,
							customAttributes: { step: 1 },
						},
					],
				},
			},
		};

		const { container, root } = renderElement(
			<SettingsUIPage schema={ schema } page="test-page" />
		);

		const getSaveButton = () => {
			const saveButton = container.querySelector< HTMLButtonElement >(
				'.woocommerce-save-button'
			);

			if ( ! saveButton ) {
				throw new Error( 'Expected a save button.' );
			}

			return saveButton;
		};

		const clickSpinButton = ( ariaLabel: string ) => {
			const button = container.querySelector< HTMLButtonElement >(
				`button[aria-label="${ ariaLabel }"]`
			);

			act( () => {
				button?.dispatchEvent(
					new MouseEvent( 'click', { bubbles: true } )
				);
			} );
		};

		expect( getSaveButton().disabled ).toBe( true );

		// The spin control emits strings; the schema supplied the number 5.
		clickSpinButton( 'Increment Threshold' );
		expect( getSaveButton().disabled ).toBe( false );

		// Stepping back to "5" restores the schema's numeric 5, so the form
		// is clean again instead of dirty on '5' !== 5.
		clickSpinButton( 'Decrement Threshold' );
		expect( getSaveButton().disabled ).toBe( true );

		act( () => root.unmount() );
		container.remove();
	} );

	it( 'preserves the initial representation for custom component setValue writes', () => {
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
							id: 'tags_field',
							label: 'Tags',
							type: 'array',
							value: '',
						},
					],
				},
			},
		};

		registerSettingsExtension( {
			scope: { page: 'test-page' },
			fieldOverrides: {
				tags_field: ( { setValue } ) => (
					<div>
						<button
							type="button"
							onClick={ () => setValue( 'tags_field', [] ) }
						>
							Clear tags
						</button>
						<button
							type="button"
							onClick={ () =>
								setValue( 'tags_field', [ 'featured' ] )
							}
						>
							Add tag
						</button>
					</div>
				),
			},
		} );

		const { container, root } = renderElement(
			<SettingsUIPage schema={ schema } page="test-page" />
		);

		const getSaveButton = () => {
			const saveButton = container.querySelector< HTMLButtonElement >(
				'.woocommerce-save-button'
			);

			if ( ! saveButton ) {
				throw new Error( 'Expected a save button.' );
			}

			return saveButton;
		};

		const clickButton = ( text: string ) => {
			const button = [
				...container.querySelectorAll< HTMLButtonElement >( 'button' ),
			].find( ( el ) => el.textContent === text );

			act( () => {
				button?.dispatchEvent(
					new MouseEvent( 'click', { bubbles: true } )
				);
			} );
		};

		// An empty array is a re-encoding of the initial '' value, so a
		// custom component writing [] through setValue keeps the form clean.
		clickButton( 'Clear tags' );
		expect( getSaveButton().disabled ).toBe( true );

		// A real change still dirties the form.
		clickButton( 'Add tag' );
		expect( getSaveButton().disabled ).toBe( false );

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
			const input = container.querySelector( 'input[type="text"]' );
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
