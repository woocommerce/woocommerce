/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { Button } from '@wordpress/components';
import classNames from 'classnames';

export default function SidebarButton(
	props: React.ComponentProps< typeof Button >
) {
	return (
		<Button
			{ ...props }
			className={ classNames(
				'edit-site-sidebar-button',
				props.className
			) }
		/>
	);
}
