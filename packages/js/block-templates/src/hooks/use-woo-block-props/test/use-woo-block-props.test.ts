/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react-hooks';
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { useWooBlockProps } from '../use-woo-block-props';

jest.mock( '@wordpress/block-editor', () => ( {
	useBlockProps: jest.fn(),
} ) );

describe( 'useWooBlockProps', () => {
	it( 'should return the block props with the block id and block order attributes', () => {
		renderHook( () =>
			useWooBlockProps(
				{
					foo: 'bar',
					_templateBlockId: 'test/block',
					_templateBlockOrder: 30,
				},
				{
					className: 'test',
				}
			)
		);

		expect( useBlockProps ).toHaveBeenCalledWith( {
			'data-template-block-id': 'test/block',
			'data-template-block-order': 30,
			className: 'test',
		} );
	} );
} );
