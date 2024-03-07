/**
 * Internal dependencies
 */
import { designWithAiStateMachineContext } from '../types';
import { ToneOfVoice } from '../pages';
import { WithCustomizeYourStoreLayout } from './WithCustomizeYourStoreLayout';

export const ToneOfVoicePage = () => (
	<ToneOfVoice
		context={
			{
				toneOfVoice: {
					choice: '',
				},
			} as designWithAiStateMachineContext
		}
		sendEvent={ () => {} }
	/>
);

export default {
	title: 'WooCommerce Admin/Application/Customize Store/Design with AI/Tone of Voice',
	component: ToneOfVoice,
	decorators: [ WithCustomizeYourStoreLayout ],
};
