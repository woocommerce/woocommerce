/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createElement, Fragment } from '@wordpress/element';
import { LEFT, RIGHT } from '@wordpress/keycodes';
import { KeyboardEvent } from 'react';
import { VisuallyHidden } from '@wordpress/components';

const DELTA_DISTANCE = 20; // The distance to resize per keydown in pixels.

type ResizeHandleProps = {
	direction: 'left' | 'right';
	resizeWidthBy: ( width: number ) => void;
};

export default function ResizeHandle( {
	direction,
	resizeWidthBy,
}: ResizeHandleProps ) {
	function handleKeyDown( event: KeyboardEvent< HTMLButtonElement > ) {
		const { keyCode } = event;

		if (
			( direction === 'left' && keyCode === LEFT ) ||
			( direction === 'right' && keyCode === RIGHT )
		) {
			resizeWidthBy( DELTA_DISTANCE );
		} else if (
			( direction === 'left' && keyCode === RIGHT ) ||
			( direction === 'right' && keyCode === LEFT )
		) {
			resizeWidthBy( -DELTA_DISTANCE );
		}
	}

	return (
		<>
			<button
				className={ `resizable-editor__drag-handle is-${ direction }` }
				aria-label={ __( 'Drag to resize', 'woocommerce' ) }
				aria-describedby={ `resizable-editor__resize-help-${ direction }` }
				onKeyDown={ handleKeyDown }
			/>
			<VisuallyHidden
				id={ `resizable-editor__resize-help-${ direction }` }
			>
				{ __(
					'Use left and right arrow keys to resize the canvas.',
					'woocommerce'
				) }
			</VisuallyHidden>
		</>
	);
}
