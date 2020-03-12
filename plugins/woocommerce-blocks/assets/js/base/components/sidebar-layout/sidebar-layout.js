/**
 * External dependencies
 */
import classNames from 'classnames';

const SidebarLayout = ( { children, className } ) => {
	return (
		<div className={ classNames( 'wc-block-sidebar-layout', className ) }>
			{ children }
		</div>
	);
};

export default SidebarLayout;
