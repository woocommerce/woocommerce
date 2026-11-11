/**
 * External dependencies
 */
import {
	registerBlockType,
	unregisterBlockType,
	registerBlockStyle,
} from '@wordpress/blocks';
import { select } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { removeBlockStylesFromAllBlocks } from '../general-block-support';

jest.mock(
	'@wordpress/dom-ready',
	() => ( callback: () => void ) => callback()
);

const BLOCKS = [ 'core/separator', 'core/social-links', 'test/other' ];

function styleNames( blockName: string ): string[] {
	return select( 'core/blocks' )
		.getBlockStyles( blockName )
		.map( ( style: { name: string } ) => style.name );
}

describe( 'removeBlockStylesFromAllBlocks', () => {
	beforeEach( () => {
		BLOCKS.forEach( ( name ) => {
			registerBlockType( name, {
				apiVersion: 3,
				title: name,
				category: 'design',
				edit: () => null,
				save: () => null,
			} );
		} );
		[ 'default', 'wide', 'dots', 'theme-fancy' ].forEach( ( style ) =>
			registerBlockStyle( 'core/separator', {
				name: style,
				label: style,
			} )
		);
		[ 'default', 'pill-shape', 'theme-fancy' ].forEach( ( style ) =>
			registerBlockStyle( 'core/social-links', {
				name: style,
				label: style,
			} )
		);
		registerBlockStyle( 'test/other', { name: 'outline', label: 'x' } );
	} );

	afterEach( () => {
		BLOCKS.forEach( ( name ) => unregisterBlockType( name ) );
	} );

	it( 'keeps only the separator styles the email renderer supports', () => {
		removeBlockStylesFromAllBlocks();

		expect( styleNames( 'core/separator' ) ).toEqual( [
			'default',
			'wide',
			'dots',
		] );
	} );

	it( 'keeps every style of a block that supports them all', () => {
		removeBlockStylesFromAllBlocks();

		expect( styleNames( 'core/social-links' ) ).toEqual( [
			'default',
			'pill-shape',
			'theme-fancy',
		] );
	} );

	it( 'removes the styles of blocks without supported styles', () => {
		removeBlockStylesFromAllBlocks();

		expect( styleNames( 'test/other' ) ).toEqual( [] );
	} );
} );
