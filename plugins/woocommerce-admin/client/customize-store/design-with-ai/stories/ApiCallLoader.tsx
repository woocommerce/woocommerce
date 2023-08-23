/**
 * Internal dependencies
 */
import { designWithAiStateMachineContext } from '../types';
import { ApiCallLoader } from '../pages';
import { WithCustomizeYourStoreLayout } from './WithCustomizeYourStoreLayout';

export const ApiCallLoaderPage = () => (
	<ApiCallLoader
		context={ {} as designWithAiStateMachineContext }
	/>
);

export default {
	title: 'WooCommerce Admin/Application/Customize Store/Design with AI/API Call Loader',
	component: ApiCallLoader,
	decorators: [ WithCustomizeYourStoreLayout ],
};
