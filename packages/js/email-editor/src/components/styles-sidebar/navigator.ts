/**
 * This is a temporary solution we can drop after we stop supporting WordPress 6.7.
 * Navigator was added in WordPress 6.8.
 */
/**
 * External dependencies
 */
import {
	// @ts-expect-error Type for Navigator is missing in @types/wordpress__editor
	Navigator as WPNavigator,
	__experimentalNavigatorProvider as NavigatorProvider,
	__experimentalNavigatorScreen as NavigatorScreen,
	__experimentalNavigatorBackButton as NavigatorBackButton, // eslint-disable-line
} from '@wordpress/components';

const Navigator = WPNavigator || NavigatorProvider;

if ( ! WPNavigator ) {
	Navigator.Screen = NavigatorScreen;
	Navigator.BackButton = NavigatorBackButton;
}

export { Navigator };
