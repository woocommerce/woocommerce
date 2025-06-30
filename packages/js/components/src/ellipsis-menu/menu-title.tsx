/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';

/**
 * `MenuTitle` is another valid Menu child, but this does not have any accessibility attributes associated
 * (so this should not be used in place of the `EllipsisMenu` prop `label`).
 */

const MenuTitle = ( {
	children,
}: {
	/**
	 * A renderable component (or string) which will be displayed as the content of this item.
	 */
	children: React.ReactNode;
} ) => {
	return <div className="woocommerce-ellipsis-menu__title">{ children }</div>;
};

export default MenuTitle;
