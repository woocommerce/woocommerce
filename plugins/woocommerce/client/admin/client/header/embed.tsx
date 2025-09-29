/**
 * Internal dependencies
 */
import './style.scss';
import { isTaskListActive } from '~/hooks/use-tasklists-state';
import { BaseHeader } from './shared';

export const EmbedHeader = ( {
	sections,
	query,
}: {
	sections: string[];
	query: Record< string, string >;
} ) => {
	const isReactifyPaymentsSettingsScreen = Boolean(
		query?.page === 'wc-settings' && query?.tab === 'checkout'
	);
	const showReminderBar = Boolean(
		isTaskListActive( 'setup' ) && ! isReactifyPaymentsSettingsScreen
	);

	return (
		<BaseHeader
			isEmbedded={ true }
			query={ query }
			sections={ sections }
			showReminderBar={ showReminderBar }
		/>
	);
};
