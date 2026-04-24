/**
 * Internal dependencies
 */
import { getHiddenInputs, getHiddenInputsForField } from '../hidden-inputs';

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

	it( 'serializes child fields from compound fields', () => {
		expect(
			getHiddenInputsForField(
				{
					id: 'gateway_panel',
					label: 'Gateway panel',
					type: 'compound',
					fields: [
						{
							id: 'enabled',
							label: 'Enabled',
							type: 'checkbox',
							save: {
								adapter: 'form_post',
								name: 'gateway_enabled',
							},
						},
						{
							id: 'locations',
							label: 'Locations',
							type: 'array',
							save: {
								adapter: 'form_post',
								name: 'gateway_locations',
							},
						},
					],
				},
				{
					gateway_panel: '',
					enabled: true,
					locations: [ 'cart', 'checkout' ],
				}
			)
		).toEqual( [
			{ name: 'gateway_enabled', value: 'yes' },
			{ name: 'gateway_locations[]', value: 'cart' },
			{ name: 'gateway_locations[]', value: 'checkout' },
		] );
	} );
} );
