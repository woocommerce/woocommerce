/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { Slot, Fill } from '@wordpress/components';
import {
	FillComponentProps,
	SlotComponentProps,
} from '@woocommerce/components/build-types/types';

type WooOnboardingTaskListHeaderProps = {
	id: string;
};

/**
 * A Fill for adding Onboarding Task List headers.
 *
 * @slotFill WooOnboardingTaskListHeader
 * @scope woocommerce-tasks
 * @param {Object} props    React props.
 * @param {string} props.id Task id.
 */
export const WooOnboardingTaskListHeader = ( {
	id,
	...props
}: WooOnboardingTaskListHeaderProps & Omit< FillComponentProps, 'name' > ) => (
	<Fill
		name={ 'woocommerce_onboarding_task_list_header_' + id }
		{ ...props }
	/>
);

WooOnboardingTaskListHeader.Slot = ( {
	id,
	fillProps,
}: WooOnboardingTaskListHeaderProps & Omit< SlotComponentProps, 'name' > ) => (
	<Slot
		// @ts-expect-error - I think Slot props type issues need to be fixed in @wordpress/components.
		name={ 'woocommerce_onboarding_task_list_header_' + id }
		fillProps={ fillProps }
	/>
);
