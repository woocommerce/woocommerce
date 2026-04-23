/**
 * External dependencies
 */
import { store, getConfig } from '@wordpress/interactivity';

type CheckboxListStore = {
	state: {
		isExpanded: boolean;
		showMoreLabel: string;
	};
	actions: {
		toggleShowMore: () => void;
	};
};

const checkboxListStore = store< CheckboxListStore >(
	'woocommerce/product-filter-checkbox-list',
	{
		state: {
			isExpanded: false,
			get showMoreLabel() {
				const { showMoreLabel, showLessLabel } = getConfig();
				return this.isExpanded ? showLessLabel : showMoreLabel;
			},
		},
		actions: {
			toggleShowMore() {
				const ctx = this as unknown as CheckboxListStore[ 'state' ];
				ctx.isExpanded = ! ctx.isExpanded;
			},
		},
	},
	{ lock: true }
);

export type { CheckboxListStore };
export { checkboxListStore };
