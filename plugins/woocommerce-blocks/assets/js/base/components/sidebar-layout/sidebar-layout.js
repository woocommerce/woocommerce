/**
 * External dependencies
 */
import classNames from 'classnames';
import PropTypes from 'prop-types';

const SidebarLayout = ( { children, className } ) => {
	return (
		<div className={ classNames( 'wc-block-sidebar-layout', className ) }>
			{ children }
		</div>
	);
};

SidebarLayout.propTypes = {
	className: PropTypes.string,
};

export default SidebarLayout;
