/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import classnames from 'classnames';
import { Button, Dropdown, NavigableMenu } from '@wordpress/components';
import { Icon } from '@wordpress/icons';
import Ellipsis from 'gridicons/dist/ellipsis';
import React, { MouseEvent, KeyboardEvent, ReactNode } from 'react';

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
};

/**
 * This is a dropdown menu hidden behind a vertical ellipsis icon. When clicked, the inner MenuItems are displayed.
 */

const EllipsisMenu = ( {
	label,
	renderContent,
	className,
	onToggle,
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
				position="bottom left"
				renderToggle={ renderEllipsis }
				renderContent={ renderMenu }
			/>
		</div>
	);
};

export default EllipsisMenu;
