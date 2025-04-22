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
	isWpVersion: jest.fn().mockReturnValue( false ),
	getSetting: jest.fn().mockReturnValue( {} ),
} ) );

describe( 'registerWooBlockType', () => {
	it( 'should register a block type with the block id and block order attributes', () => {
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

		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore ts2345 Complaining about the type of the foo attribute; it's fine.
		registerWooBlockType( block );

		expect( registerBlockType ).toHaveBeenCalledWith(
			{
				name: 'test/block',
				attributes: {
					foo: {
						type: 'boolean',
						default: false,
						role: 'content',
					},
					_templateBlockId: {
						type: 'string',
						role: 'content',
					},
					_templateBlockOrder: {
						type: 'integer',
						role: 'content',
					},
					_templateBlockHideConditions: {
						type: 'array',
						role: 'content',
					},
					_templateBlockDisableConditions: {
						role: 'content',
						type: 'array',
					},
					disabled: {
						role: 'content',
						type: 'boolean',
					},
				},
			},
			{
				foo: 'bar',
				edit: expect.any( Function ),
			}
		);
	} );
} );
