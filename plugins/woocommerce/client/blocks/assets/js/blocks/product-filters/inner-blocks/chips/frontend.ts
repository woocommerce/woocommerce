/**
 * External dependencies
 */
import { store, getConfig } from '@wordpress/interactivity';

type ChipsStore = {
	state: {
		isExpanded: boolean;
		showMoreLabel: string;
	};
	actions: {
		toggleShowMore: () => void;
	};
};

const chipsStore = store< ChipsStore >(
	'woocommerce/product-filter-chips',
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
				const ctx = this as unknown as ChipsStore[ 'state' ];
				ctx.isExpanded = ! ctx.isExpanded;
			},
		},
	},
	{ lock: true }
);

export type { ChipsStore };
export { chipsStore };
