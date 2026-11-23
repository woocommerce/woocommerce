/**
 * External dependencies
 */
import { render, screen, fireEvent } from '@testing-library/react';
import {
	registerBlockType,
	unregisterBlockType,
	getBlockType,
	createBlock,
	switchToBlockType,
	getPossibleBlockTransformations,
} from '@wordpress/blocks';
import type { BlockInstance } from '@wordpress/blocks';
/**
 * Internal dependencies
 */
import { enhanceSiteLogoBlock } from '../site-logo';
import { enhanceSiteTitleBlock } from '../site-title';
import { restoreAllModifiedBlockSettings } from '../../../config-tools/block-config';
import { resetBlockVariations } from '../../../config-tools/block-variations';

const mockState: {
	editorSettings: { readOnlySiteIdentityBlocks?: boolean };
	site: { name: string; home: string; site_logo?: number };
} = {
	editorSettings: {},
	site: { name: '', home: '' },
};

const mockMedia = {
	id: 42,
	source_url: 'http://example.com/logo.png',
	alt_text: 'Store logo',
};

jest.mock( '../../../store', () => ( {
	storeName: 'email-editor/editor',
} ) );

jest.mock( '@wordpress/core-data', () => ( {
	store: { name: 'core' },
} ) );

jest.mock( '@wordpress/block-editor', () => ( {
	store: { name: 'core/block-editor' },
	useBlockProps: ( props = {} ) => props,
	useBlockEditingMode: () => 'default',
	BlockControls: ( { children }: { children: React.ReactNode } ) => (
		<>{ children }</>
	),
	InspectorControls: ( { children }: { children: React.ReactNode } ) => (
		<>{ children }</>
	),
	AlignmentControl: () => <div>alignment control</div>,
	HeadingLevelDropdown: () => null,
} ) );

jest.mock( '@wordpress/data', () => {
	const actual = jest.requireActual( '@wordpress/data' );
	const fakeSelectors: Record< string, Record< string, unknown > > = {
		'email-editor/editor': {
			getInitialEditorSettings: () => mockState.editorSettings,
		},
		core: {
			getEntityRecord: ( kind: string, name: string, id?: number ) => {
				if ( kind === 'root' && name === '__unstableBase' ) {
					return mockState.site;
				}
				if (
					kind === 'postType' &&
					name === 'attachment' &&
					id === mockMedia.id
				) {
					return mockMedia;
				}
				return undefined;
			},
			hasFinishedResolution: () => true,
		},
		'core/block-editor': {
			getSettings: () => ( { maxWidth: 580 } ),
		},
	};
	const fakeSelect = ( store: string | { name: string } ) => {
		const name = typeof store === 'string' ? store : store?.name;
		return fakeSelectors[ name ] ?? actual.select( store );
	};
	return {
		...actual,
		select: jest.fn( fakeSelect ),
		useSelect: jest.fn( ( callback ) => callback( fakeSelect ) ),
	};
} );

const OriginalEdit = () => <div>core edit</div>;

function renderEdit( blockName: string, attributes: object ) {
	const setAttributes = jest.fn();
	const Edit = getBlockType( blockName )?.edit as React.ElementType;
	render(
		<Edit
			name={ blockName }
			clientId="block-1"
			attributes={ attributes }
			setAttributes={ setAttributes }
			isSelected
		/>
	);
	return { setAttributes };
}

function possibleTransformNames( block: BlockInstance ) {
	return getPossibleBlockTransformations( [ block ] ).map(
		( blockType ) => blockType.name
	);
}

beforeEach( () => {
	mockState.editorSettings = { readOnlySiteIdentityBlocks: true };
	mockState.site = {
		name: 'Ticket &amp; Store',
		home: 'http://example.com',
		site_logo: mockMedia.id,
	};
} );

