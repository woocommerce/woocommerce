/**
 * Internal dependencies
 */
import { designWithAiStateMachineContext } from '../types';
import { LookAndFeel } from '../pages';
import { WithCustomizeYourStoreLayout } from './WithCustomizeYourStoreLayout';

export const LookAndFeelPage = () => (
	<LookAndFeel
		context={
			{
				lookAndFeel: {
					choice: '',
				},
			} as designWithAiStateMachineContext
		}
		sendEvent={ () => {} }
	/>
);

export default {
	title: 'WooCommerce Admin/Application/Customize Store/Design with AI/Look and Feel',
	component: LookAndFeel,
	decorators: [ WithCustomizeYourStoreLayout ],
};
