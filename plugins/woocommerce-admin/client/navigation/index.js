/**
 * External dependencies
 */
import { SlotFillProvider } from '@wordpress/components';
import { PluginArea } from '@wordpress/plugins';
import { withNavigationHydration } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import './stylesheets/index.scss';
import Container from './components/container';

const Navigation = () => (
	<SlotFillProvider>
		<Container />
		<PluginArea />
	</SlotFillProvider>
);

const HydratedNavigation = withNavigationHydration( window.wcNavigation )(
	Navigation
);

export default HydratedNavigation;
