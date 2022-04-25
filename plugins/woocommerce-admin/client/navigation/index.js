/**
 * External dependencies
 */
import { withNavigationHydration } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import './style.scss';
import Container from './components/container';

const HydratedNavigation = withNavigationHydration( window.wcNavigation )(
	Container
);

export default HydratedNavigation;
