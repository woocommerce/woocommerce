/**
 * External dependencies
 */
import { render, screen, fireEvent, act } from '@testing-library/react';

import React from 'react';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../types';

import { fieldExtensions } from './field';

jest.mock( '@wordpress/i18n', () => ( {
	__: ( text: string ) => text,
	_x: ( text: string ) => text,
	sprintf: ( format: string, ...args: unknown[] ) => {
		let i = 0;
		return format.replace( /%[sd]/g, () => String( args[ i++ ] ?? '' ) );
	},
	isRTL: () => false,
} ) );

// Store onSelect callbacks for testing
let mockOnSelectCallback: ( ( selection: any ) => void ) | null = null;
let currentSelection: any[] = [];

jest.mock( '@wordpress/icons', () => ( {
	upload: 'upload-icon',
	closeSmall: 'close-icon',
} ) );

jest.mock( '@wordpress/ui', () => ( {
	IconButton: ( { onClick, label }: any ) => (
		<button onClick={ onClick } aria-label={ label }>
			{ label }
		</button>
	),
} ) );

describe( 'Product Images Field - Edit Component', () => {
	const mockOnChange = jest.fn();

	beforeEach( () => {
		jest.clearAllMocks();
		currentSelection = [];
		window.wp = {
			media: jest.fn( () => {
				let selectCallback = () => {};

				return {
					on: ( event: string, callback: () => void ) => {
						if ( event === 'select' ) {
							selectCallback = callback;
							mockOnSelectCallback = ( selection: any ) => {
								currentSelection = Array.isArray( selection )
									? selection
									: [ selection ];
								selectCallback();
							};
						}
					},
					open: jest.fn(),
					state: () => ( {
						get: () => ( {
							toJSON: () => currentSelection,
						} ),
					} ),
				};
			} ),
		};
	} );

	const createMockImage = (
		id: number,
		src = `image-${ id }.jpg`,
		thumbnail = `thumb-${ id }.jpg`
	) => ( {
		id,
		src,
		alt: `Alt ${ id }`,
		name: `Image ${ id }`,
		thumbnail,
		date_created: '',
		date_created_gmt: '',
		date_modified: '',
		date_modified_gmt: '',
	} );

	describe( 'Image Rendering', () => {
		it( 'should render existing images with thumbnails', () => {
			const data = {
				images: [ createMockImage( 1 ), createMockImage( 2 ) ],
			} as ProductEntityRecord;

			const Edit = fieldExtensions.Edit as React.ComponentType< any >;
			render( <Edit data={ data } onChange={ mockOnChange } /> );

			const images = screen.getAllByRole( 'img' );
			expect( images ).toHaveLength( 2 );
			expect( images[ 0 ] ).toHaveAttribute( 'src', 'thumb-1.jpg' );
			expect( images[ 1 ] ).toHaveAttribute( 'src', 'thumb-2.jpg' );
		} );

		it( 'should fallback to src when thumbnail is not available', () => {
			const data = {
				images: [
					{ ...createMockImage( 1 ), thumbnail: '' },
					createMockImage( 2 ),
				],
			} as ProductEntityRecord;

			const Edit = fieldExtensions.Edit as React.ComponentType< any >;
			render( <Edit data={ data } onChange={ mockOnChange } /> );

			const images = screen.getAllByRole( 'img' );
			expect( images[ 0 ] ).toHaveAttribute( 'src', 'image-1.jpg' );
			expect( images[ 1 ] ).toHaveAttribute( 'src', 'thumb-2.jpg' );
		} );

		it( 'should render remove button for each image', () => {
			const data = {
				images: [ createMockImage( 1 ), createMockImage( 2 ) ],
			} as ProductEntityRecord;

			const Edit = fieldExtensions.Edit as React.ComponentType< any >;
			render( <Edit data={ data } onChange={ mockOnChange } /> );

			const removeButtons = screen.getAllByLabelText( 'Remove image' );
			expect( removeButtons ).toHaveLength( 2 );
		} );

		it( 'should not render drag handle for single image', () => {
			const data = {
				images: [ createMockImage( 1 ) ],
			} as ProductEntityRecord;

			const Edit = fieldExtensions.Edit as React.ComponentType< any >;
			render( <Edit data={ data } onChange={ mockOnChange } /> );

			expect(
				screen.queryByLabelText( 'Drag to reorder' )
			).not.toBeInTheDocument();
		} );

		it( 'should render drag handles for multiple images', () => {
			const data = {
				images: [ createMockImage( 1 ), createMockImage( 2 ) ],
			} as ProductEntityRecord;

			const Edit = fieldExtensions.Edit as React.ComponentType< any >;
			render( <Edit data={ data } onChange={ mockOnChange } /> );

			expect(
				screen.getAllByLabelText( 'Drag to reorder' )
			).toHaveLength( 2 );
		} );
	} );

	describe( 'Image Removal', () => {
		it( 'should remove image by ID', () => {
			const data = {
				images: [
					createMockImage( 1 ),
					createMockImage( 2 ),
					createMockImage( 3 ),
				],
			} as ProductEntityRecord;

			const Edit = fieldExtensions.Edit as React.ComponentType< any >;
			render( <Edit data={ data } onChange={ mockOnChange } /> );

			const removeButtons = screen.getAllByLabelText( 'Remove image' );
			fireEvent.click( removeButtons[ 1 ] ); // Remove image with ID 2

			expect( mockOnChange ).toHaveBeenCalledWith( {
				images: [ createMockImage( 1 ), createMockImage( 3 ) ],
			} );
		} );

		it( 'should preserve order after removal', () => {
			const data = {
				images: [
					createMockImage( 1 ),
					createMockImage( 2 ),
					createMockImage( 3 ),
					createMockImage( 4 ),
				],
			} as ProductEntityRecord;

			const Edit = fieldExtensions.Edit as React.ComponentType< any >;
			render( <Edit data={ data } onChange={ mockOnChange } /> );

			const removeButtons = screen.getAllByLabelText( 'Remove image' );
			fireEvent.click( removeButtons[ 0 ] ); // Remove first image (ID 1)

			expect( mockOnChange ).toHaveBeenCalledWith( {
				images: [
					createMockImage( 2 ),
					createMockImage( 3 ),
					createMockImage( 4 ),
				],
			} );
		} );
	} );
} );

