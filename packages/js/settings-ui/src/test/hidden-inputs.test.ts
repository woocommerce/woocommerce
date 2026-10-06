/**
 * External dependencies
 */
import { getSettings, setSettings } from '@wordpress/date';

/**
 * Internal dependencies
 */
import { getHiddenInputs } from '../hidden-inputs';
import type { SettingsUIField, SettingsValue } from '../types';

const formPostField = (
	overrides: Partial< SettingsUIField > = {}
): SettingsUIField => ( {
	id: 'quantity',
	label: 'Quantity',
	type: 'number',
	save: { adapter: 'form_post', name: 'quantity' },
	...overrides,
} );

const unsupportedFieldCases = [
	{
		label: 'save adapter',
		field: formPostField( {
			save: { adapter: 'custom', name: 'quantity' },
		} ),
		message: 'Save adapter "custom" is not supported.',
	},
	{
		label: 'form-post field name',
		field: formPostField( {
			save: {
				adapter: 'form_post',
				name: 'settings[group][quantity',
			},
		} ),
		message:
			'Form-post field name "settings[group][quantity" is not supported.',
	},
	{
		label: 'list initialValue for a scalar field',
		field: formPostField( {
			save: {
				adapter: 'form_post',
				name: 'quantity',
				initialValue: [ '1', '2' ],
			},
		} ),
		message:
			'Field "quantity" has a list initialValue but is not an array field.',
	},
];

const unsupportedDefaultCases = unsupportedFieldCases.filter(
	( { label } ) => label !== 'form-post field name'
);

