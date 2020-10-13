/**
 * External dependencies
 */
import { SlotFillProvider } from '@wordpress/components';
import { PluginArea } from '@wordpress/plugins';

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

export default Navigation;
