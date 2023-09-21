/**
 * External dependencies
 */
import { store as interactivityApiStore } from '@woocommerce/interactivity';

interface State {
	[ key: string ]: unknown;
}

interface Context {
	woocommerce: {
		selectedImage: string;
		imageId: string;
		isDialogOpen: boolean;
	};
}

interface Selectors {
	woocommerce: {
		isSelected: ( store: unknown ) => boolean;
		pagerDotFillOpacity: ( store: SelectorsStore ) => number;
		isDialogOpen: ( store: unknown ) => boolean;
	};
}

interface Actions {
	woocommerce: {
		thumbnails: {
			handleClick: ( context: Context ) => void;
		};
	};
}

interface Store {
	state: State;
	context: Context;
	selectors: Selectors;
	actions: Actions;
	ref?: HTMLElement;
}

type SelectorsStore = Pick< Store, 'context' | 'selectors' | 'ref' >;

interactivityApiStore( {
	state: {},
	selectors: {
		woocommerce: {
			isSelected: ( { context }: Store ) => {
				return (
					context?.woocommerce.selectedImage ===
					context?.woocommerce.imageId
				);
			},
			pagerDotFillOpacity( store: SelectorsStore ) {
				const { context } = store;

				return context?.woocommerce.selectedImage ===
					context?.woocommerce.imageId
					? 1
					: 0.2;
			},
			isDialogOpen: ( { context }: Store ) => {
				return context?.woocommerce.isDialogOpen;
			},
		},
	},
	actions: {
		woocommerce: {
			thumbnails: {
				handleClick: ( { context }: Store ) => {
					context.woocommerce.selectedImage =
						context.woocommerce.imageId;
				},
			},
			handleSelectImage: ( { context }: Store ) => {
				context.woocommerce.selectedImage = context.woocommerce.imageId;
			},
		},
	},
} );
