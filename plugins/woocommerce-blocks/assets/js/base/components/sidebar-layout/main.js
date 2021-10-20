/**
 * External dependencies
 */
import { forwardRef } from 'react';
import classNames from 'classnames';

const Main = forwardRef( ( { children, className = '' }, ref ) => {
	return (
		<div
			ref={ ref }
			className={ classNames( 'wc-block-components-main', className ) }
		>
			{ children }
		</div>
	);
} );

export default Main;
