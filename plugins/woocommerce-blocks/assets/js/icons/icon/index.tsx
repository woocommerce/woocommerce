/**
 * External dependencies
 */
import { cloneElement, isValidElement } from '@wordpress/element';
import type { ReactElement } from 'react';

interface IconProps {
	srcElement?: ReactElement;
	size?: number;
	className?: string;
}

function Icon( {
	srcElement,
	size = 24,
	...props
}: IconProps ): ReactElement | null {
	if ( ! isValidElement( srcElement ) ) {
		return null;
	}
	return cloneElement( srcElement, {
		width: size,
		height: size,
		...props,
	} );
}

export default Icon;
