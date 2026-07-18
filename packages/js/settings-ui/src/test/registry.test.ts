/**
 * Internal dependencies
 */
import {
	__resetRegistry,
	registerSettingsExtension,
	resolveFieldComponent,
	resolveFieldValidator,
	resolveFieldVisibilityPredicate,
	resolveGroupVisibilityPredicate,
	resolveSaveHandler,
} from '../registry';
import type {
	SettingsExtensionRegistration,
	SettingsFieldComponent,
	SettingsSaveHandler,
	SettingsVisibilityPredicate,
} from '../types';

describe( 'settings extension registry', () => {
	afterEach( () => {
		__resetRegistry();
	} );

	it( 'resolves named field components within the matching scope', () => {
		const component: SettingsFieldComponent = () => null;

		registerSettingsExtension( {
			scope: { page: 'registry-test', section: 'advanced' },
			components: {
				'test/component': component,
			},
		} );

		expect(
			resolveFieldComponent(
				{
					id: 'field',
					label: 'Field',
					type: 'text',
					component: 'test/component',
				},
				{ page: 'registry-test', section: 'advanced' }
			)
		).toBe( component );
	} );

	it( 'resolves validators registered with field components', () => {
		const component: SettingsFieldComponent = () => null;
		const validate = () => 'This value is invalid.';

		registerSettingsExtension( {
			scope: { page: 'registry-test' },
			components: {
				'test/component': { component, validate },
			},
		} );

		const field = {
			id: 'field',
			label: 'Field',
			type: 'text',
			component: 'test/component',
		};
		const context = { page: 'registry-test' };

		expect( resolveFieldComponent( field, context ) ).toBe( component );
		expect( resolveFieldValidator( field, context ) ).toBe( validate );
	} );

	it( 'resolves field components by documented precedence before registration recency', () => {
		const component: SettingsFieldComponent = () => null;
		const fieldOverride: SettingsFieldComponent = () => null;
		const typeRenderer: SettingsFieldComponent = () => null;

		registerSettingsExtension( {
			scope: { page: 'registry-precedence' },
			components: {
				'test/component': component,
			},
			fieldOverrides: {
				field: fieldOverride,
			},
		} );
		registerSettingsExtension( {
			scope: { page: 'registry-precedence' },
			typeRenderers: {
				text: typeRenderer,
			},
		} );

		expect(
			resolveFieldComponent(
				{
					id: 'field',
					label: 'Field',
					type: 'text',
					component: 'test/component',
				},
				{ page: 'registry-precedence' }
			)
		).toBe( component );
		expect(
			resolveFieldComponent(
				{
					id: 'field',
					label: 'Field',
					type: 'text',
				},
				{ page: 'registry-precedence' }
			)
		).toBe( fieldOverride );
	} );

	it( 'composes registrations in the same scope while newer keys take precedence', () => {
		const originalComponent: SettingsFieldComponent = () => null;
		const replacementComponent: SettingsFieldComponent = () => null;
		const saveHandler: SettingsSaveHandler = () => undefined;

		registerSettingsExtension( {
			scope: { page: 'registry-composition' },
			components: {
				'test/component': originalComponent,
			},
			saveHandlers: {
				'test/save': saveHandler,
			},
		} );
		registerSettingsExtension( {
			scope: { page: 'registry-composition' },
			components: {
				'test/component': replacementComponent,
			},
		} );

		expect(
			resolveFieldComponent(
				{
					id: 'field',
					label: 'Field',
					type: 'text',
					component: 'test/component',
				},
				{ page: 'registry-composition' }
			)
		).toBe( replacementComponent );
		expect(
			resolveSaveHandler( 'test/save', {
				page: 'registry-composition',
			} )
		).toBe( saveHandler );
	} );

	it( 'ignores malformed registration payloads', () => {
		const warnSpy = jest
			.spyOn( console, 'warn' )
			.mockImplementation( () => undefined );

		expect( () =>
			registerSettingsExtension( {
				scope: { page: 'registry-invalid' },
				components: [],
			} as unknown as SettingsExtensionRegistration )
		).not.toThrow();
		expect(
			resolveFieldComponent(
				{
					id: 'field',
					label: 'Field',
					type: 'text',
					component: '0',
				},
				{ page: 'registry-invalid' }
			)
		).toBeUndefined();
		expect( warnSpy ).toHaveBeenCalledWith(
			expect.stringContaining(
				'Invalid settings extension registration payload.'
			),
			expect.any( Object )
		);

		warnSpy.mockRestore();
	} );

	it( 'ignores registrations outside the current page scope', () => {
		const component: SettingsFieldComponent = () => null;

		registerSettingsExtension( {
			scope: { page: 'registry-test-other' },
			typeRenderers: {
				text: component,
			},
		} );

		expect(
			resolveFieldComponent(
				{
					id: 'field',
					label: 'Field',
					type: 'text',
				},
				{ page: 'registry-test-missing' }
			)
		).toBeUndefined();
	} );

	it( 'distinguishes page-wide, default-section, and named-section scopes', () => {
		const pageWideComponent: SettingsFieldComponent = () => null;
		const defaultSectionComponent: SettingsFieldComponent = () => null;
		const namedSectionComponent: SettingsFieldComponent = () => null;

		registerSettingsExtension( {
			scope: { page: 'registry-section-scope' },
			components: {
				'page-wide': pageWideComponent,
			},
		} );
		registerSettingsExtension( {
			scope: { page: 'registry-section-scope', section: '' },
			fieldOverrides: {
				default_field: defaultSectionComponent,
			},
		} );
		registerSettingsExtension( {
			scope: { page: 'registry-section-scope', section: 'advanced' },
			components: {
				'named-section': namedSectionComponent,
			},
		} );

		expect(
			resolveFieldComponent(
				{
					id: 'field',
					label: 'Field',
					type: 'text',
					component: 'page-wide',
				},
				{ page: 'registry-section-scope', section: 'advanced' }
			)
		).toBe( pageWideComponent );
		expect(
			resolveFieldComponent(
				{
					id: 'field',
					label: 'Field',
					type: 'text',
					component: 'page-wide',
				},
				{ page: 'registry-section-scope', section: '' }
			)
		).toBe( pageWideComponent );
		expect(
			resolveFieldComponent(
				{
					id: 'default_field',
					label: 'Field',
					type: 'text',
				},
				{ page: 'registry-section-scope', section: '' }
			)
		).toBe( defaultSectionComponent );
		expect(
			resolveFieldComponent(
				{
					id: 'default_field',
					label: 'Field',
					type: 'text',
				},
				{ page: 'registry-section-scope', section: 'advanced' }
			)
		).toBeUndefined();
		const warnSpy = jest
			.spyOn( console, 'warn' )
			.mockImplementation( jest.fn() );
		expect(
			resolveFieldComponent(
				{
					id: 'field',
					label: 'Field',
					type: 'text',
					component: 'named-section',
				},
				{ page: 'registry-section-scope', section: '' }
			)
		).toBeUndefined();
		warnSpy.mockRestore();
		expect(
			resolveFieldComponent(
				{
					id: 'field',
					label: 'Field',
					type: 'text',
					component: 'named-section',
				},
				{ page: 'registry-section-scope', section: 'advanced' }
			)
		).toBe( namedSectionComponent );
	} );

	it( 'resolves visibility predicates by field and group scope', () => {
		const fieldPredicate: SettingsVisibilityPredicate = () => true;
		const groupPredicate: SettingsVisibilityPredicate = () => false;

		registerSettingsExtension( {
			scope: { page: 'registry-visibility', section: 'payments' },
			fieldVisibility: {
				field: fieldPredicate,
			},
			groupVisibility: {
				group: groupPredicate,
			},
		} );

		expect(
			resolveFieldVisibilityPredicate( 'field', {
				page: 'registry-visibility',
				section: 'payments',
			} )
		).toBe( fieldPredicate );
		expect(
			resolveGroupVisibilityPredicate( 'group', {
				page: 'registry-visibility',
				section: 'payments',
			} )
		).toBe( groupPredicate );
		expect(
			resolveFieldVisibilityPredicate( 'field', {
				page: 'registry-visibility',
				section: 'other',
			} )
		).toBeUndefined();
	} );

	it( 'resolves save handlers by scope', () => {
		const saveHandler: SettingsSaveHandler = () => undefined;

		registerSettingsExtension( {
			scope: { page: 'registry-save' },
			saveHandlers: {
				'save/handler': saveHandler,
			},
		} );

		expect(
			resolveSaveHandler( 'save/handler', {
				page: 'registry-save',
			} )
		).toBe( saveHandler );
	} );
} );
