/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { addQueryArgs } from '@wordpress/url';
import { __experimentalItemGroup as ItemGroup } from '@wordpress/components';
import * as IconPackage from '@wordpress/icons';
import {
	SidebarNavigationScreen,
	SidebarNavigationItem,
} from '@automattic/site-admin';

const { Icon, ...icons } = IconPackage;

const SidebarNavigationScreenContent = ( {
	activePage,
	pages,
}: {
	activePage: string;
	pages: SettingsPages;
} ) => {
	return (
		<ItemGroup>
			{ Object.keys( pages ).map( ( slug ) => {
				const { label, icon } = pages[ slug ];
				const isCurrentPage = activePage === slug;
				const to = isCurrentPage
					? undefined
					: addQueryArgs( 'wc-settings', { tab: slug } );
				return (
					<SidebarNavigationItem
						icon={
							icons[ icon as keyof typeof icons ] ||
							icons.settings
						}
						aria-current={ isCurrentPage }
						uid={ slug }
						key={ slug }
						to={ to }
					>
						{ label }
					</SidebarNavigationItem>
				);
			} ) }
		</ItemGroup>
	);
};

export const Sidebar = ( {
	activePage,
	pages,
	pageTitle,
}: {
	activePage: string;
	pages: SettingsPages;
	pageTitle: string;
} ) => {
	return (
		<SidebarNavigationScreen
			title={ pageTitle }
			isRoot
			exitLink={ addQueryArgs( 'admin.php', { page: 'wc-admin' } ) }
			content={
				<SidebarNavigationScreenContent
					activePage={ activePage }
					pages={ pages }
				/>
			}
		/>
	);
};
