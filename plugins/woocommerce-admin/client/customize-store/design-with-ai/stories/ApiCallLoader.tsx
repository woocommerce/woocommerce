/**
 * Internal dependencies
 */
import { ApiCallLoader, AssembleHubLoader } from '../pages';
import { WithCustomizeYourStoreLayout } from './WithCustomizeYourStoreLayout';
import './style.scss';

export const ApiCallLoaderPage = () => <AssembleHubLoader />;
export const ApiCallLoaderPageWithSmoothTransition = () => (
	<div className="smooth-transition">
		<ApiCallLoader />
	</div>
);

export default {
	title: 'WooCommerce Admin/Application/Customize Store/Design with AI/API Call Loader',
	component: AssembleHubLoader,
	decorators: [ WithCustomizeYourStoreLayout ],
};
