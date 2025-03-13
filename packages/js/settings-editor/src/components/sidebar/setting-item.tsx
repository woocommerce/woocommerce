/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import clsx from 'clsx';
import { privateApis as routerPrivateApis } from '@wordpress/router';
import { addQueryArgs, getQueryArgs, removeQueryArgs } from '@wordpress/url';
/* eslint-disable @woocommerce/dependency-group */
import { __experimentalHStack as HStack } from '@wordpress/components';
// @ts-ignore No types for this exist yet.
import SidebarNavigationItem from '@wordpress/edit-site/build-module/components/sidebar-navigation-item';
// @ts-ignore No types for this exist yet.
import { unlock } from '@wordpress/edit-site/build-module/lock-unlock';
/* eslint-enable @woocommerce/dependency-group */

const { useHistory, useLocation } = unlock( routerPrivateApis );

type SettingItemProps = {
	label: string;
	slug: string;
	icon: React.JSX.Element;
	isActive: boolean;
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

export function SettingItem( {
	label,
	slug,
	icon,
	isActive,
}: SettingItemProps ) {
	const {
		params: { postType, page },
	} = useLocation();

	const { href, onClick } = useLink( {
		page,
		postType,
		tab: slug,
	} );

	return (
		<HStack
			justify="flex-start"
			className={ clsx( 'edit-site-sidebar-setting-item', {
				'is-selected': isActive,
			} ) }
		>
			<SidebarNavigationItem
				icon={ icon }
				href={ href }
				onClick={ onClick }
				aria-current={ isActive ? 'true' : undefined }
			>
				{ label }
			</SidebarNavigationItem>
		</HStack>
	);
}
