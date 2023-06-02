/**
 * External dependencies
 */
import classNames from 'classnames';
import PropTypes from 'prop-types';
import { ContainerWidthContextProvider } from '@woocommerce/base-context';

/**
 * Internal dependencies
 */
import './style.scss';

const SidebarLayout = ( { children, className } ) => {
	return (
		<ContainerWidthContextProvider
			className={ classNames(
				'wc-block-components-sidebar-layout',
				className
			) }
		>
			{ children }
		</ContainerWidthContextProvider>
	);
};

SidebarLayout.propTypes = {
	className: PropTypes.string,
};

export default SidebarLayout;
