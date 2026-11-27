/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import '@testing-library/jest-dom';
import {
	getBlockSupport,
	registerBlockType,
	unregisterBlockType,
} from '@wordpress/blocks';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import {
	BlockCompatibilityWarnings,
	hasBackgroundImageSupport,
} from '../block-compatibility-warnings';

jest.mock( '@wordpress/blocks', () => ( {
	...jest.requireActual( '@wordpress/blocks' ),
	getBlockSupport: jest.fn(),
} ) );

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useSelect: jest.fn(),
} ) );

jest.mock( '@wordpress/components', () => ( {
	Fill: ( { children } ) => <div>{ children }</div>,
	Notice: ( { children } ) => <div>{ children }</div>,
} ) );

describe( 'hasBackgroundImageSupport', () => {
	it.each( [
		[ 'supports background images', { backgroundImage: true }, true ],
		[ 'has background support without saying otherwise', {}, true ],
		[ 'turns background images off', { backgroundImage: false }, false ],
		[ 'has no background support', undefined, false ],
	] )( 'warns when the block %s: %s', ( _case, support, expected ) => {
		( getBlockSupport as jest.Mock ).mockReturnValue( support );

		expect( Boolean( hasBackgroundImageSupport( 'core/group' ) ) ).toBe(
			expected
		);
		expect( getBlockSupport ).toHaveBeenCalledWith(
			'core/group',
			'background'
		);
	} );
} );

const BORDER_WARNING =
	'Border display may vary or be unsupported in some email clients.';

const BLOCK_SUPPORTS = {
	'test/experimental-border': {
		__experimentalBorder: { color: true, width: true },
	},
	'test/border': { border: { color: true, width: true } },
	'test/no-border': {},
};

function renderWithSelectedBlock( name: string ) {
	( useSelect as jest.Mock ).mockReturnValue( { name } );
	render( <BlockCompatibilityWarnings /> );
}

describe( 'BlockCompatibilityWarnings', () => {
	beforeAll( () => {
		Object.entries( BLOCK_SUPPORTS ).forEach( ( [ name, supports ] ) => {
			registerBlockType( name, {
				apiVersion: 3,
				title: name,
				category: 'text',
				supports,
				edit: () => null,
				save: () => null,
			} );
		} );
	} );

	afterAll( () => {
		Object.keys( BLOCK_SUPPORTS ).forEach( ( name ) =>
			unregisterBlockType( name )
		);
	} );

	it.each( [ 'test/experimental-border', 'test/border' ] )(
		'shows the border warning for %s',
		( name ) => {
			renderWithSelectedBlock( name );

			expect(
				screen.getByText( BORDER_WARNING, { exact: false } )
			).toBeInTheDocument();
		}
	);

	it( 'does not show the border warning for a block without border support', () => {
		renderWithSelectedBlock( 'test/no-border' );

		expect(
			screen.queryByText( BORDER_WARNING, { exact: false } )
		).not.toBeInTheDocument();
	} );
} );