describe( 'getHiddenInputs', () => {
	it( 'serializes checkbox values for legacy form posts', () => {
		expect(
			getHiddenInputs(
				{
					id: 'enabled',
					label: 'Enabled',
					type: 'checkbox',
					save: { adapter: 'form_post', name: 'enabled' },
				},
				true
			)
		).toEqual( [ { name: 'enabled', value: 'yes' } ] );
	} );

	it.each( [ 'yes', '1' ] )(
		'treats the legacy truthy-string checkbox value %p as checked',
		( legacyValue ) => {
			expect(
				getHiddenInputs(
					{
						id: 'enabled',
						label: 'Enabled',
						type: 'checkbox',
						save: { adapter: 'form_post', name: 'enabled' },
					},
					legacyValue
				)
			).toEqual( [ { name: 'enabled', value: 'yes' } ] );
		}
	);

	it( 'serializes changed checkbox values instead of their original form representation', () => {
		expect(
			getHiddenInputs(
				{
					id: 'enabled',
					label: 'Enabled',
					type: 'checkbox',
					save: {
						adapter: 'form_post',
						name: 'enabled',
						initialValue: 'yes',
					},
				},
				false,
				{ initialCanonicalValue: true }
			)
		).toEqual( [ { name: 'enabled', value: 'no' } ] );
	} );

	it( 'serializes array values with bracketed field names', () => {
		expect(
			getHiddenInputs(
				{
					id: 'methods',
					label: 'Methods',
					type: 'array',
					save: { adapter: 'form_post', name: 'methods' },
				},
				[ 'card', 'link' ]
			)
		).toEqual( [
			{ name: 'methods[]', value: 'card' },
			{ name: 'methods[]', value: 'link' },
		] );
	} );

	it( 'omits an unchanged empty array like the classic form', () => {
		expect(
			getHiddenInputs(
				{
					id: 'methods',
					label: 'Methods',
					type: 'array',
					save: {
						adapter: 'form_post',
						name: 'methods',
						initialValue: [],
					},
				},
				[],
				{ initialCanonicalValue: [] }
			)
		).toEqual( [] );
	} );

	it( 'does not serialize fields using the none adapter', () => {
		expect(
			getHiddenInputs(
				{
					id: 'info',
					label: 'Info',
					type: 'info',
					save: { adapter: 'none' },
				},
				''
			)
		).toEqual( [] );
	} );

	it( 'serializes the current value in a two-argument call even when an original form value exists', () => {
		const field = formPostField( {
			save: {
				adapter: 'form_post',
				name: 'quantity',
				initialValue: '02',
			},
		} );

		expect( getHiddenInputs( field, 2 ) ).toEqual( [
			{ name: 'quantity', value: '2' },
		] );
	} );

	it.each( unsupportedDefaultCases )(
		'handles an unsupported $label gracefully by default',
		( { field, message } ) => {
			const consoleError = jest
				.spyOn( console, 'error' )
				.mockImplementation( () => undefined );

			expect(
				getHiddenInputs( field, 2, { initialCanonicalValue: 1 } )
			).toEqual( [] );
			expect( consoleError ).toHaveBeenCalledWith(
				`[WooCommerce settings UI] ${ message }`,
				{ field }
			);

			consoleError.mockRestore();
		}
	);

	it( 'keeps legacy field names serializable through the public default API', () => {
		expect(
			getHiddenInputs(
				formPostField( {
					save: { adapter: 'form_post', name: 'settings[]' },
				} ),
				2
			)
		).toEqual( [ { name: 'settings[]', value: '2' } ] );
	} );

	it( 'preserves a zero form-post field name', () => {
		expect(
			getHiddenInputs(
				formPostField( {
					save: { adapter: 'form_post', name: '0' },
				} ),
				2,
				{ strict: true }
			)
		).toEqual( [ { name: '0', value: '2' } ] );
	} );

	it.each( unsupportedFieldCases )(
		'throws for an unsupported $label in strict mode',
		( { field, message } ) => {
			expect( () =>
				getHiddenInputs( field, 2, {
					initialCanonicalValue: 1,
					strict: true,
				} )
			).toThrow( message );
		}
	);

	it( 'preserves the original form representation while the canonical value is unchanged', () => {
		const field = formPostField( {
			save: {
				adapter: 'form_post',
				name: 'quantity',
				initialValue: '02',
			},
		} );

		expect(
			getHiddenInputs( field, 2, { initialCanonicalValue: 2 } )
		).toEqual( [ { name: 'quantity', value: '02' } ] );
	} );

	it( 'serializes an edited canonical value', () => {
		const field = formPostField( {
			save: {
				adapter: 'form_post',
				name: 'quantity',
				initialValue: '02',
			},
		} );

		expect(
			getHiddenInputs( field, 3, { initialCanonicalValue: 2 } )
		).toEqual( [ { name: 'quantity', value: '3' } ] );
	} );

	it( 'serializes a cleared canonical number as an empty string', () => {
		const field = formPostField( {
			save: {
				adapter: 'form_post',
				name: 'quantity',
				initialValue: '02',
			},
		} );

		expect(
			getHiddenInputs( field, null, { initialCanonicalValue: 2 } )
		).toEqual( [ { name: 'quantity', value: '' } ] );
	} );

	it.each< [ SettingsValue, SettingsValue, boolean ] >( [
		[ 0, 0, true ],
		[ 0, '0', false ],
		[ '', '', true ],
		[ '', false, false ],
		[ false, false, true ],
		[ false, null, false ],
		[ null, null, true ],
		[ null, '', false ],
		[ [], [], true ],
		[ [], [ '' ], false ],
	] )(
		'distinguishes exact falsey values (%p and %p)',
		( currentValue, initialValue, isUnchanged ) => {
			const field = formPostField( {
				save: {
					adapter: 'form_post',
					name: 'quantity',
					initialValue: 'original',
				},
			} );
			const [ input ] = getHiddenInputs( field, currentValue, {
				initialCanonicalValue: initialValue,
			} );

			expect( input?.value === 'original' ).toBe( isUnchanged );
		}
	);

	it( 'serializes one-level nested names', () => {
		expect(
			getHiddenInputs(
				formPostField( {
					save: {
						adapter: 'form_post',
						name: 'settings[quantity]',
					},
				} ),
				2,
				{ initialCanonicalValue: 1 }
			)
		).toEqual( [ { name: 'settings[quantity]', value: '2' } ] );
	} );

	it( 'serializes deep nested names for backward compatibility', () => {
		expect(
			getHiddenInputs(
				formPostField( {
					save: {
						adapter: 'form_post',
						name: 'settings[group][quantity]',
					},
				} ),
				2,
				{ initialCanonicalValue: 1 }
			)
		).toEqual( [ { name: 'settings[group][quantity]', value: '2' } ] );
	} );

	it( 'rejects deep nested names in strict schema mode', () => {
		expect( () =>
			getHiddenInputs(
				formPostField( {
					save: {
						adapter: 'form_post',
						name: 'settings[group][quantity]',
					},
				} ),
				2,
				{ strict: true }
			)
		).toThrow(
			'Form-post field name "settings[group][quantity]" is not supported.'
		);
	} );

	it( 'preserves original array entries with bracketed one-level nested names', () => {
		expect(
			getHiddenInputs(
				formPostField( {
					type: 'array',
					save: {
						adapter: 'form_post',
						name: 'settings[methods]',
						initialValue: [ 'card', 'link' ],
					},
				} ),
				[ 'card', 'link' ],
				{ initialCanonicalValue: [ 'card', 'link' ] }
			)
		).toEqual( [
			{ name: 'settings[methods][]', value: 'card' },
			{ name: 'settings[methods][]', value: 'link' },
		] );
	} );

	it( 'serializes current array entries with existing bracketed one-level nested names', () => {
		expect(
			getHiddenInputs(
				formPostField( {
					type: 'array',
					save: {
						adapter: 'form_post',
						name: 'settings[methods][]',
					},
				} ),
				[ 'card', 'link' ]
			)
		).toEqual( [
			{ name: 'settings[methods][]', value: 'card' },
			{ name: 'settings[methods][]', value: 'link' },
		] );
	} );

	it( 'serializes array entries with existing deep nested names', () => {
		expect(
			getHiddenInputs(
				formPostField( {
					type: 'array',
					save: {
						adapter: 'form_post',
						name: 'settings[group][methods][]',
					},
				} ),
				[ 'card', 'link' ]
			)
		).toEqual( [
			{ name: 'settings[group][methods][]', value: 'card' },
			{ name: 'settings[group][methods][]', value: 'link' },
		] );
	} );

	it( 'keeps disabled fields in the form-post entry list', () => {
		expect(
			getHiddenInputs( formPostField( { disabled: true } ), 2, {
				initialCanonicalValue: 1,
			} )
		).toEqual( [ { name: 'quantity', value: '2' } ] );
	} );

	it( 'keeps hidden fields in the form-post entry list', () => {
		expect(
			getHiddenInputs(
				formPostField( {
					visibility: { controller: 'enabled', value: true },
				} ),
				2,
				{ initialCanonicalValue: 1 }
			)
		).toEqual( [ { name: 'quantity', value: '2' } ] );
	} );

	it( 'preserves the original form representation for an unchanged canonical datetime', () => {
		expect(
			getHiddenInputs(
				formPostField( {
					type: 'datetime-local',
					save: {
						adapter: 'form_post',
						name: 'starts_at',
						initialValue: '2026-01-01T12:00',
					},
				} ),
				'2026-01-01T12:00:00Z',
				{ initialCanonicalValue: '2026-01-01T12:00:00Z' }
			)
		).toEqual( [ { name: 'starts_at', value: '2026-01-01T12:00' } ] );
	} );

	it( 'serializes an edited canonical datetime back to store-local form', () => {
		expect(
			getHiddenInputs(
				formPostField( {
					type: 'datetime-local',
					save: {
						adapter: 'form_post',
						name: 'starts_at',
						initialValue: '2026-01-01T12:00',
					},
				} ),
				'2026-01-01T13:30:00Z',
				{
					initialCanonicalValue: '2026-01-01T12:00:00Z',
					serializeDateTimeAsStoreLocal: true,
				}
			)
		).toEqual( [ { name: 'starts_at', value: '2026-01-01T13:30:00' } ] );
	} );

	it( 'keeps datetime serialization unchanged for public two-argument calls', () => {
		expect(
			getHiddenInputs(
				formPostField( {
					type: 'datetime-local',
					save: { adapter: 'form_post', name: 'starts_at' },
				} ),
				'2026-01-01T17:30:00Z'
			)
		).toEqual( [ { name: 'starts_at', value: '2026-01-01T17:30:00Z' } ] );
	} );

	it( 'serializes an edited datetime in a non-UTC store timezone', () => {
		const previousSettings = getSettings();
		setSettings( {
			...previousSettings,
			timezone: {
				...previousSettings.timezone,
				offset: '-5',
				offsetFormatted: '-05:00',
				string: 'America/New_York',
				abbr: 'EST',
			},
		} );

		try {
			expect(
				getHiddenInputs(
					formPostField( {
						type: 'datetime-local',
						save: {
							adapter: 'form_post',
							name: 'starts_at',
						},
					} ),
					'2026-01-01T17:30:00Z',
					{ serializeDateTimeAsStoreLocal: true }
				)
			).toEqual( [
				{ name: 'starts_at', value: '2026-01-01T12:30:00' },
			] );
		} finally {
			setSettings( previousSettings );
		}
	} );
} );
