/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { Slot, Fill } from '@wordpress/components';

export const WooOnboardingTaskListItem = ( { id, ...props } ) => (
	<Fill name={ 'woocommerce_onboarding_task_list_item_' + id } { ...props } />
);

WooOnboardingTaskListItem.Slot = ( { id, fillProps } ) => (
	<Slot
		name={ 'woocommerce_onboarding_task_list_item_' + id }
		fillProps={ fillProps }
	/>
);