describe( 'Site Title block', () => {
	beforeEach( () => {
		registerBlockType( 'core/site-title', {
			apiVersion: 3,
			title: 'Site Title',
			category: 'theme',
			attributes: {
				level: { type: 'number', default: 1 },
				levelOptions: { type: 'array' },
				textAlign: { type: 'string' },
				isLink: { type: 'boolean', default: true },
				linkTarget: { type: 'string', default: '_self' },
				style: { type: 'object' },
			},
			edit: OriginalEdit,
			save: () => null,
		} );
		registerBlockType( 'core/heading', {
			apiVersion: 3,
			title: 'Heading',
			category: 'text',
			attributes: {
				content: { type: 'string' },
				level: { type: 'number', default: 2 },
				textAlign: { type: 'string' },
				style: { type: 'object' },
			},
			edit: () => null,
			save: () => null,
		} );
		enhanceSiteTitleBlock();
	} );

	afterEach( () => {
		restoreAllModifiedBlockSettings();
		unregisterBlockType( 'core/site-title' );
		unregisterBlockType( 'core/heading' );
	} );

	it( 'renders the original edit component when the setting is off', () => {
		mockState.editorSettings = {};
		renderEdit( 'core/site-title', { level: 2, isLink: true } );
		expect( screen.getByText( 'core edit' ) ).toBeInTheDocument();
	} );

	it( 'shows the site title without letting the user edit it', () => {
		renderEdit( 'core/site-title', { level: 2, isLink: true } );

		const heading = screen.getByRole( 'heading', {
			level: 2,
			name: 'Ticket & Store',
		} );
		expect( screen.queryByText( 'core edit' ) ).not.toBeInTheDocument();
		expect( heading.querySelector( '[contenteditable]' ) ).toBeNull();
	} );

	it( 'explains how to change the title in the inspector', () => {
		renderEdit( 'core/site-title', { level: 2 } );
		expect(
			screen.getByText( /This shows your site title\./ )
		).toBeInTheDocument();
	} );

	it( 'keeps the link setting editable', () => {
		const { setAttributes } = renderEdit( 'core/site-title', {
			level: 2,
			isLink: true,
			linkTarget: '_self',
		} );
		fireEvent.click(
			screen.getByRole( 'checkbox', { name: 'Make title link to home' } )
		);
		expect( setAttributes ).toHaveBeenCalledWith( { isLink: false } );
	} );

	it( 'shows its own alignment control only when the block has a textAlign attribute', () => {
		renderEdit( 'core/site-title', { level: 2 } );
		expect( screen.getByText( 'alignment control' ) ).toBeInTheDocument();
	} );

	it( 'leaves text alignment to block supports when the block has no textAlign attribute', () => {
		// From WordPress 7.1, Site Title aligns text through the typography block support.
		restoreAllModifiedBlockSettings();
		unregisterBlockType( 'core/site-title' );
		registerBlockType( 'core/site-title', {
			apiVersion: 3,
			title: 'Site Title',
			category: 'theme',
			attributes: {
				level: { type: 'number', default: 1 },
				isLink: { type: 'boolean', default: true },
				linkTarget: { type: 'string', default: '_self' },
			},
			edit: OriginalEdit,
			save: () => null,
		} );
		enhanceSiteTitleBlock();

		renderEdit( 'core/site-title', { level: 2 } );
		expect(
			screen.getByRole( 'heading', { name: 'Ticket & Store' } )
		).toBeInTheDocument();
		expect(
			screen.queryByText( 'alignment control' )
		).not.toBeInTheDocument();
	} );

	it( 'transforms to a heading with the site title as text', () => {
		const block = createBlock( 'core/site-title', {
			level: 3,
			textAlign: 'center',
			style: { spacing: { padding: { top: '10px' } } },
		} );
		expect( possibleTransformNames( block ) ).toContain( 'core/heading' );

		const [ heading ] = switchToBlockType( block, 'core/heading' ) ?? [];
		expect( heading.name ).toBe( 'core/heading' );
		expect( heading.attributes ).toEqual( {
			content: 'Ticket &amp; Store',
			level: 3,
			textAlign: 'center',
			style: { spacing: { padding: { top: '10px' } } },
		} );
	} );

	it( 'uses the default heading level for a paragraph site title', () => {
		const block = createBlock( 'core/site-title', { level: 0 } );
		const [ heading ] = switchToBlockType( block, 'core/heading' ) ?? [];
		expect( heading.attributes.level ).toBe( 2 );
	} );

	it( 'does not offer the heading transform when the setting is off', () => {
		mockState.editorSettings = {};
		const block = createBlock( 'core/site-title', { level: 2 } );
		expect( possibleTransformNames( block ) ).not.toContain(
			'core/heading'
		);
	} );
} );

