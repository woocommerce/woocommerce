/**
 * Internal dependencies
 */
import { buildDataFormField } from '../dataform-adapter';
import type { DataFormAdapterOptions } from '../dataform-adapter';
import type {
	SettingsUIField,
	SettingsUIGroup,
	SettingsValue,
	SettingsValues,
} from '../types';

/**
 * Every descriptor must obey the same value-boundary laws regardless of
 * type: presenting a schema value to the control and writing it straight
 * back must restore the original representation, and control writes must
 * land in the schema's option vocabulary. Each law runs against every
 * type so a new descriptor cannot drift without failing here.
 */

const options = (
	field: SettingsUIField,
	initialValues: SettingsValues
): DataFormAdapterOptions => {
	const group: SettingsUIGroup = { id: 'general', fields: [ field ] };
	return {
		schema: {
			id: 'laws',
			title: 'Laws',
			section: 'default',
			save: { adapter: 'none' },
			groups: { general: group },
		},
		context: { page: 'laws', section: '' },
		initialValues,
	};
};

type LawCase = {
	type: string;
	fieldExtras?: Partial< SettingsUIField >;
	// Schema-shaped values the PHP layer can deliver for this type.
	initials: SettingsValue[];
	// Control-side writes and the schema vocabulary they must land in.
	writes?: Array< { control: unknown; expected: SettingsValue } >;
};

const OPTION_SET = {
	options: [
		{ value: 'alpha', label: 'Alpha' },
		// PHP integer keys arrive as numbers at runtime.
		{ value: 2 as unknown as string, label: 'Two' },
	],
};

const LAW_CASES: LawCase[] = [
	{
		type: 'checkbox',
		initials: [ 'yes', 'no', true, false, 1, 0, '1', '0' ],
		writes: [
			{ control: true, expected: 'yes' },
			{ control: false, expected: 'no' },
		],
	},
	{
		type: 'number',
		initials: [ '42', 42, '3.5', '' ],
		writes: [ { control: 7, expected: '7' } ],
	},
	{
		type: 'select',
		fieldExtras: OPTION_SET,
		initials: [ 'alpha', 2 ],
		writes: [
			{ control: 'alpha', expected: 'alpha' },
			// Selecting the numeric option restores the numeric value.
			{ control: '2', expected: 2 },
		],
	},
	{
		type: 'radio',
		fieldExtras: OPTION_SET,
		initials: [ 'alpha', 2 ],
		writes: [ { control: '2', expected: 2 } ],
	},
	{
		type: 'array',
		initials: [ [ 'red', 'blue' ], [] ],
		writes: [ { control: [ 'red' ], expected: [ 'red' ] } ],
	},
	{ type: 'text', initials: [ 'hello' ] },
	{ type: 'email', initials: [ 'a@b.co' ] },
	{ type: 'url', initials: [ 'https://a.co' ] },
	{ type: 'password', initials: [ 's3cret' ] },
	{ type: 'tel', initials: [ '+1 555' ] },
	{ type: 'textarea', initials: [ 'line one\nline two' ] },
	{ type: 'time', initials: [ '13:30' ] },
	{ type: 'date', initials: [ '2026-07-17' ] },
	{ type: 'datetime-local', initials: [ '2026-07-17T13:30' ] },
	{ type: 'color', initials: [ '#123456' ] },
];

const buildField = ( lawCase: LawCase, initial: SettingsValue ) => {
	const settingsField: SettingsUIField = {
		id: 'field',
		label: 'Field',
		type: lawCase.type,
		...lawCase.fieldExtras,
	};
	const initialValues = { field: initial };
	return {
		field: buildDataFormField(
			settingsField,
			options( settingsField, initialValues )
		),
		initialValues,
	};
};

describe( 'descriptor value-boundary laws', () => {
	describe.each( LAW_CASES.map( ( c ) => [ c.type, c ] as const ) )(
		'%s',
		( _type, lawCase ) => {
			it.each(
				lawCase.initials.map( ( initial ) => [
					JSON.stringify( initial ),
					initial,
				] )
			)(
				'write-back of the presented value restores %s exactly',
				( _label, initial ) => {
					const { field, initialValues } = buildField(
						lawCase,
						initial
					);
					const presented = field.getValue?.( {
						item: initialValues,
					} );
					const restored = field.setValue?.( {
						item: initialValues,
						value: presented,
					} );

					expect( restored ).toEqual( { field: initial } );
				}
			);

			if ( lawCase.writes ) {
				it.each(
					lawCase.writes.map( ( write ) => [
						JSON.stringify( write.control ),
						write,
					] )
				)(
					'control write %s lands in schema vocabulary',
					( _label, write ) => {
						const { field, initialValues } = buildField(
							lawCase,
							lawCase.initials[ 0 ]
						);
						const result = field.setValue?.( {
							item: initialValues,
							value: write.control,
						} );

						expect( result ).toEqual( {
							field: write.expected,
						} );
					}
				);
			}
		}
	);
} );
