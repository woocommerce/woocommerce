/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { Button } from '@wordpress/components';
import clsx from 'clsx';

export default function SidebarButton(
	props: React.ComponentProps< typeof Button >
) {
	return (
		<Button
			{ ...props }
			className={ clsx( 'edit-site-sidebar-button', props.className ) }
		/>
	);
}
