/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import { registerBlockType } from '@wordpress/blocks';
import type { ReactNode } from 'react';

/**
 * Internal dependencies
 */
import metadata from '../block.json';
import Edit from '../edit';

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
	attributes: typeof metadata.attributes;
	edit: unknown;
	save: () => null;
};

const loadRegisteredBlock = () => {
	let isolatedEdit: unknown;

	jest.isolateModules( () => {
		isolatedEdit = (
			jest.requireActual( '../edit' ) as {
				default: unknown;
			}
		 ).default;
		jest.requireActual( '../index' );
	} );

	expect( registerBlockType ).toHaveBeenCalledTimes( 1 );
	const [ registeredMetadata, settings ] = ( registerBlockType as jest.Mock )
		.mock.calls[ 0 ];

	expect( registeredMetadata ).toEqual( metadata );
	expect( registeredMetadata.name ).toBe( 'woocommerce/catalog-sorting' );
	expect( settings.attributes ).toEqual( metadata.attributes );

	return {
		registeredMetadata,
		settings: settings as RegisteredBlock,
		isolatedEdit,
	};
};

describe( 'Catalog Sorting block registration', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'registers the real editor component', () => {
		const { registeredMetadata, settings, isolatedEdit } =
			loadRegisteredBlock();

		expect( registeredMetadata ).toEqual( metadata );
		expect( registeredMetadata.name ).toBe( 'woocommerce/catalog-sorting' );
		expect( settings.attributes ).toEqual( metadata.attributes );
		expect( settings.edit ).toBe( isolatedEdit );
	} );

	it( 'registers a dynamic save callback that returns null', () => {
		const { settings } = loadRegisteredBlock();

		expect( settings.save() ).toBeNull();
	} );
} );

describe( 'Catalog Sorting editor placeholder', () => {
	it( 'renders the default sorting option without a visual label', () => {
		render(
			<Edit
				attributes={ { useLabel: false } }
				setAttributes={ jest.fn() }
			/>
		);

		expect(
			screen.getByRole( 'option', { name: 'Default sorting' } )
		).toBeInTheDocument();
	} );
} );
