/**
 * External dependencies
 */
import { PluginArea } from '@wordpress/plugins';
import { NavSlotFillProvider } from '@woocommerce/navigation';
import { withNavigationHydration } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import './stylesheets/index.scss';
import Container from './components/container';

const Navigation = () => (
	<NavSlotFillProvider>
		<Container />
		<PluginArea />
	</NavSlotFillProvider>
);

const HydratedNavigation = withNavigationHydration( window.wcNavigation )(
	Navigation
);

export default HydratedNavigation;
