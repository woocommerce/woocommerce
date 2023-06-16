/**
 * External dependencies
 */
import { __, isRTL } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { createElement, forwardRef, useContext } from '@wordpress/element';
import { redo as redoIcon, undo as undoIcon } from '@wordpress/icons';
import { Ref } from 'react';

/**
 * Internal dependencies
 */
import { EditorContext } from '../context';

function EditorHistoryRedo(
	props: { [ key: string ]: unknown },
	ref: Ref< HTMLButtonElement >
) {
	const { hasRedo, redo } = useContext( EditorContext );

	return (
		<Button
			{ ...props }
			ref={ ref }
			icon={ ! isRTL() ? redoIcon : undoIcon }
			/* translators: button label text should, if possible, be under 16 characters. */
			label={ __( 'Redo', 'woocommerce' ) }
			// If there are no redo levels we don't want to actually disable this
			// button, because it will remove focus for keyboard users.
			// See: https://github.com/WordPress/gutenberg/issues/3486
			aria-disabled={ ! hasRedo }
			onClick={ hasRedo ? redo : undefined }
			className="editor-history__redo"
		/>
	);
}

export default forwardRef( EditorHistoryRedo );
