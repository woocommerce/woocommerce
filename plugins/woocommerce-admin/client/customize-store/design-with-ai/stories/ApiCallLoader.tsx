/**
 * Internal dependencies
 */
import { ApiCallLoader, AssembleHubLoader } from '../pages';
import { WithCustomizeYourStoreLayout } from './WithCustomizeYourStoreLayout';
import './style.scss';

export const APICallLoaderWithSmoothTransition = () => (
	<div className="smooth-transition">
		<ApiCallLoader />
	</div>
);
export const AssembleHubLoaderWithSmoothTransition = () => (
	<div className="smooth-transition">
		<AssembleHubLoader />
	</div>
);

export default {
	title: 'WooCommerce Admin/Application/Customize Store/Design with AI/API Call Loader',
	component: ApiCallLoader,
	decorators: [ WithCustomizeYourStoreLayout ],
};
