/**
 * Internal dependencies
 */
import { Loader } from '../pages/Loader';
import { WithSetupWizardLayout } from './WithSetupWizardLayout';

import '../style.scss';

export const Short = () => (
	<Loader
		context={ { loader: { progress: 10, useStages: 'skipGuidedSetup' } } }
	/>
);

export const Plugins = () => (
	<Loader context={ { loader: { progress: 10, useStages: 'plugins' } } } />
);

export default {
	title: 'WooCommerce Admin/Application/Core Profiler/Loader',
	component: Loader,
	decorators: [ WithSetupWizardLayout ],
};
