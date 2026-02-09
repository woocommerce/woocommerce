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
	__experimentalUseSlotFills as useSlotFillsHook,
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

// Add a fallback for useSlotFills hook to not break in older versions of wp.components.
// This hook was introduced in wp.components@21.2.0.
const useSlotFills = useSlotFillsHook || ( () => null );

export const useSlot = ( name ) => {
	const _useSlot = useSlotHook || __experimentalUseSlot;
	const slot = _useSlot( name );
	const fills = useSlotFills( name );

	/*
	 * Since wp.components@21.2.0, the slot object no longer contains the fills prop.
	 * Add fills prop to the slot object for backward compatibility.
	 */
	if ( typeof useSlotFillsHook === 'function' ) {
		return {
			...slot,
			fills,
		};
	}
	return slot;
};

export { ExperimentalListItem as ListItem } from './experimental-list/experimental-list-item';
export { ExperimentalList as List } from './experimental-list/experimental-list';
export { ExperimentalCollapsibleList as CollapsibleList } from './experimental-list/collapsible-list';
export { TaskItem } from './experimental-list/task-item';
export * from './inbox-note';

export * from './vertical-css-transition';
