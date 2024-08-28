/**
 * External dependencies
 */
import { isRTL } from '@wordpress/i18n';
import { chevronRightSmall, chevronLeftSmall, Icon } from '@wordpress/icons';
import { privateApis as routerPrivateApis } from '@wordpress/router';
import classNames from 'classnames';
import { createElement, useContext } from '@wordpress/element';
import {
	// @ts-expect-error missing type.
	__experimentalItem as Item,
	// @ts-expect-error missing type.
	__experimentalHStack as HStack,
	FlexBlock,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { unlock } from '../../lock-unlock';
import { SidebarNavigationContext } from '../sidebar';

const { useHistory } = unlock( routerPrivateApis );

type SidebarNavigationItemProps = {
	className?: string;
	icon?: React.JSX.Element;
	suffix?: string;
	withChevron?: boolean;
	uid?: string;
	params?: Record< string, string >;
	onClick?: ( e: Event ) => void;
	children: React.ReactNode;
};

export default function SidebarNavigationItem( {
	className,
	icon,
	withChevron = false,
	suffix,
	uid,
	params,
	onClick,
	children,
	...props
}: SidebarNavigationItemProps ) {
	const history = useHistory();
	const { navigate } = useContext( SidebarNavigationContext );
	// If there is no custom click handler, create one that navigates to `params`.
	function handleClick( e: Event ) {
		if ( onClick ) {
			onClick( e );
			navigate( 'forward' );
		} else if ( params ) {
			e.preventDefault();
			history.push( params );
			navigate( 'forward', `[id="${ uid }"]` );
		}
	}

	return (
		<Item
			className={ classNames(
				'edit-site-sidebar-navigation-item',
				{ 'with-suffix': ! withChevron && suffix },
				className
			) }
			onClick={ handleClick }
			id={ uid }
			{ ...props }
		>
			<HStack justify="flex-start">
				{ icon && (
					<Icon
						style={ { fill: 'currentcolor' } }
						icon={ icon }
						size={ 24 }
					/>
				) }
				<FlexBlock>{ children }</FlexBlock>
				{ withChevron && (
					<Icon
						icon={ isRTL() ? chevronLeftSmall : chevronRightSmall }
						className="edit-site-sidebar-navigation-item__drilldown-indicator"
						size={ 24 }
					/>
				) }
				{ ! withChevron && suffix }
			</HStack>
		</Item>
	);
}
