/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import clsx from 'clsx';
import { addQueryArgs, getQueryArgs, removeQueryArgs } from '@wordpress/url';
/* eslint-disable @woocommerce/dependency-group */
import { __experimentalHStack as HStack } from '@wordpress/components';
// @ts-ignore No types for this exist yet.
import SidebarNavigationItem from '@wordpress/edit-site/build-module/components/sidebar-navigation-item';
import { useHistory } from '@automattic/site-admin';
/* eslint-enable @woocommerce/dependency-group */

type SettingItemProps = {
	label: string;
	slug: string;
	icon: React.JSX.Element;
	isActive: boolean;
};

function useLink( params: Record< string, string | undefined > ) {
	const { navigate } = useHistory();
	function onClick( event: Event ) {
		event?.preventDefault();

		navigate( addQueryArgs( 'wc-settings', params ) );
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
	const { href, onClick } = useLink( {
		page: 'wc-settings',
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
