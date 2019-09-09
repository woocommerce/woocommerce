/**
 * Internal dependencies
 */
import './editor.scss';
import { getBlockClassName } from './utils.js';

export default ( { attributes } ) => {
	const {
		categoryIds,
		imageType,
		orderby,
		productId,
		reviewsOnPageLoad,
		reviewsOnLoadMore,
		showLoadMore,
		showOrderby,
	} = attributes;

	const data = {
		'data-image-type': imageType,
		'data-orderby': orderby,
		'data-reviews-on-page-load': reviewsOnPageLoad,
		'data-reviews-on-load-more': reviewsOnLoadMore,
		'data-show-load-more': showLoadMore,
		'data-show-orderby': showOrderby,
	};
	let className = 'wc-block-all-reviews';

	if ( productId ) {
		data[ 'data-product-id' ] = productId;
		className = 'wc-block-reviews-by-product';
	}

	if ( Array.isArray( categoryIds ) ) {
		data[ 'data-category-ids' ] = categoryIds.join( ',' );
		className = 'wc-block-reviews-by-category';
	}

	return (
		<div
			className={ getBlockClassName( className, attributes ) }
			{ ...data }
		/>
	);
};
