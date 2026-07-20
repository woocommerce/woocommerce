/**
 * Internal dependencies
 */
import type { Form } from '@wordpress/dataviews';
import {
	evaluateWooValidity,
	filterDataFormValidity,
	isFormValidityValid,
	mergeFormValidity,
} from '../settings-ui-page';
import { __resetRegistry, registerSettingsExtension } from '../registry';
import type { SettingsUIField } from '../types';

const context = { page: 'test-page', section: '' };
const form: Form = {
	layout: { type: 'regular' },
	fields: [
		{ id: 'first-group', children: [ 'dependent' ] },
		{ id: 'second-group', children: [ 'controller' ] },
	],
};

const makeField = (
	overrides: Partial< SettingsUIField > = {}
): SettingsUIField => ( {
	id: 'dependent',
	label: 'Dependent',
	type: 'number',
	...overrides,
} );

describe( 'Settings UI validity bridges', () => {
	afterEach( () => {
		__resetRegistry();
	} );

	it( 'reruns cross-subtree validators whenever any value changes', () => {
		const validate = jest.fn( ( { values } ) =>
			values.controller === 'blocked' ? 'Blocked.' : null
		);
		registerSettingsExtension( {
			scope: context,
			fieldOverrides: {
				dependent: { component: () => null, validate },
			},
		} );
		const fields = [ makeField() ];

		expect(
			evaluateWooValidity(
				fields,
				{ dependent: 1, controller: 'allowed' },
				context,
				form
			)
		).toBeUndefined();
		expect(
			evaluateWooValidity(
				fields,
				{ dependent: 1, controller: 'blocked' },
				context,
				form
			)
		).toEqual( {
			'first-group': {
				children: {
					dependent: {
						custom: { type: 'invalid', message: 'Blocked.' },
					},
				},
			},
		} );
		expect( validate ).toHaveBeenCalledTimes( 2 );
	} );

	it( 'gives range validation precedence over a registered validator', () => {
		registerSettingsExtension( {
			scope: context,
			fieldOverrides: {
				dependent: {
					component: () => null,
					validate: () => 'Extension error.',
				},
			},
		} );
		const validity = evaluateWooValidity(
			[ makeField( { validation: { min: 2, max: 5 } } ) ],
			{ dependent: 1 },
			context,
			form
		);

		expect(
			validity?.[ 'first-group' ]?.children?.dependent.custom?.message
		).toBe( 'Value must be at least 2.' );
	} );

	it( 'keeps package type errors ahead of Woo validation', () => {
		const merged = mergeFormValidity(
			{
				'first-group': {
					children: {
						dependent: {
							custom: {
								type: 'invalid',
								message: 'Value must be a number.',
							},
						},
					},
				},
			},
			{
				'first-group': {
					children: {
						dependent: {
							custom: {
								type: 'invalid',
								message: 'Extension error.',
							},
						},
					},
				},
			}
		);

		expect(
			merged?.[ 'first-group' ]?.children?.dependent.custom?.message
		).toBe( 'Value must be a number.' );
		expect( isFormValidityValid( merged ) ).toBe( false );
	} );

	it( 'removes stale validity after a dependent field is hidden', () => {
		const staleValidity = {
			'first-group': {
				children: {
					dependent: {
						custom: { type: 'invalid' as const, message: 'Stale.' },
					},
				},
			},
		};
		const visibleForm: Form = {
			fields: [
				{ id: 'first-group', children: [] },
				{ id: 'second-group', children: [ 'controller' ] },
			],
		};

		expect(
			filterDataFormValidity( staleValidity, visibleForm )
		).toBeUndefined();
		expect(
			isFormValidityValid(
				filterDataFormValidity( staleValidity, visibleForm )
			)
		).toBe( true );
	} );
} );
