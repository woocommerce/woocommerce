/**
 * External dependencies
 */
import classNames from 'classnames';
import PropTypes from 'prop-types';
import { useContainerQueries } from '@woocommerce/base-hooks';

/**
 * Internal dependencies
 */
import './style.scss';

const SidebarLayout = ( { children, className } ) => {
	const [ resizeListener, containerQueryClassName ] = useContainerQueries();

	return (
		<div
			className={ classNames(
				'wc-block-sidebar-layout',
				className,
				containerQueryClassName
			) }
		>
			{ resizeListener }
			{ children }
		</div>
	);
};

SidebarLayout.propTypes = {
	className: PropTypes.string,
};

export default SidebarLayout;
