/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import './style.scss';

function TextToolbarButton( { className = '', ...props } ) {
	const classes = clsx( 'wc-block-text-toolbar-button', className );
	return <Button className={ classes } { ...props } />;
}

export default TextToolbarButton;
