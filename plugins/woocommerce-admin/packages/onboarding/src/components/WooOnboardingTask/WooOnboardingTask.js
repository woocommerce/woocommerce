/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { Slot, Fill } from '@wordpress/components';

export const WooOnboardingTask = ( { id, ...props } ) => (
	<Fill name={ 'woocommerce_onboarding_task_' + id } { ...props } />
);

WooOnboardingTask.Slot = ( { id, fillProps } ) => (
	<Slot
		name={ 'woocommerce_onboarding_task_' + id }
		fillProps={ fillProps }
	/>
);
