/**
 * External dependencies
 */
import classnames from 'classnames';
import { useState } from 'react';
import { ResizableBox } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { useThrottle } from '../../utils/useThrottle';

export const ConstrainedResizable = ( {
	className = '',
	onResize,
	...props
} ) => {
	const [ isResizing, setIsResizing ] = useState( false );

	const classNames = classnames( className, {
		'is-resizing': isResizing,
	} );
	const throttledResize = useThrottle(
		( event, direction, elt ) => {
			if ( ! isResizing ) setIsResizing( true );
			onResize( event, direction, elt );
		},
		50,
		{ leading: true }
	);

	return (
		<ResizableBox
			className={ classNames }
			enable={ { bottom: true } }
			onResize={ throttledResize }
			onResizeStop={ ( ...args ) => {
				onResize( ...args );
				setIsResizing( false );
			} }
			{ ...props }
		/>
	);
};
