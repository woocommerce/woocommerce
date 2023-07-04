/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { createElement, forwardRef, useContext } from '@wordpress/element';
import { column } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import { Ref } from 'react';

/**
 * Internal dependencies
 */
import { EditorContext } from '../context';

export const ShowBlockInspectorPanel = forwardRef(
	function ForwardedRefSidebarOpened(
		props: { [ key: string ]: unknown },
		ref: Ref< HTMLButtonElement >
	) {
		const { isSidebarOpened, setIsSidebarOpened } =
			useContext( EditorContext );

		function handleClick() {
			setIsSidebarOpened( ! isSidebarOpened );
		}

		return (
			<Button
				{ ...props }
				ref={ ref }
				icon={ column }
				isPressed={ isSidebarOpened }
				label={ __( 'Show/hide block inspector', 'woocommerce' ) }
				onClick={ handleClick }
			/>
		);
	}
);