describe( 'Product Images Field - Image Selection Logic', () => {
	const mockOnChange = jest.fn();

	beforeEach( () => {
		jest.clearAllMocks();
		mockOnSelectCallback = null;
	} );

	const createMockImage = (
		id: number,
		src = `image-${ id }.jpg`,
		thumbnail = `thumb-${ id }.jpg`
	) => ( {
		id,
		src,
		alt: `Alt ${ id }`,
		name: `Image ${ id }`,
		thumbnail,
		date_created: '',
		date_created_gmt: '',
		date_modified: '',
		date_modified_gmt: '',
	} );

	const createMockAttachment = ( id: number ) => ( {
		id,
		url: `full-${ id }.jpg`,
		alt: `Alt ${ id }`,
		title: `Image ${ id }`,
		media_details: {
			sizes: {
				woocommerce_thumbnail: {
					source_url: `thumb-${ id }.jpg`,
				},
			},
		},
	} );

	it( 'should handle empty initial state', () => {
		const data = { images: [] } as Partial< ProductEntityRecord >;
		const Edit = fieldExtensions.Edit as React.ComponentType< any >;

		render( <Edit data={ data } onChange={ mockOnChange } /> );

		const uploadButton = screen.getByLabelText( 'Add images' );
		expect( uploadButton ).toBeInTheDocument();
	} );

	it( 'should append newly selected images', () => {
		const data = {
			images: [ createMockImage( 1 ) ],
		} as ProductEntityRecord;
		const Edit = fieldExtensions.Edit as React.ComponentType< any >;

		render( <Edit data={ data } onChange={ mockOnChange } /> );

		// Open modal to capture onSelect callback
		const uploadButton = screen.getByLabelText( 'Add images' );
		fireEvent.click( uploadButton );

		// Simulate selecting images including existing 1 plus new 2 and 3
		act( () => {
			mockOnSelectCallback?.( [
				createMockAttachment( 1 ),
				createMockAttachment( 2 ),
				createMockAttachment( 3 ),
			] );
		} );

		expect( mockOnChange ).toHaveBeenCalledWith( {
			images: [
				createMockImage( 1 ),
				{
					id: 2,
					src: 'full-2.jpg',
					alt: 'Alt 2',
					name: 'Image 2',
					thumbnail: 'thumb-2.jpg',
					date_created: '',
					date_created_gmt: '',
					date_modified: '',
					date_modified_gmt: '',
				},
				{
					id: 3,
					src: 'full-3.jpg',
					alt: 'Alt 3',
					name: 'Image 3',
					thumbnail: 'thumb-3.jpg',
					date_created: '',
					date_created_gmt: '',
					date_modified: '',
					date_modified_gmt: '',
				},
			],
		} );
	} );

	it( 'should preserve existing order when adding new images', () => {
		const data = {
			images: [ createMockImage( 1 ), createMockImage( 2 ) ],
		} as ProductEntityRecord;
		const Edit = fieldExtensions.Edit as React.ComponentType< any >;

		render( <Edit data={ data } onChange={ mockOnChange } /> );

		// Open modal to capture onSelect callback
		const uploadButton = screen.getByLabelText( 'Add images' );
		fireEvent.click( uploadButton );

		// Simulate selecting [4, 3, 1, 2] in modal (modal order)
		act( () => {
			mockOnSelectCallback?.( [
				createMockAttachment( 4 ),
				createMockAttachment( 3 ),
				createMockAttachment( 1 ),
				createMockAttachment( 2 ),
			] );
		} );

		// Should preserve [1, 2] order and append only new [4, 3] in that order
		expect( mockOnChange ).toHaveBeenCalledWith( {
			images: [
				createMockImage( 1 ),
				createMockImage( 2 ),
				{
					id: 4,
					src: 'full-4.jpg',
					alt: 'Alt 4',
					name: 'Image 4',
					thumbnail: 'thumb-4.jpg',
					date_created: '',
					date_created_gmt: '',
					date_modified: '',
					date_modified_gmt: '',
				},
				{
					id: 3,
					src: 'full-3.jpg',
					alt: 'Alt 3',
					name: 'Image 3',
					thumbnail: 'thumb-3.jpg',
					date_created: '',
					date_created_gmt: '',
					date_modified: '',
					date_modified_gmt: '',
				},
			],
		} );
	} );

	it( 'should remove deselected images', () => {
		const data = {
			images: [
				createMockImage( 1 ),
				createMockImage( 2 ),
				createMockImage( 3 ),
			],
		} as ProductEntityRecord;
		const Edit = fieldExtensions.Edit as React.ComponentType< any >;

		render( <Edit data={ data } onChange={ mockOnChange } /> );

		// Open modal to capture onSelect callback
		const uploadButton = screen.getByLabelText( 'Add images' );
		fireEvent.click( uploadButton );

		// Simulate unselecting image 2 (only 1 and 3 selected)
		act( () => {
			mockOnSelectCallback?.( [
				createMockAttachment( 1 ),
				createMockAttachment( 3 ),
			] );
		} );

		// Should remove image 2
		expect( mockOnChange ).toHaveBeenCalledWith( {
			images: [ createMockImage( 1 ), createMockImage( 3 ) ],
		} );
	} );

	it( 'should handle mixed selection and deselection', () => {
		const data = {
			images: [
				createMockImage( 1 ),
				createMockImage( 2 ),
				createMockImage( 3 ),
			],
		} as ProductEntityRecord;
		const Edit = fieldExtensions.Edit as React.ComponentType< any >;

		render( <Edit data={ data } onChange={ mockOnChange } /> );

		// Open modal to capture onSelect callback
		const uploadButton = screen.getByLabelText( 'Add images' );
		fireEvent.click( uploadButton );

		// Simulate: unselect 2, keep 1 and 3, add 4
		act( () => {
			mockOnSelectCallback?.( [
				createMockAttachment( 4 ),
				createMockAttachment( 1 ),
				createMockAttachment( 3 ),
			] );
		} );

		// Should preserve [1, 3] order and append [4], removing [2]
		expect( mockOnChange ).toHaveBeenCalledWith( {
			images: [
				createMockImage( 1 ),
				createMockImage( 3 ),
				{
					id: 4,
					src: 'full-4.jpg',
					alt: 'Alt 4',
					name: 'Image 4',
					thumbnail: 'thumb-4.jpg',
					date_created: '',
					date_created_gmt: '',
					date_modified: '',
					date_modified_gmt: '',
				},
			],
		} );
	} );

	it( 'should handle deselecting all images', () => {
		const data = {
			images: [
				createMockImage( 1 ),
				createMockImage( 2 ),
				createMockImage( 3 ),
			],
		} as ProductEntityRecord;
		const Edit = fieldExtensions.Edit as React.ComponentType< any >;

		render( <Edit data={ data } onChange={ mockOnChange } /> );

		// Open modal to capture onSelect callback
		const uploadButton = screen.getByLabelText( 'Add images' );
		fireEvent.click( uploadButton );

		// Simulate deselecting all
		act( () => {
			mockOnSelectCallback?.( [] );
		} );

		expect( mockOnChange ).toHaveBeenCalledWith( {
			images: [],
		} );
	} );
} );

