/**
 * External dependencies
 */
import { useSlot } from '@woocommerce/experimental';

/**
 * Internal dependencies
 */
import './progress-header.scss';
import {
	WC_TASKLIST_EXPERIMENTAL_PROGRESS_HEADER_SLOT_NAME,
	WooTaskListProgressHeaderItem,
} from './utils';
import {
	DefaultProgressHeader,
	DefaultProgressHeaderProps,
} from './default-progress-header';

export const ProgressHeader: React.FC< DefaultProgressHeaderProps > = ( {
	taskListId,
} ) => {
	const slot = useSlot( WC_TASKLIST_EXPERIMENTAL_PROGRESS_HEADER_SLOT_NAME );

	return Boolean( slot?.fills?.length ) ? (
		<WooTaskListProgressHeaderItem.Slot fillProps={ { taskListId } } />
	) : (
		<DefaultProgressHeader taskListId={ taskListId } />
	);
};
