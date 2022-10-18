/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import { createElement } from '@wordpress/element';

/**
 * `MenuTitle` is another valid Menu child, but this does not have any accessibility attributes associated
 * (so this should not be used in place of the `EllipsisMenu` prop `label`).
 *
 * @param {Object} props
 * @param {Node}   props.children
 * @return {Object} -
 */
const MenuTitle = ( { children } ) => {
	return <div className="woocommerce-ellipsis-menu__title">{ children }</div>;
};

MenuTitle.propTypes = {
	/**
	 * A renderable component (or string) which will be displayed as the content of this item.
	 */
	children: PropTypes.node,
};

export default MenuTitle;
