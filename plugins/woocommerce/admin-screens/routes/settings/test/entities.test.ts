/**
 * Internal dependencies
 */
import { getPlainSettings } from '../entities';

describe( 'getPlainSettings', () => {
	it( 'sends every setting as the plain value the endpoint takes', () => {
		expect(
			getPlainSettings(
				{},
				{
					enabled: true,
					settings: {
						title: { value: 'Check payments' },
						instructions: 'Send a check',
						enable_for_methods: [ 'flat_rate' ],
					},
				}
			)
		).toEqual( {
			settings: {
				title: 'Check payments',
				instructions: 'Send a check',
				enable_for_methods: [ 'flat_rate' ],
			},
		} );
	} );

	it( 'leaves the edits alone when no settings changed', () => {
		expect( getPlainSettings( {}, { enabled: true } ) ).toEqual( {} );
	} );
} );
