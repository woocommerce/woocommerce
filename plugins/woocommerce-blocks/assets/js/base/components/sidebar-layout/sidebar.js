/**
 * External dependencies
 */
import { forwardRef } from 'react';
import classNames from 'classnames';

const Sidebar = forwardRef( ( { children, className = '' }, ref ) => {
	return (
		<div
			ref={ ref }
			className={ classNames( 'wc-block-components-sidebar', className ) }
		>
			{ children }
		</div>
	);
} );

export default Sidebar;
