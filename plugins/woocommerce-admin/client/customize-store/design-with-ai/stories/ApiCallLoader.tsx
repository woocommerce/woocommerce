/**
 * Internal dependencies
 */
import { ApiCallLoader } from '../pages';
import { WithCustomizeYourStoreLayout } from './WithCustomizeYourStoreLayout';

export const ApiCallLoaderPage = () => <ApiCallLoader />;

export default {
	title: 'WooCommerce Admin/Application/Customize Store/Design with AI/API Call Loader',
	component: ApiCallLoader,
	decorators: [ WithCustomizeYourStoreLayout ],
};
