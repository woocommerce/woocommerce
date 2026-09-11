/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import { useEntityProp } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import CategoryTitle from '../edit';
import CategoryDescription from '../../category-description/edit';
import titleMetadata from '../block.json';
import descriptionMetadata from '../../category-description/block.json';

const mockCanUser = jest.fn( () => false );
const mockGetEntityRecord = jest.fn( () => ( {
	link: 'https://example.com/category/context',
} ) );

jest.mock( '@wordpress/data', () => ( {
	useSelect: (
		callback: (
			select: () => {
				canUser: typeof mockCanUser;
				getEntityRecord: typeof mockGetEntityRecord;
			}
		) => unknown
	) =>
		callback( () => ( {
			canUser: mockCanUser,
			getEntityRecord: mockGetEntityRecord,
		} ) ),
} ) );
jest.mock( '@wordpress/core-data', () => ( {
	store: 'core',
	useEntityProp: jest.fn( () => [
		'Context text',
		jest.fn(),
		{ rendered: 'Context text' },
	] ),
} ) );
jest.mock( '@woocommerce/base-hooks', () => ( {
	usePreviewMode: () => false,
} ) );
jest.mock( '@wordpress/components', () => ( {
	ToggleControl: () => null,
	TextControl: () => null,
	__experimentalToolsPanel: () => null,
	__experimentalToolsPanelItem: () => null,
} ) );
jest.mock( '@wordpress/block-editor', () => ( {
	useBlockProps: () => ( {} ),
	AlignmentControl: () => null,
	HeadingLevelDropdown: () => null,
	BlockControls: () => null,
	InspectorControls: () => null,
} ) );

const attributes = {
	isLink: true,
	level: 2,
	linkTarget: '_self',
	rel: '',
};

describe.each( [
	[ 'title', CategoryTitle, 'name', titleMetadata ],
	[ 'description', CategoryDescription, 'description', descriptionMetadata ],
] as const )(
	'Category %s term context',
	( _name, Edit, property, metadata ) => {
		beforeEach( () => jest.clearAllMocks() );

		it.each( [
			[ { termId: 42, taxonomy: 'category' }, 'category' ],
			[ { termId: 42, taxonomy: 'product_cat' }, 'product_cat' ],
			[
				{
					termId: 42,
					termTaxonomy: 'product_cat',
					taxonomy: 'category',
				},
				'product_cat',
			],
			[ { termId: 42 }, 'product_cat' ],
		] )( 'uses the effective taxonomy for %j', ( context, taxonomy ) => {
			// Match the context filtering performed by the block editor.
			const registeredContext = Object.fromEntries(
				Object.entries( context ).filter( ( [ key ] ) =>
					metadata.usesContext.includes( key )
				)
			);
			render(
				<Edit
					attributes={ attributes }
					context={ registeredContext }
					setAttributes={ jest.fn() }
				/>
			);

			expect( useEntityProp ).toHaveBeenCalledWith(
				'taxonomy',
				taxonomy,
				property,
				'42'
			);
			expect( mockCanUser ).toHaveBeenCalledWith( 'update', {
				kind: 'taxonomy',
				name: taxonomy,
				id: 42,
			} );
			expect( screen.getByText( 'Context text' ) ).toBeInTheDocument();
		} );
	}
);

it( 'uses Core taxonomy context to resolve the title link', () => {
	render(
		<CategoryTitle
			attributes={ attributes }
			context={ { termId: 42, taxonomy: 'category' } }
			setAttributes={ jest.fn() }
		/>
	);
	expect( mockGetEntityRecord ).toHaveBeenLastCalledWith(
		'taxonomy',
		'category',
		42
	);
	expect( screen.getByRole( 'link' ) ).toHaveAttribute(
		'href',
		'https://example.com/category/context'
	);
} );
