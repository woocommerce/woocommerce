/**
 * External dependencies
 */
import { withConsole } from '@storybook/addon-console';

/**
 * Internal dependencies
 */
import QuickLinks from '..';

function logItemClick( listItemTag ) {
	// eslint-disable-next-line no-console
	console.log( `QuickLinks item with tag '${ listItemTag }' clicked` );
	return false;
}

function getSetting() {
	return 'https://example.com';
}

export default {
	title: 'WooCommerce Admin/homescreen/QuickLinks',
	component: QuickLinks,
	decorators: [ ( storyFn, context ) => withConsole()( storyFn )( context ) ],
};

export const Default = () => {
	return (
		<QuickLinks getSetting={ getSetting } onItemClick={ logItemClick } />
	);
};
