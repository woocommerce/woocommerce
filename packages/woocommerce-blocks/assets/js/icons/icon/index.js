/**
 * External dependencies
 */
import { cloneElement, isValidElement } from 'wordpress-element';
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
	srcElement: PropTypes.element,
	size: PropTypes.number,
};

export default Icon;
