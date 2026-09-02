/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import { registerBlockType } from '@wordpress/blocks';
import type { ComponentType } from 'react';

/**
 * Internal dependencies
 */
import metadata from '../block.json';

jest.mock( '@wordpress/blocks', () => ( {
	registerBlockType: jest.fn(),
} ) );

jest.mock( '@wordpress/block-editor', () => ( {
	useBlockProps: ( props: Record< string, unknown > ) => props,
} ) );

type RegisteredBlock = {
	edit: ComponentType< Record< string, unknown > >;
	save: () => null;
};

const loadRegisteredBlock = (): RegisteredBlock => {
	jest.isolateModules( () => {
		jest.requireActual( '../index' );
	} );

	expect( registerBlockType ).toHaveBeenCalledTimes( 1 );
	const [ registeredMetadata, settings ] = ( registerBlockType as jest.Mock )
		.mock.calls[ 0 ];

	expect( registeredMetadata ).toEqual( metadata );
	expect( registeredMetadata.name ).toBe(
		'woocommerce/product-results-count'
	);
	expect( settings ).not.toHaveProperty( 'attributes' );

	return settings as RegisteredBlock;
};

describe( 'Product Results Count block registration', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'registers its real editor placeholder and dynamic save callback', () => {
		const { edit: Edit, save } = loadRegisteredBlock();

		render( <Edit /> );

		expect(
			screen.getByText( 'Showing 1-X of X results' )
		).toBeInTheDocument();
		expect( save() ).toBeNull();
	} );
} );
