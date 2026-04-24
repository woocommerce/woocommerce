/**
 * Internal dependencies
 */
import { registerSettingsExtension, resolveFieldComponent } from '../registry';
import type { SettingsFieldComponent } from '../types';

describe( 'settings extension registry', () => {
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
} );
