/**
 * External dependencies
 */
import {
	getImageSrcFromProduct,
	getImageIdFromProduct,
} from '@woocommerce/utils';
import { useEffect, useState } from 'react';

/**
 * Internal dependencies
 */
import { BLOCK_NAMES } from './constants';
import {
	getCategoryImageSrc,
	getCategoryImageId,
} from './featured-category/utils';

export function useBackgroundImage( { blockName, item, mediaId, mediaSrc } ) {
	const [ backgroundImageId, setBackgroundImageId ] = useState( 0 );
	const [ backgroundImageSrc, setBackgroundImageSrc ] = useState( '' );

	useEffect( () => {
		if ( mediaId ) {
			setBackgroundImageId( mediaId );
		} else {
			setBackgroundImageId(
				blockName === BLOCK_NAMES.featuredProduct
					? getImageIdFromProduct( item )
					: getCategoryImageId( item )
			);
		}
	}, [ blockName, item, mediaId ] );

	useEffect( () => {
		if ( mediaSrc ) {
			setBackgroundImageSrc( mediaSrc );
		} else {
			setBackgroundImageSrc(
				blockName === BLOCK_NAMES.featuredProduct
					? getImageSrcFromProduct( item )
					: getCategoryImageSrc( item )
			);
		}
	}, [ blockName, item, mediaSrc ] );

	return { backgroundImageId, backgroundImageSrc };
}
