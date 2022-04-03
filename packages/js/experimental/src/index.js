/**
 * External dependencies
 */
import {
	__experimentalNavigation,
	__experimentalNavigationBackButton,
	__experimentalNavigationGroup,
	__experimentalNavigationMenu,
	__experimentalNavigationItem,
	__experimentalText,
	__experimentalUseSlot,
	Navigation as NavigationComponent,
	NavigationBackButton as NavigationBackButtonComponent,
	NavigationGroup as NavigationGroupComponent,
	NavigationMenu as NavigationMenuComponent,
	NavigationItem as NavigationItemComponent,
	Text as TextComponent,
	useSlot as useSlotHook,
} from '@wordpress/components';

/**
 * Prioritize exports of non-experimental components over experimental.
 */
export const Navigation = NavigationComponent || __experimentalNavigation;
export const NavigationBackButton =
	NavigationBackButtonComponent || __experimentalNavigationBackButton;
export const NavigationGroup =
	NavigationGroupComponent || __experimentalNavigationGroup;
export const NavigationMenu =
	NavigationMenuComponent || __experimentalNavigationMenu;
export const NavigationItem =
	NavigationItemComponent || __experimentalNavigationItem;
export const Text = TextComponent || __experimentalText;
export const useSlot = useSlotHook || __experimentalUseSlot;

export { ExperimentalListItem as ListItem } from './experimental-list/experimental-list-item';
export { ExperimentalList as List } from './experimental-list/experimental-list';
export { ExperimentalCollapsibleList as CollapsibleList } from './experimental-list/collapsible-list';
export { TaskItem } from './experimental-list/task-item';
export * from './inbox-note';

export * from './vertical-css-transition';
