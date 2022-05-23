/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { Slot, Fill } from '@wordpress/components';

type WooOnboardingTaskListItemProps = {
	id: string;
};

/**
 * A Fill for adding Onboarding Task List items.
 *
 * @slotFill WooOnboardingTaskListItem
 * @scope woocommerce-tasks
 * @param {Object} props    React props.
 * @param {string} props.id Task id.
 */
export const WooOnboardingTaskListItem: React.FC< WooOnboardingTaskListItemProps > & {
	Slot: React.VFC< Slot.Props & { id: string } >;
} = ( { id, ...props } ) => (
	<Fill name={ 'woocommerce_onboarding_task_list_item_' + id } { ...props } />
);

WooOnboardingTaskListItem.Slot = ( { id, fillProps } ) => (
	<Slot
		name={ 'woocommerce_onboarding_task_list_item_' + id }
		fillProps={ fillProps }
	/>
);
