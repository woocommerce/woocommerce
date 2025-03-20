/**
 * External dependencies
 */
import { forwardRef } from '@wordpress/element';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import { ForwardRefProps } from './types';

const Main = forwardRef< HTMLInputElement, ForwardRefProps >(
	( { children, className = '' }, ref ): JSX.Element => {
		return (
			<div
				ref={ ref }
				className={ clsx( 'wc-block-components-main', className ) }
			>
				{ children }
			</div>
		);
	}
);

export default Main;
