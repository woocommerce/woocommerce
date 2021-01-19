/**
 * External dependencies
 */
import classnames from 'classnames';
import { RawHTML } from '@wordpress/element';

/**
 * Internal dependencies
 */
import getShortcode from './get-shortcode';

/**
 * Return a save function using the blockType to generate the correct shortcode.
 *
 * @param {*} blockType Block being rendered.
 */
export const deprecatedConvertToShortcode = ( blockType ) => {
	return function ( props ) {
		const { align, contentVisibility } = props.attributes;
		const classes = classnames( align ? `align${ align }` : '', {
			'is-hidden-title': ! contentVisibility.title,
			'is-hidden-price': ! contentVisibility.price,
			'is-hidden-rating': ! contentVisibility.rating,
			'is-hidden-button': ! contentVisibility.button,
		} );
		return (
			<RawHTML className={ classes }>
				{ getShortcode( props, blockType ) }
			</RawHTML>
		);
	};
};