describe( 'Product Images Field - Drag and Drop Reorder', () => {
	const mockOnChange = jest.fn();

	beforeEach( () => {
		jest.clearAllMocks();
	} );

	const createMockImage = (
		id: number,
		src = `image-${ id }.jpg`,
		thumbnail = `thumb-${ id }.jpg`
	) => ( {
		id,
		src,
		alt: `Alt ${ id }`,
		name: `Image ${ id }`,
		thumbnail,
		date_created: '',
		date_created_gmt: '',
		date_modified: '',
		date_modified_gmt: '',
	} );

	const renderField = ( images: ProductEntityRecord[ 'images' ] ) => {
		const Edit = fieldExtensions.Edit as React.ComponentType< any >;
		render(
			<Edit
				data={ { images } as ProductEntityRecord }
				onChange={ mockOnChange }
			/>
		);
	};

	it( 'reorders images when drag ends over a different target', () => {
		const images = [
			createMockImage( 1 ),
			createMockImage( 2 ),
			createMockImage( 3 ),
		];
		renderField( images );

		fireEvent.dragStart(
			screen.getAllByLabelText( 'Drag to reorder' )[ 0 ]
		);
		fireEvent.dragOver( screen.getAllByRole( 'group' )[ 2 ] );
		fireEvent.drop( screen.getAllByRole( 'group' )[ 2 ] );

		expect( mockOnChange ).toHaveBeenCalledWith( {
			images: [ images[ 1 ], images[ 2 ], images[ 0 ] ],
		} );
	} );

	it( 'does nothing when dropping on the same item', () => {
		const images = [
			createMockImage( 1 ),
			createMockImage( 2 ),
			createMockImage( 3 ),
		];
		renderField( images );

		fireEvent.dragStart(
			screen.getAllByLabelText( 'Drag to reorder' )[ 1 ]
		);
		fireEvent.dragOver( screen.getAllByRole( 'group' )[ 1 ] );
		fireEvent.drop( screen.getAllByRole( 'group' )[ 1 ] );

		expect( mockOnChange ).not.toHaveBeenCalled();
	} );

	it( 'ignores drag end events without a valid target', () => {
		const images = [
			createMockImage( 1 ),
			createMockImage( 2 ),
			createMockImage( 3 ),
		];
		renderField( images );

		fireEvent.dragStart(
			screen.getAllByLabelText( 'Drag to reorder' )[ 1 ]
		);
		fireEvent.dragEnd( screen.getAllByLabelText( 'Drag to reorder' )[ 1 ] );

		expect( mockOnChange ).not.toHaveBeenCalled();
	} );
} );
/* eslint-enable @typescript-eslint/no-explicit-any */
