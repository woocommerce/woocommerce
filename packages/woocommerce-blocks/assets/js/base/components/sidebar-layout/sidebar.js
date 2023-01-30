/**
 * External dependencies
 */
import classNames from 'classnames';
import PropTypes from 'prop-types';

const Sidebar = ( { children, className } ) => {
	return (
		<div
			className={ classNames( 'wc-block-components-sidebar', className ) }
		>
			{ children }
		</div>
	);
};

Sidebar.propTypes = {
	className: PropTypes.string,
};

export default Sidebar;
