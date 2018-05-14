/** @format */
/**
 * External dependencies
 */
import PropTypes from 'prop-types';

const MenuTitle = ( { children } ) => {
	return <div className="woo-dash__ellipsis-menu-title">{ children }</div>;
};

MenuTitle.propTypes = {
	children: PropTypes.node,
};

export default MenuTitle;
