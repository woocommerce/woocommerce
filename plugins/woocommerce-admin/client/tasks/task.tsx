/**
 * External dependencies
 */
import { WooOnboardingTask } from '@woocommerce/onboarding';

export type TaskProps = {
	query: { task: string };
};

export const Task: React.FC< TaskProps > = ( { query } ) => (
	<WooOnboardingTask.Slot id={ query.task } fillProps={ { query } } />
);
