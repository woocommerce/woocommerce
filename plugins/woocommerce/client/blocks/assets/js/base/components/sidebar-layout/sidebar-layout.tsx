/**
 * External dependencies
 */
import clsx from 'clsx';
import { ContainerWidthContextProvider } from '@woocommerce/base-context';

/**
 * Internal dependencies
 */
import './style.scss';
interface SidebarLayoutProps {
	children: JSX.Element | JSX.Element[];
	className: string;
}

const SidebarLayout = ( {
	children,
	className,
}: SidebarLayoutProps ): JSX.Element => {
	return (
		<ContainerWidthContextProvider
			className={ clsx(
				'wc-block-components-sidebar-layout',
				className
			) }
		>
			{ children }
		</ContainerWidthContextProvider>
	);
};

export default SidebarLayout;
