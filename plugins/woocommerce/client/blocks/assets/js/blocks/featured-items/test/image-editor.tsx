/* eslint-disable @wordpress/no-unsafe-wp-apis */

/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import type { ReactNode } from 'react';
import {
	__experimentalImageEditor as GutenbergImageEditor,
	__experimentalImageEditingProvider as LegacyImageEditingProvider,
} from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { ImageEditor } from '../image-editor';

jest.mock( '@wordpress/block-editor', () => ( {
	...jest.requireActual( '@wordpress/block-editor' ),
	__experimentalImageEditor: jest.fn( () => null ),
	__experimentalImageEditingProvider: jest.fn(
		( { children }: { children: ReactNode } ) => children
	),
} ) );

const mockGutenbergImageEditor = GutenbergImageEditor as jest.Mock;
const mockLegacyImageEditingProvider = LegacyImageEditingProvider as jest.Mock;

describe( 'Featured Items image editor', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'passes the complete editing contract to the merged image editor', () => {
		const setAttributes = jest.fn();
		const setIsEditingImage = jest.fn();
		const container = document.createElement( 'div' );

		render(
			<ImageEditor
				align="center"
				backgroundImageId={ 42 }
				backgroundImageSize={ { height: 640, width: 960 } }
				backgroundImageSrc="https://example.com/product.jpg"
				containerRef={ { current: container } }
				setAttributes={ setAttributes }
				setIsEditingImage={ setIsEditingImage }
			/>
		);

		expect( mockLegacyImageEditingProvider ).not.toHaveBeenCalled();
		expect( mockGutenbergImageEditor ).toHaveBeenCalled();
		const editorProps = mockGutenbergImageEditor.mock.lastCall?.[ 0 ];
		expect( editorProps ).toEqual(
			expect.objectContaining( {
				id: 42,
				url: 'https://example.com/product.jpg',
				height: 640,
				width: 960,
				naturalHeight: 640,
				naturalWidth: 960,
				onSaveImage: expect.any( Function ),
				onFinishEditing: expect.any( Function ),
			} )
		);

		editorProps.onSaveImage( {
			id: 84,
			url: 'https://example.com/product-edited.jpg',
		} );
		editorProps.onFinishEditing();

		expect( setAttributes ).toHaveBeenCalledWith( {
			mediaId: 84,
			mediaSrc: 'https://example.com/product-edited.jpg',
		} );
		expect( setIsEditingImage ).toHaveBeenCalledWith( false );
	} );

	it( 'uses the editor fallback for missing natural image dimensions', () => {
		render(
			<ImageEditor
				align="center"
				backgroundImageId={ 42 }
				backgroundImageSize={ { height: 0, width: 0 } }
				backgroundImageSrc="https://example.com/product.jpg"
				containerRef={ {
					current: document.createElement( 'div' ),
				} }
				setAttributes={ jest.fn() }
				setIsEditingImage={ jest.fn() }
			/>
		);

		const editorProps = mockGutenbergImageEditor.mock.lastCall?.[ 0 ];
		expect( editorProps ).toEqual(
			expect.objectContaining( {
				height: 500,
				width: 500,
				naturalHeight: 500,
				naturalWidth: 500,
			} )
		);
	} );
} );
