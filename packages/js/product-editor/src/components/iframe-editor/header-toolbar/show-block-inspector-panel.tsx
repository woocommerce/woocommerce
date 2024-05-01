/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { createElement, forwardRef, useContext } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Ref } from 'react';

/**
 * Internal dependencies
 */
import { EditorContext } from '../context';
import drawerRight from '../settings-sidebar/drawer-right';

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
				icon={ drawerRight }
				isPressed={ isSidebarOpened }
				label={ __( 'Show/hide block inspector', 'woocommerce' ) }
				onClick={ handleClick }
			/>
		);
	}
);
