/**
 * External dependencies
 */
import { store as interactivityApiStore } from '@woocommerce/interactivity';

interface State {
	[ key: string ]: unknown;
}

interface Context {
	productGallery: { numberOfThumbnails: number };
}

interface Selectors {
	productGallery: {
		getNumberOfPages: ( store: unknown ) => number;
	};
}

interface Store {
	state: State;
	context: Context;
	selectors: Selectors;
	ref: HTMLElement;
}

type SelectorsStore = Pick< Store, 'context' | 'selectors' >;

interactivityApiStore( {
	selectors: {
		productGallery: {
			getNumberOfPages: ( store: SelectorsStore ) => {
				const { context } = store;

				return context.productGallery.numberOfThumbnails;
			},
		},
	},
} as Store );
