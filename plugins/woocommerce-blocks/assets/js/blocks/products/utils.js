/**
 * External dependencies
 */
import clsx from 'clsx';

export const getBlockClassName = ( blockClassName, attributes ) => {
	const { className, contentVisibility } = attributes;

	return clsx( blockClassName, className, {
		'has-image': contentVisibility && contentVisibility.image,
		'has-title': contentVisibility && contentVisibility.title,
		'has-rating': contentVisibility && contentVisibility.rating,
		'has-price': contentVisibility && contentVisibility.price,
		'has-button': contentVisibility && contentVisibility.button,
	} );
};
