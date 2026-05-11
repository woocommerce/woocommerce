/**
 * External dependencies
 */
import { act, fireEvent, render, screen } from '@testing-library/react';
import type { DataFormControlProps } from '@wordpress/dataviews';
import React from 'react';

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
		delete ( window as unknown as { wp?: unknown } ).wp;
		jest.clearAllMocks();
	} );

	it( 'replaces the current images with the selected media attachments', () => {
		if ( ! fieldExtensions.Edit ) {
			throw new Error( 'images edit not implemented' );
		}

		let openCallback: () => void = () => undefined;
		let selectCallback: () => void = () => undefined;
		const attachments = [
			{
				id: 34,
				url: 'new-image.jpg',
				alt: 'New image',
				title: 'New image title',
				media_details: {
					sizes: {
						thumbnail: {
							source_url: 'new-image-thumbnail.jpg',
						},
					},
				},
			},
		];
		const selectionAdd = jest.fn();
		const frame = {
			on: jest.fn( ( event: 'open' | 'select', callback: () => void ) => {
				if ( event === 'open' ) {
					openCallback = callback;
				}

				if ( event === 'select' ) {
					selectCallback = callback;
				}
			} ),
			open: jest.fn( () => {
				openCallback();
			} ),
			state: () => ( {
				get: () => ( {
					add: selectionAdd,
					map: (
						callback: ( attachment: {
							toJSON: () => ( typeof attachments )[ number ];
						} ) => ( typeof attachments )[ number ]
					) =>
						attachments.map( ( attachment ) =>
							callback( {
								toJSON: () => attachment,
							} )
						),
				} ),
			} ),
		};
		const onChange = jest.fn();
		const query = jest.fn( ( options ) => ( {
			queryOptions: options,
		} ) );
		const Library = jest.fn( function Library(
			this: { options: Record< string, unknown > },
			options: Record< string, unknown >
		) {
			this.options = options;
		} );
		const selectedAttachment = {
			fetch: jest.fn(),
			toJSON: jest.fn(),
		};
		const attachment = jest.fn( () => selectedAttachment );
		const media = jest.fn( () => frame );
		const Edit = fieldExtensions.Edit as React.ComponentType<
			DataFormControlProps< ProductEntityRecord >
		>;

		( window as unknown as { wp: { media: typeof media } } ).wp = {
			media: Object.assign( media, {
				controller: {
					Library,
				},
				attachment,
				query,
			} ),
		};

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

		fireEvent.click(
			screen.getByRole( 'button', {
				name: 'Add images',
			} )
		);
		act( () => {
			selectCallback();
		} );

		expect( query ).toHaveBeenCalledWith( {
			type: 'image',
		} );
		expect( Library ).toHaveBeenCalledWith( {
			title: 'Add images',
			library: {
				queryOptions: {
					type: 'image',
				},
			},
			multiple: 'add',
			filterable: 'all',
			syncSelection: false,
		} );
		expect( media ).toHaveBeenCalledWith(
			expect.objectContaining( {
				multiple: 'add',
			} )
		);
		expect( attachment ).toHaveBeenCalledWith( 15 );
		expect( selectedAttachment.fetch ).toHaveBeenCalled();
		expect( selectionAdd ).toHaveBeenCalledWith( selectedAttachment );
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
