/**
 * External dependencies
 */
import type { ComponentProps } from 'react';
import { createElement } from '@wordpress/element';
import classnames from 'classnames';
import { Button, Dropdown, NavigableMenu } from '@wordpress/components';
import { Icon } from '@wordpress/icons';
import Ellipsis from 'gridicons/dist/ellipsis';
import { MouseEvent, KeyboardEvent, ReactNode } from 'react';

type CallbackProps = {
	isOpen?: boolean;
	onToggle: () => void;
	onClose?: () => void;
};

type EllipsisMenuProps = {
	/**
	 * The label shown when hovering/focusing on the icon button.
	 */
	label: string;
	/**
	 * A function returning `MenuTitle`/`MenuItem` components as a render prop. Arguments from Dropdown passed as function arguments.
	 */
	renderContent?: ( props: CallbackProps ) => ReactNode | JSX.Element;
	/**
	 * Classname to add to ellipsis menu.
	 */
	className?: string;
	/**
	 * Callback function when dropdown button is clicked, it provides the click event.
	 */
	onToggle?: ( e: MouseEvent | KeyboardEvent ) => void;
	/**
	 * Placement of the dropdown menu. Default is 'bottom-start'.
	 */
	placement?: ComponentProps<
		typeof Dropdown
		// @ts-expect-error missing prop in types. -- Props type definition is outdated and does not include popoverProps.
	>[ 'popoverProps' ][ 'placement' ];
	/**
	 * By default, the first menu item will receive focus. This is the same as setting this prop to "firstElement".
	 * Specifying a true value will focus the container instead.
	 * Specifying a false value disables the focus handling entirely
	 * (this should only be done when an appropriately accessible
	 * substitute behavior exists).
	 *
	 * @default 'firstElement'
	 */
	focusOnMount?: ComponentProps< typeof Dropdown >[ 'focusOnMount' ];
};

/**
 * This is a dropdown menu hidden behind a vertical ellipsis icon. When clicked, the inner MenuItems are displayed.
 */

const EllipsisMenu = ( {
	label,
	renderContent,
	className,
	onToggle,
	// if set bottom-start, it will fallback to bottom-end / top-end / top-start
	// if it's bottom, it will fallback to only top
	placement = 'bottom-start',
	focusOnMount = 'firstElement',
}: EllipsisMenuProps ) => {
	if ( ! renderContent ) {
		return null;
	}

	const renderEllipsis = ( {
		onToggle: toggleHandlerOverride,
		isOpen,
	}: CallbackProps ) => {
		const toggleClassname = classnames(
			'woocommerce-ellipsis-menu__toggle',
			{
				'is-opened': isOpen,
			}
		);

		return (
			<Button
				className={ toggleClassname }
				onClick={ ( e: MouseEvent | KeyboardEvent ) => {
					if ( onToggle ) {
						onToggle( e );
					}
					if ( toggleHandlerOverride ) {
						toggleHandlerOverride();
					}
				} }
				title={ label }
				aria-expanded={ isOpen }
			>
				<Icon icon={ <Ellipsis /> } />
			</Button>
		);
	};

	const renderMenu = ( renderContentArgs: CallbackProps ) => (
		<NavigableMenu className="woocommerce-ellipsis-menu__content">
			{ renderContent( renderContentArgs ) }
		</NavigableMenu>
	);

	return (
		<div className={ classnames( className, 'woocommerce-ellipsis-menu' ) }>
			<Dropdown
				contentClassName="woocommerce-ellipsis-menu__popover"
				popoverProps={ { placement, focusOnMount } }
				renderToggle={ renderEllipsis }
				renderContent={ renderMenu }
			/>
		</div>
	);
};

export default EllipsisMenu;
