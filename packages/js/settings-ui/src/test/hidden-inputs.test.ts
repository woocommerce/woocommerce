/**
 * External dependencies
 */
import { getSettings, setSettings } from '@wordpress/date';

/**
 * Internal dependencies
 */
import {
	fromLocalDatetime,
	getHiddenInputs,
	toLocalDatetime,
} from '../hidden-inputs';
import type { SettingsUIField } from '../types';

const makeField = (
	overrides: Partial< SettingsUIField > = {}
): SettingsUIField => ( {
	id: 'field',
	label: 'Field',
	type: 'text',
	value: '',
	save: { adapter: 'form_post', name: 'field', initialValue: '' },
	...overrides,
} );

describe( 'getHiddenInputs', () => {
	it( 'preserves the original form value when the canonical value is unchanged', () => {
		const field = makeField( {
			id: 'count',
			type: 'integer',
			value: 2,
			save: {
				adapter: 'form_post',
				name: 'count',
				initialValue: '02',
			},
		} );

		expect( getHiddenInputs( field, 2 ) ).toEqual( [
			{ name: 'count', value: '02' },
		] );
		expect( getHiddenInputs( field, 3 ) ).toEqual( [
			{ name: 'count', value: '3' },
		] );
	} );

	it( 'preserves the 10.9 checkbox and datetime call contract', () => {
		const checkbox = makeField( {
			id: 'legacy_enabled',
			type: 'checkbox',
			save: { adapter: 'form_post', name: 'legacy_enabled' },
		} );
		const datetime = makeField( {
			id: 'legacy_starts_at',
			type: 'datetime-local',
			save: { adapter: 'form_post', name: 'legacy_starts_at' },
		} );

		expect( getHiddenInputs( checkbox, 'yes' ) ).toEqual( [
			{ name: 'legacy_enabled', value: 'yes' },
		] );
		expect( getHiddenInputs( checkbox, '1' ) ).toEqual( [
			{ name: 'legacy_enabled', value: 'yes' },
		] );
		expect( getHiddenInputs( checkbox, 'no' ) ).toEqual( [
			{ name: 'legacy_enabled', value: 'no' },
		] );
		expect( getHiddenInputs( datetime, '2026-07-17T13:30:45' ) ).toEqual( [
			{ name: 'legacy_starts_at', value: '2026-07-17T13:30:45' },
		] );
	} );

	it( 'serializes changed checkbox values for legacy form posts', () => {
		const field = makeField( {
			id: 'enabled',
			type: 'checkbox',
			value: true,
			save: {
				adapter: 'form_post',
				name: 'enabled',
				initialValue: 'yes',
			},
		} );

		expect( getHiddenInputs( field, true ) ).toEqual( [
			{ name: 'enabled', value: 'yes' },
		] );
		expect( getHiddenInputs( field, false ) ).toEqual( [
			{ name: 'enabled', value: 'no' },
		] );
	} );

	it( 'serializes array values with bracketed field names', () => {
		const field = makeField( {
			id: 'methods',
			type: 'array',
			value: [ 'card' ],
			save: {
				adapter: 'form_post',
				name: 'methods',
				initialValue: [ 'card' ],
			},
		} );

		expect( getHiddenInputs( field, [ 'card' ] ) ).toEqual( [
			{ name: 'methods[]', value: 'card' },
		] );
		expect( getHiddenInputs( field, [ 'card', 'link' ] ) ).toEqual( [
			{ name: 'methods[]', value: 'card' },
			{ name: 'methods[]', value: 'link' },
		] );
	} );

	it( 'preserves unchanged datetimes and encodes changed ISO values', () => {
		const field = makeField( {
			id: 'starts_at',
			type: 'datetime-local',
			value: '2026-07-17T13:30:45+00:00',
			save: {
				adapter: 'form_post',
				name: 'starts_at',
				initialValue: '2026-07-17T13:30:45',
			},
		} );

		expect( getHiddenInputs( field, '2026-07-17T13:30:45+00:00' ) ).toEqual(
			[ { name: 'starts_at', value: '2026-07-17T13:30:45' } ]
		);
		expect( getHiddenInputs( field, '2026-07-18T14:45:30.000Z' ) ).toEqual(
			[ { name: 'starts_at', value: '2026-07-18T14:45:30' } ]
		);
		expect( getHiddenInputs( field, null ) ).toEqual( [
			{ name: 'starts_at', value: '' },
		] );
	} );

	it( 'converts datetime controls through the store timezone', () => {
		const originalSettings = getSettings();
		setSettings( {
			...originalSettings,
			timezone: {
				...originalSettings.timezone,
				string: 'America/New_York',
			},
		} );

		try {
			expect( toLocalDatetime( '2026-07-17T17:30:00.000Z' ) ).toBe(
				'2026-07-17T13:30'
			);
			expect( fromLocalDatetime( '2026-07-17T13:30' ) ).toBe(
				'2026-07-17T17:30:00.000Z'
			);
			expect( fromLocalDatetime( '' ) ).toBeNull();
		} finally {
			setSettings( originalSettings );
		}
	} );

	it( 'does not serialize fields using the none adapter', () => {
		expect(
			getHiddenInputs(
				makeField( {
					id: 'info',
					type: 'info',
					save: { adapter: 'none' },
				} ),
				''
			)
		).toEqual( [] );
	} );
} );
