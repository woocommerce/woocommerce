/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { Slot, Fill } from '@wordpress/components';

type WooOnboardingTaskListHeaderProps = {
	id: string;
};

/**
 * A Fill for adding Onboarding Task List items.
 *
 * @slotFill WooOnboardingTaskListHeader
 * @scope woocommerce-tasks
 * @param {Object} props    React props.
 * @param {string} props.id Task id.
 */
export const WooOnboardingTaskListHeader: React.FC< WooOnboardingTaskListHeaderProps > & {
	Slot: React.VFC< Slot.Props & { id: string } >;
} = ( { id, ...props } ) => (
	<Fill
		name={ 'woocommerce_onboarding_task_list_header_' + id }
		{ ...props }
	/>
);

WooOnboardingTaskListHeader.Slot = ( { id, fillProps } ) => (
	<Slot
		name={ 'woocommerce_onboarding_task_list_header_' + id }
		fillProps={ fillProps }
	/>
);
