/**
 * External dependencies
 */
import { WP_REST_API_Category } from 'wp-types';
import { ProductResponseItem } from '@woocommerce/types';
import {
	getImageSrcFromProduct,
	getImageIdFromProduct,
} from '@woocommerce/utils';
import { useEffect, useState, useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { BLOCK_NAMES } from './constants';
import {
	getCategoryImageSrc,
	getCategoryImageId,
} from './featured-category/utils';

interface BackgroundProps {
	blockName: string;
	item: ProductResponseItem | WP_REST_API_Category;
	mediaId: number | undefined;
	mediaSrc: string | undefined;
}

export interface BgImageDimensions {
	height: number;
	width: number;
}

interface BackgroundImage {
	backgroundImageId: number;
	backgroundImageSrc: string;
	isImageBgTransparent: boolean;
	originalImgDimension: BgImageDimensions;
}

export function useBackgroundImage( {
	blockName,
	item,
	mediaId,
	mediaSrc,
}: BackgroundProps ): BackgroundImage {
	const [ backgroundImageId, setBackgroundImageId ] = useState( 0 );
	const [ backgroundImageSrc, setBackgroundImageSrc ] = useState( '' );
	const [ isImageBgTransparent, setIsImageBgTransparent ] =
		useState< boolean >( false );
	const [ originalImgDimension, setOriginalImgDimension ] =
		useState< BgImageDimensions >( { height: 0, width: 0 } );
	const shadowImgRef = useRef< HTMLImageElement | null >( null );
	const shadowCanvasRef = useRef< HTMLCanvasElement | null >( null );

	useEffect( () => {
		if ( mediaId ) {
			setBackgroundImageId( mediaId );
		} else {
			setBackgroundImageId(
				blockName === BLOCK_NAMES.featuredProduct
					? getImageIdFromProduct( item as ProductResponseItem )
					: getCategoryImageId( item as WP_REST_API_Category )
			);
		}
	}, [ blockName, item, mediaId ] );

	useEffect( () => {
		if ( mediaSrc ) {
			setBackgroundImageSrc( mediaSrc );
		} else {
			setBackgroundImageSrc(
				blockName === BLOCK_NAMES.featuredProduct
					? getImageSrcFromProduct( item as ProductResponseItem )
					: getCategoryImageSrc( item as WP_REST_API_Category )
			);
		}
	}, [ blockName, item, mediaSrc ] );

	useEffect( () => {
		if ( backgroundImageSrc ) {
			if ( ! shadowImgRef.current ) {
				shadowImgRef.current = new Image();
			}

			if ( ! shadowCanvasRef.current ) {
				shadowCanvasRef.current = document.createElement( 'canvas' );
			}

			const img = shadowImgRef.current;
			const canvas = shadowCanvasRef.current;

			img.src = backgroundImageSrc;
			img.onload = () => {
				const width = img.naturalWidth;
				const height = img.naturalHeight;

				if ( height !== null && width !== null ) {
					setOriginalImgDimension( {
						height,
						width,
					} );
				}

				canvas.width = width;
				canvas.height = height;

				// Draw the image on the canvas element.
				const ctx = canvas.getContext( '2d', {
					willReadFrequently: true,
				} );

				if ( ! ctx ) return;

				ctx.drawImage( img, 0, 0, width, height );

				const imagePixelData = ctx.getImageData(
					0,
					0,
					width,
					height
				).data;

				// Check for transparency (alpha channel < 255).
				const hasTransparentPixels = ( () => {
					for ( let i = 3; i < imagePixelData.length; i += 4 ) {
						if ( imagePixelData[ i ] < 255 ) {
							return true;
						}
					}
					return false;
				} )();

				if ( isImageBgTransparent !== hasTransparentPixels ) {
					setIsImageBgTransparent( hasTransparentPixels );
				}
			};
		} else {
			setIsImageBgTransparent( true );
		}

		return () => {
			if ( shadowImgRef.current ) {
				shadowImgRef.current.onload = null; // Clean up image onload event on unmount.
			}
		};
	}, [ backgroundImageSrc ] );

	return {
		backgroundImageId,
		backgroundImageSrc,
		isImageBgTransparent,
		originalImgDimension,
	};
}
