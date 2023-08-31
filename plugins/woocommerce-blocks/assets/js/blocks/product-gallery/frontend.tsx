/**
 * External dependencies
 */
import { store as interactivityApiStore } from '@woocommerce/interactivity';

interface State {
	[ key: string ]: unknown;
}

interface Context {
	woocommerce: {
		productGallery: { numberOfThumbnails: number };
	};
}

interface Selectors {
	woocommerce: {
		productGallery: {
			numberOfPages: ( store: unknown ) => number;
		};
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
		woocommerce: {
			productGallery: {
				numberOfPages: ( store: SelectorsStore ) => {
					const { context } = store;

					return context.woocommerce.productGallery
						.numberOfThumbnails;
				},
			},
		},
	},
} as Store );
