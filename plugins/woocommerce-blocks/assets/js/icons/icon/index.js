/**
 * External dependencies
 */
import { cloneElement, isValidElement } from '@wordpress/element';
import { SVG } from '@wordpress/components';
import PropTypes from 'prop-types';

function Icon( { srcElement, size = 24, ...props } ) {
	return (
		isValidElement( srcElement ) &&
		cloneElement( srcElement, {
			width: size,
			height: size,
			...props,
		} )
	);
}

Icon.propTypes = {
	srcElement: PropTypes.oneOfType( [
		PropTypes.instanceOf( SVG ),
		// HTMLImageElement is a global interface
		// eslint-disable-next-line no-undef
		PropTypes.instanceOf( HTMLImageElement ),
	] ),
	size: PropTypes.number,
};

export default Icon;
