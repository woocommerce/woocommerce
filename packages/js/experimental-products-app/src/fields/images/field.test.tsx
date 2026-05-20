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
		} as ProductEntityRecord );

	afterEach( () => {
		jest.clearAllMocks();
	} );

	it( 'replaces the current images with the selected media attachments', () => {
		if ( ! fieldExtensions.Edit ) {
			throw new Error( 'images edit not implemented' );
		}

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
		const Edit = fieldExtensions.Edit as React.ComponentType<
			DataFormControlProps< ProductEntityRecord >
		>;

		render(
			<Edit
				data={ buildProduct( {
					images: [
						{
							id: 15,
							src: 'old-image.jpg',
							alt: 'Old image',
						} as ProductEntityRecord[ 'images' ][ number ],
					],
				} ) }
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
} );
