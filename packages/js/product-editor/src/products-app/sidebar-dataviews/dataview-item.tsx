/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import classNames from 'classnames';
import { privateApis as routerPrivateApis } from '@wordpress/router';
import { addQueryArgs, getQueryArgs, removeQueryArgs } from '@wordpress/url';
import { VIEW_LAYOUTS } from '@wordpress/dataviews';
// @ts-expect-error missing type.
// eslint-disable-next-line @woocommerce/dependency-group
import { __experimentalHStack as HStack } from '@wordpress/components';

/**
 * Internal dependencies
 */
import SidebarNavigationItem from '../sidebar-navigation-item';
import { unlock } from '../../lock-unlock';

const { useHistory, useLocation } = unlock( routerPrivateApis );

type DataViewItemProps = {
	title: string;
	slug: string;
	customViewId?: string;
	type: string;
	icon: React.JSX.Element;
	isActive: boolean;
	isCustom: boolean;
	suffix?: string;
};

function useLink(
	params: Record< string, string | undefined >,
	state?: Record< string, string | undefined >,
	shouldReplace = false
) {
	const history = useHistory();
	function onClick( event: Event ) {
		event?.preventDefault();

		if ( shouldReplace ) {
			history.replace( params, state );
		} else {
			history.push( params, state );
		}
	}

	const currentArgs = getQueryArgs( window.location.href );
	const currentUrlWithoutArgs = removeQueryArgs(
		window.location.href,
		...Object.keys( currentArgs )
	);

	const newUrl = addQueryArgs( currentUrlWithoutArgs, params );

	return {
		href: newUrl,
		onClick,
	};
}

export default function DataViewItem( {
	title,
	slug,
	customViewId,
	type,
	icon,
	isActive,
	isCustom,
	suffix,
}: DataViewItemProps ) {
	const {
		params: { postType, page },
	} = useLocation();

	const iconToUse =
		icon || VIEW_LAYOUTS.find( ( v ) => v.type === type )?.icon;

	let activeView: undefined | string = isCustom ? customViewId : slug;
	if ( activeView === 'all' ) {
		activeView = undefined;
	}
	const linkInfo = useLink( {
		page,
		postType,
		layout: type,
		activeView,
		isCustom: isCustom ? 'true' : undefined,
	} );
	return (
		<HStack
			justify="flex-start"
			className={ classNames(
				'edit-site-sidebar-dataviews-dataview-item',
				{
					'is-selected': isActive,
				}
			) }
		>
			<SidebarNavigationItem
				icon={ iconToUse }
				{ ...linkInfo }
				aria-current={ isActive ? 'true' : undefined }
			>
				{ title }
			</SidebarNavigationItem>
			{ suffix }
		</HStack>
	);
}
