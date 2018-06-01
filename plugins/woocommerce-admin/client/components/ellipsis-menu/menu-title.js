/** @format */
/**
 * External dependencies
 */
import PropTypes from 'prop-types';

const MenuTitle = ( { children } ) => {
	return <div className="woocommerce-ellipsis-menu__title">{ children }</div>;
};

MenuTitle.propTypes = {
	children: PropTypes.node,
};

export default MenuTitle;
