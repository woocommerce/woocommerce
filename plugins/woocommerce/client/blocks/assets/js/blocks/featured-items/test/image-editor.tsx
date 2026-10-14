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
				originalImgDimension={ { height: 100, width: 200 } }
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

	// Repeated and parallax backgrounds render a <div>, never an <img>, so the
	// measured size stays empty for as long as the block keeps that setting.
	it( 'falls back to the off-screen measurement when no image was measured', () => {
		render(
			<ImageEditor
				align="center"
				backgroundImageId={ 42 }
				backgroundImageSize={ {} }
				backgroundImageSrc="https://example.com/product.jpg"
				containerRef={ {
					current: document.createElement( 'div' ),
				} }
				originalImgDimension={ { height: 640, width: 960 } }
				setAttributes={ jest.fn() }
				setIsEditingImage={ jest.fn() }
			/>
		);

		const editorProps = mockGutenbergImageEditor.mock.lastCall?.[ 0 ];
		expect( editorProps ).toEqual(
			expect.objectContaining( {
				height: 640,
				width: 960,
				naturalHeight: 640,
				naturalWidth: 960,
			} )
		);
	} );

	it( 'uses the editor default only when no size has been measured yet', () => {
		render(
			<ImageEditor
				align="center"
				backgroundImageId={ 42 }
				backgroundImageSize={ { height: 0, width: 0 } }
				backgroundImageSrc="https://example.com/product.jpg"
				containerRef={ {
					current: document.createElement( 'div' ),
				} }
				originalImgDimension={ { height: 0, width: 0 } }
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
