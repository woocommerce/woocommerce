/**
 * Internal dependencies
 */
import { getHiddenInputs } from '../hidden-inputs';

describe( 'getHiddenInputs', () => {
	it.each( [
		[ true, 'yes' ],
		[ 'yes', 'yes' ],
		[ '1', 'yes' ],
		[ 1, 'yes' ],
		[ false, 'no' ],
		[ 'no', 'no' ],
		[ '0', 'no' ],
		[ 0, 'no' ],
	] )( 'serializes checkbox value %p as %s', ( value, serialized ) => {
		expect(
			getHiddenInputs(
				{
					id: 'enabled',
					label: 'Enabled',
					type: 'checkbox',
					save: { adapter: 'form_post', name: 'enabled' },
				},
				value
			)
		).toEqual( [ { name: 'enabled', value: serialized } ] );
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
} );
