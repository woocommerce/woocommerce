/**
 * External dependencies
 */
import { act, fireEvent, render, screen } from '@testing-library/react';
import type { DataFormControlProps } from '@wordpress/dataviews';
import React from 'react';

type MockMediaUploadProps = {
	allowedTypes?: string[];
	multiple?: boolean | string;
	onSelect: ( attachments: unknown ) => void;
	render: ( args: { open: () => void } ) => React.ReactNode;
	title?: string;
	value?: number[];
};

const mockOpenMediaUploadModal = jest.fn();
const mockMediaUpload = jest.fn( ( props: MockMediaUploadProps ) =>
	props.render( { open: mockOpenMediaUploadModal } )
);

jest.mock( '@wordpress/media-utils', () => ( {
	MediaUpload: ( props: MockMediaUploadProps ) => mockMediaUpload( props ),
} ) );

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../types';

import { fieldExtensions } from './field';

jest.mock( '@dnd-kit/react', () => ( {
	DragDropProvider: ( { children }: { children: React.ReactNode } ) =>
		children,
} ) );

jest.mock( '@dnd-kit/react/sortable', () => ( {
	isSortable: () => false,
	useSortable: () => ( {
		ref: () => undefined,
		handleRef: () => undefined,
		isDragging: false,
	} ),
} ) );

describe( 'images field', () => {
	const buildProduct = (
		overrides: Partial< ProductEntityRecord > = {}
	): ProductEntityRecord =>
		( {
			id: 12,
			name: 'Beanie',
			images: [],
			...overrides,
		} ) as ProductEntityRecord;

	const renderImagesEdit = (
		data: ProductEntityRecord,
		onChange = jest.fn()
	) => {
		if ( ! fieldExtensions.Edit ) {
			throw new Error( 'images edit not implemented' );
		}

		const Edit = fieldExtensions.Edit as React.ComponentType<
			DataFormControlProps< ProductEntityRecord >
		>;

		render(
			<Edit
				data={ data }
				field={
					{
						...fieldExtensions,
						id: 'images',
						label: 'Images',
					} as DataFormControlProps< ProductEntityRecord >[ 'field' ]
				}
				onChange={ onChange }
			/>
		);

		return onChange;
	};

	afterEach( () => {
		jest.clearAllMocks();
	} );

	it( 'replaces the current images with the selected media attachments', () => {
		const attachments = [
			{
				id: 34,
				url: 'new-image.jpg',
				alt: 'New image',
				title: 'New image title',
				sizes: {
					thumbnail: {
						url: 'new-image-thumbnail.jpg',
					},
				},
			},
		];
		const onChange = jest.fn();

		renderImagesEdit(
			buildProduct( {
				images: [
					{
						id: 15,
						src: 'old-image.jpg',
						alt: 'Old image',
					} as ProductEntityRecord[ 'images' ][ number ],
				],
			} ),
			onChange
		);

		expect( mockMediaUpload ).toHaveBeenCalledWith(
			expect.objectContaining( {
				allowedTypes: [ 'image' ],
				multiple: 'add',
				title: 'Add images',
				value: [ 15 ],
			} )
		);

		fireEvent.click(
			screen.getByRole( 'button', {
				name: 'Add images',
			} )
		);
		expect( mockOpenMediaUploadModal ).toHaveBeenCalled();

		act( () => {
			mockMediaUpload.mock.calls[ 0 ][ 0 ].onSelect( attachments );
		} );

		expect(
			screen.getByRole( 'img', {
				name: 'New image',
			} )
		).toBeInTheDocument();
		expect(
			screen.queryByRole( 'img', {
				name: 'Old image',
			} )
		).not.toBeInTheDocument();
		expect( onChange ).toHaveBeenCalledWith( {
			images: [
				expect.objectContaining( {
					id: 34,
					src: 'new-image.jpg',
					alt: 'New image',
					name: 'New image title',
					thumbnail: 'new-image-thumbnail.jpg',
				} ),
			],
		} );
	} );

	it( 'limits variations to a single selected image', () => {
		const attachments = [
			{
				id: 34,
				url: 'new-variation-image.jpg',
				alt: 'New variation image',
				title: 'New variation image title',
				sizes: {
					thumbnail: {
						url: 'new-variation-image-thumbnail.jpg',
					},
				},
			},
			{
				id: 35,
				url: 'extra-variation-image.jpg',
				alt: 'Extra variation image',
				title: 'Extra variation image title',
			},
		];
		const onChange = jest.fn();

		renderImagesEdit(
			buildProduct( {
				type: 'variation',
				images: [
					{
						id: 15,
						src: 'old-variation-image.jpg',
						alt: 'Old variation image',
					} as ProductEntityRecord[ 'images' ][ number ],
					{
						id: 16,
						src: 'second-variation-image.jpg',
						alt: 'Second variation image',
					} as ProductEntityRecord[ 'images' ][ number ],
				],
			} ),
			onChange
		);

		expect( mockMediaUpload ).toHaveBeenCalledWith(
			expect.objectContaining( {
				allowedTypes: [ 'image' ],
				multiple: false,
				title: 'Add image',
				value: [ 15 ],
			} )
		);

		expect(
			screen.getByRole( 'img', {
				name: 'Old variation image',
			} )
		).toBeInTheDocument();
		expect(
			screen.queryByRole( 'img', {
				name: 'Second variation image',
			} )
		).not.toBeInTheDocument();
		expect(
			screen.queryByRole( 'button', {
				name: 'Drag to reorder',
			} )
		).not.toBeInTheDocument();

		fireEvent.click(
			screen.getByRole( 'button', {
				name: 'Add image',
			} )
		);
		expect( mockOpenMediaUploadModal ).toHaveBeenCalled();

		act( () => {
			mockMediaUpload.mock.calls[ 0 ][ 0 ].onSelect( attachments );
		} );

		expect(
			screen.getByRole( 'img', {
				name: 'New variation image',
			} )
		).toBeInTheDocument();
		expect(
			screen.queryByRole( 'img', {
				name: 'Extra variation image',
			} )
		).not.toBeInTheDocument();
		expect( onChange ).toHaveBeenCalledWith( {
			images: [
				expect.objectContaining( {
					id: 34,
					src: 'new-variation-image.jpg',
					alt: 'New variation image',
					name: 'New variation image title',
					thumbnail: 'new-variation-image-thumbnail.jpg',
				} ),
			],
		} );
	} );
} );
