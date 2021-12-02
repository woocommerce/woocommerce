/**
 * External dependencies
 */
import { cloneElement, isValidElement } from '@wordpress/element';
import type { HTMLProps, ReactElement } from 'react';

export interface IconProps {
	srcElement?: ReactElement;
	size?: number;
	className?: string;
}

function Icon( {
	srcElement,
	size = 24,
	...props
}: IconProps &
	HTMLProps< HTMLImageElement | SVGElement > ): ReactElement | null {
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
