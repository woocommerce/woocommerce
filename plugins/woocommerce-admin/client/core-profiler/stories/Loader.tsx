/**
 * Internal dependencies
 */
import { CoreProfilerLoader } from '../components/loader/Loader';
import { WithSetupWizardLayout } from './WithSetupWizardLayout';

import '../style.scss';

export const Short = () => (
	<CoreProfilerLoader
		context={ { loader: { progress: 10, useStages: 'skipGuidedSetup' } } }
	/>
);

export const Plugins = () => (
	<CoreProfilerLoader
		context={ { loader: { progress: 10, useStages: 'plugins' } } }
	/>
);

export default {
	title: 'WooCommerce Admin/Application/Core Profiler/Loader',
	component: CoreProfilerLoader,
	decorators: [ WithSetupWizardLayout ],
};
