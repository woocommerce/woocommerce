/**
 * External dependencies
 */
import { BaseControl, FormToggle } from '@wordpress/components';
import { createElement } from '@wordpress/element';
import { DOWN, ENTER, SPACE, UP } from '@wordpress/keycodes';
import { useRef, MouseEvent, KeyboardEvent } from 'react';

type MenuItemProps = {
	/**
	 * Whether the menu item is checked or not. Only relevant for menu items with `isCheckbox`.
	 */
	checked?: boolean;
	/**
	 * A renderable component (or string) which will be displayed as the content of this item. Generally a `ToggleControl`.
	 */
	children?: React.ReactNode;
	/**
	 * Whether the menu item is a checkbox (will render a FormToggle and use the `menuitemcheckbox` role).
	 */
	isCheckbox?: boolean;
	/**
	 * Boolean to control whether the MenuItem should handle the click event. Defaults to false, assuming your child component
	 * handles the click event.
	 */
	isClickable?: boolean;
	/**
	 * A function called when this item is activated via keyboard ENTER or SPACE; or when the item is clicked
	 * (only if `isClickable` is set).
	 */
	onInvoke: ( () => void ) | undefined;
};

const MenuItem = ( {
	checked,
	children,
	isCheckbox = false,
	isClickable = false,
	onInvoke = () => {},
}: MenuItemProps ) => {
	const container = useRef< HTMLInputElement >( null );
	const onClick = ( event: MouseEvent< HTMLDivElement > ) => {
		if ( isClickable ) {
			event.preventDefault();
			onInvoke();
		}
	};

	const onKeyDown = ( event: KeyboardEvent< HTMLDivElement > ) => {
		const eventTarget = event.target as HTMLElement;
		if ( eventTarget.isSameNode( event.currentTarget ) ) {
			if ( event.keyCode === ENTER || event.keyCode === SPACE ) {
				event.preventDefault();
				onInvoke();
			}
			if ( event.keyCode === UP ) {
				event.preventDefault();
			}
			if ( event.keyCode === DOWN ) {
				event.preventDefault();
				const nextElementToFocus = ( eventTarget.nextSibling ||
					eventTarget.parentNode?.querySelector(
						'.woocommerce-ellipsis-menu__item'
					) ) as HTMLElement;
				nextElementToFocus.focus();
			}
		}
	};

	if ( isCheckbox ) {
		return (
			<div
				aria-checked={ checked }
				ref={ container }
				role="menuitemcheckbox"
				tabIndex={ 0 }
				onKeyDown={ onKeyDown }
				onClick={ onClick }
				className="woocommerce-ellipsis-menu__item"
			>
				{ /* id props is actuall an optional prop. It looks like DefinitelyTyped has out-of-date types*/ }
				{ /* @ts-expect-error: Suprressing `id` is required prop error.  */ }
				<BaseControl className="components-toggle-control">
					<FormToggle
						aria-hidden="true"
						checked={ checked }
						onChange={ onInvoke }
						onClick={ ( e ) => e.stopPropagation() }
						tabIndex={ -1 }
					/>
					{ children }
				</BaseControl>
			</div>
		);
	}

	return (
		<div
			role="menuitem"
			tabIndex={ 0 }
			onKeyDown={ onKeyDown }
			onClick={ onClick }
			className="woocommerce-ellipsis-menu__item"
		>
			{ children }
		</div>
	);
};

export default MenuItem;
