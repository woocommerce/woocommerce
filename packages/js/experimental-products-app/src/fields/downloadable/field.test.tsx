/**
 * External dependencies
 */
import { act, fireEvent, render, screen } from '@testing-library/react';
import type { DataFormControlProps } from '@wordpress/dataviews';
import React from 'react';

const mockUploadMedia = jest.fn();

jest.mock( '@wordpress/components', () => {
	const ReactActual = jest.requireActual( 'react' );

	return {
		FormFileUpload: ( {
			children,
			className,
			onChange,
		}: {
			children?: React.ReactNode;
			className?: string;
			onChange?: React.ChangeEventHandler< HTMLInputElement >;
		} ) =>
			ReactActual.createElement(
				'label',
				null,
				ReactActual.createElement( 'input', {
					className,
					onChange,
					type: 'file',
				} ),
				children
			),
		Button: ( {
			children,
			onClick,
			'aria-label': ariaLabel,
		}: {
			children?: React.ReactNode;
			onClick?: React.MouseEventHandler< HTMLButtonElement >;
			'aria-label'?: string;
		} ) =>
			ReactActual.createElement(
				'button',
				{
					'aria-label': ariaLabel,
					onClick,
					type: 'button',
				},
				children
			),
	};
} );

jest.mock( '@wordpress/media-utils', () => ( {
	uploadMedia: ( args: unknown ) => mockUploadMedia( args ),
} ) );

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../types';

import { fieldExtensions } from './field';

describe( 'downloadable field', () => {
	const buildProduct = (
		overrides: Partial< ProductEntityRecord > = {}
	): ProductEntityRecord =>
		( {
			id: 12,
			name: 'Beanie',
			downloadable: true,
			downloads: [],
			...overrides,
		} ) as ProductEntityRecord;

	const renderEdit = ( data: ProductEntityRecord, onChange = jest.fn() ) => {
		if ( ! fieldExtensions.Edit ) {
			throw new Error( 'downloadable edit not implemented' );
		}

		const Edit = fieldExtensions.Edit as React.ComponentType<
			DataFormControlProps< ProductEntityRecord >
		>;

		const view = render(
			<Edit
				data={ data }
				field={
					{
						...fieldExtensions,
						id: 'downloadable',
						label: 'Downloadable',
					} as DataFormControlProps< ProductEntityRecord >[ 'field' ]
				}
				onChange={ onChange }
			/>
		);

		return {
			...view,
			onChange,
		};
	};

	beforeEach( () => {
		jest.clearAllMocks();
		Object.defineProperty( URL, 'createObjectURL', {
			writable: true,
			value: jest.fn( () => 'blob:download-file' ),
		} );
		Object.defineProperty( URL, 'revokeObjectURL', {
			writable: true,
			value: jest.fn(),
		} );
	} );

	it( 'uploads selected files to the media library before saving them as downloads', () => {
		const { container, onChange } = renderEdit( buildProduct() );
		const file = new File( [ 'manual' ], 'manual.pdf', {
			type: 'application/pdf',
		} );
		const fileInput = container.querySelector(
			'input[type="file"]'
		) as HTMLInputElement;

		fireEvent.change( fileInput, {
			target: {
				files: [ file ],
			},
		} );

		expect( screen.getByText( /manual\.pdf/ ) ).toHaveTextContent(
			'manual.pdf - uploading…'
		);
		expect( onChange ).not.toHaveBeenCalled();
		expect( mockUploadMedia ).toHaveBeenCalledWith(
			expect.objectContaining( {
				filesList: [ file ],
				onFileChange: expect.any( Function ),
				onError: expect.any( Function ),
			} )
		);

		act( () => {
			mockUploadMedia.mock.calls[ 0 ][ 0 ].onFileChange( [
				{
					id: 34,
					url: 'https://example.com/wp-content/uploads/manual.pdf',
					title: 'Product manual',
				},
			] );
		} );

		expect( URL.revokeObjectURL ).toHaveBeenCalledWith(
			'blob:download-file'
		);
		expect( onChange ).toHaveBeenCalledTimes( 1 );
		expect( onChange ).toHaveBeenCalledWith( {
			downloads: [
				{
					id: '34',
					file: 'https://example.com/wp-content/uploads/manual.pdf',
					name: 'Product manual',
				},
			],
		} );
		expect( screen.getByText( 'Product manual' ) ).toBeInTheDocument();
	} );

	it( 'removes the temporary download when upload fails', () => {
		const { container, onChange } = renderEdit( buildProduct() );
		const file = new File( [ 'manual' ], 'manual.pdf', {
			type: 'application/pdf',
		} );
		const fileInput = container.querySelector(
			'input[type="file"]'
		) as HTMLInputElement;

		fireEvent.change( fileInput, {
			target: {
				files: [ file ],
			},
		} );

		act( () => {
			mockUploadMedia.mock.calls[ 0 ][ 0 ].onError();
		} );

		expect( URL.revokeObjectURL ).toHaveBeenCalledWith(
			'blob:download-file'
		);
		expect( onChange ).not.toHaveBeenCalled();
		expect( screen.queryByText( /manual\.pdf/ ) ).not.toBeInTheDocument();
	} );

	it.each( [
		[ 'missing media ID', { url: 'https://example.com/manual.pdf' } ],
		[ 'missing media URL', { id: 34 } ],
	] )(
		'removes the temporary download when uploaded attachment has a %s',
		( _name, attachment ) => {
			const { container, onChange } = renderEdit( buildProduct() );
			const file = new File( [ 'manual' ], 'manual.pdf', {
				type: 'application/pdf',
			} );
			const fileInput = container.querySelector(
				'input[type="file"]'
			) as HTMLInputElement;

			fireEvent.change( fileInput, {
				target: {
					files: [ file ],
				},
			} );

			act( () => {
				mockUploadMedia.mock.calls[ 0 ][ 0 ].onFileChange( [
					attachment,
				] );
			} );

			expect( URL.revokeObjectURL ).toHaveBeenCalledWith(
				'blob:download-file'
			);
			expect( onChange ).not.toHaveBeenCalled();
			expect(
				screen.queryByText( /manual\.pdf/ )
			).not.toBeInTheDocument();
		}
	);
} );
