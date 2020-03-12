/**
 * External dependencies
 */
import classNames from 'classnames';

const Sidebar = ( { children, className } ) => {
	return (
		<div className={ classNames( 'wc-block-sidebar', className ) }>
			{ children }
		</div>
	);
};

export default Sidebar;
