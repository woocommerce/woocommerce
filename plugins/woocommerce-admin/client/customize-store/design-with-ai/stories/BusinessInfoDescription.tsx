/**
 * Internal dependencies
 */
import { designWithAiStateMachineContext } from '../types';
import { BusinessInfoDescription } from '../pages';
import { WithCustomizeYourStoreLayout } from './WithCustomizeYourStoreLayout';

export const BusinessInfoDescriptionPage = () => (
	<BusinessInfoDescription
		context={
			{
				businessInfoDescription: {
					descriptionText: '',
				},
			} as designWithAiStateMachineContext
		}
		sendEvent={ () => {} }
	/>
);

export default {
	title: 'WooCommerce Admin/Application/Customize Store/Design with AI/Business Info Description',
	component: BusinessInfoDescription,
	decorators: [ WithCustomizeYourStoreLayout ],
};