describe( 'Site Logo block', () => {
	const coreTransform = {
		type: 'block' as const,
		blocks: [ 'core/site-title' ],
		transform: () => createBlock( 'core/site-title' ),
	};

	beforeEach( () => {
		registerBlockType( 'core/site-logo', {
			apiVersion: 3,
			title: 'Site Logo',
			category: 'theme',
			attributes: {
				width: { type: 'number' },
				isLink: { type: 'boolean', default: true },
				linkTarget: { type: 'string', default: '_self' },
				shouldSyncIcon: { type: 'boolean' },
				align: { type: 'string' },
				style: { type: 'object' },
			},
			transforms: { to: [ coreTransform ] },
			edit: OriginalEdit,
			save: () => null,
		} );
		registerBlockType( 'core/site-title', {
			apiVersion: 3,
			title: 'Site Title',
			category: 'theme',
			edit: () => null,
			save: () => null,
		} );
		registerBlockType( 'core/image', {
			apiVersion: 3,
			title: 'Image',
			category: 'media',
			attributes: {
				url: { type: 'string' },
				alt: { type: 'string' },
				id: { type: 'number' },
				width: { type: 'string' },
				href: { type: 'string' },
				linkTarget: { type: 'string' },
				linkDestination: { type: 'string' },
				align: { type: 'string' },
				style: { type: 'object' },
			},
			edit: () => null,
			save: () => null,
		} );
		enhanceSiteLogoBlock();
	} );

	afterEach( () => {
		restoreAllModifiedBlockSettings();
		resetBlockVariations();
		unregisterBlockType( 'core/site-logo' );
		unregisterBlockType( 'core/site-title' );
		unregisterBlockType( 'core/image' );
	} );

	it( 'renders the original edit component when the setting is off', () => {
		mockState.editorSettings = {};
		renderEdit( 'core/site-logo', { width: 120 } );
		expect( screen.getByText( 'core edit' ) ).toBeInTheDocument();
	} );

	it( 'shows the site logo without the controls that change site settings', () => {
		renderEdit( 'core/site-logo', { width: 120, isLink: true } );

		const image = screen.getByRole( 'img', { name: 'Store logo' } );
		expect( image ).toHaveClass( 'custom-logo' );
		expect( image ).toHaveAttribute( 'src', mockMedia.source_url );
		expect( image.closest( 'a' ) ).toHaveAttribute(
			'href',
			'http://example.com'
		);
		expect( screen.queryByText( 'core edit' ) ).not.toBeInTheDocument();
		expect( screen.queryByText( 'Replace' ) ).not.toBeInTheDocument();
		expect(
			screen.queryByText( 'Use as Site Icon' )
		).not.toBeInTheDocument();
	} );

	it( 'explains how to change the logo in the inspector', () => {
		renderEdit( 'core/site-logo', { width: 120 } );
		expect(
			screen.getByText( /This shows your site logo\./ )
		).toBeInTheDocument();
	} );

	it( 'keeps the image width editable', () => {
		const { setAttributes } = renderEdit( 'core/site-logo', {
			width: 120,
		} );
		fireEvent.change(
			screen.getByRole( 'spinbutton', { name: 'Image width' } ),
			{ target: { value: '200' } }
		);
		expect( setAttributes ).toHaveBeenCalledWith( { width: 200 } );
	} );

	it( 'shows a placeholder when the site has no logo', () => {
		mockState.site = { ...mockState.site, site_logo: undefined };
		renderEdit( 'core/site-logo', { width: 120 } );
		expect( screen.queryByRole( 'img' ) ).not.toBeInTheDocument();
	} );

	it( 'keeps the core transforms', () => {
		const to = getBlockType( 'core/site-logo' )?.transforms?.to ?? [];
		expect( to ).toContain( coreTransform );
	} );

	it( 'transforms to an image with the logo, width and link', () => {
		const block = createBlock( 'core/site-logo', {
			width: 200,
			align: 'right',
			isLink: true,
			linkTarget: '_blank',
			shouldSyncIcon: true,
			style: { spacing: { padding: { top: '10px' } } },
		} );
		expect( possibleTransformNames( block ) ).toContain( 'core/image' );

		const [ image ] = switchToBlockType( block, 'core/image' ) ?? [];
		expect( image.name ).toBe( 'core/image' );
		expect( image.attributes ).toEqual( {
			url: mockMedia.source_url,
			alt: 'Store logo',
			id: mockMedia.id,
			width: '200px',
			align: 'right',
			href: 'http://example.com',
			linkTarget: '_blank',
			linkDestination: 'custom',
			style: { spacing: { padding: { top: '10px' } } },
		} );
	} );

	it( 'uses the default width and no link when they are not set', () => {
		const block = createBlock( 'core/site-logo', { isLink: false } );
		const [ image ] = switchToBlockType( block, 'core/image' ) ?? [];
		expect( image.attributes.width ).toBe( '120px' );
		expect( image.attributes.href ).toBeUndefined();
	} );

	it( 'does not offer the image transform when the setting is off', () => {
		mockState.editorSettings = {};
		const block = createBlock( 'core/site-logo', { width: 120 } );
		expect( possibleTransformNames( block ) ).not.toContain( 'core/image' );
	} );
} );
