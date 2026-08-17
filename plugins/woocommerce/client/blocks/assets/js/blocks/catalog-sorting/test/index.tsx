/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import { registerBlockType } from '@wordpress/blocks';
import type { ComponentType, ReactNode } from 'react';

/**
 * Internal dependencies
 */
import metadata from '../block.json';

jest.mock( '@wordpress/blocks', () => ( {
	registerBlockType: jest.fn(),
} ) );

jest.mock( '@wordpress/block-editor', () => ( {
	InspectorControls: ( { children }: { children: ReactNode } ) => (
		<div>{ children }</div>
	),
	useBlockProps: ( props: Record< string, unknown > ) => props,
} ) );

jest.mock( '@wordpress/components', () => ( {
	Disabled: ( { children }: { children: ReactNode } ) => <>{ children }</>,
	ToggleControl: () => null,
	__experimentalToolsPanel: ( { children }: { children: ReactNode } ) => (
		<div>{ children }</div>
	),
	__experimentalToolsPanelItem: ( { children }: { children: ReactNode } ) => (
		<div>{ children }</div>
	),
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
	expect( registeredMetadata.name ).toBe( 'woocommerce/catalog-sorting' );
	expect( settings.attributes ).toEqual( metadata.attributes );

	return settings as RegisteredBlock;
};

describe( 'Catalog Sorting block registration', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'registers its real editor placeholder and dynamic save callback', () => {
		const { edit: Edit, save } = loadRegisteredBlock();

		render(
			<Edit
				attributes={ { useLabel: false } }
				setAttributes={ jest.fn() }
			/>
		);

		expect(
			screen.getByRole( 'option', { name: 'Default sorting' } )
		).toBeInTheDocument();
		expect( save() ).toBeNull();
	} );
} );
