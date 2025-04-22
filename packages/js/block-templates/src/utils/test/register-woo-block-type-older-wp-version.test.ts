/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
/**
 * Internal dependencies
 */
import { registerWooBlockType } from '../register-woo-block-type';

jest.mock( '@wordpress/blocks', () => ( {
	registerBlockType: jest.fn(),
} ) );

jest.mock( '@woocommerce/settings', () => ( {
	isWpVersion: jest.fn().mockReturnValue( true ),
	getSetting: jest.fn().mockReturnValue( {} ),
} ) );

describe( 'registerWooBlockType with older wp version', () => {
	it( 'should add __experimentalRole to attributes when wp version is less than 6.7', () => {
		const block = {
			name: 'test/block',
			metadata: {
				attributes: {
					foo: {
						type: 'boolean',
						default: false,
						role: 'content',
					},
				},
			},
			settings: {
				foo: 'bar',
				edit: jest.fn(),
			},
		};

		( registerBlockType as jest.Mock ).mockClear();
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore ts2345 Complaining about the type of the foo attribute; it's fine.
		registerWooBlockType( block );

		const args = ( registerBlockType as jest.Mock ).mock.calls[ 0 ][ 0 ];
		const attributes = args.attributes;
		for ( const attribute of Object.values( attributes ) ) {
			expect( attribute ).toHaveProperty( '__experimentalRole' );
		}
	} );
} );
