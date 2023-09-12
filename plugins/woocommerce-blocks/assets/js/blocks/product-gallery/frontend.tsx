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
	};
}

interface Selectors {
	woocommerce: {
		isSelected: ( store: unknown ) => boolean;
	};
}

interface Actions {
	woocommerce: {
		handleClick: ( context: Context ) => void;
	};
}

interface Store {
	state: State;
	context: Context;
	selectors: Selectors;
	actions: Actions;
	ref?: HTMLElement;
}

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
		},
	},
	actions: {
		woocommerce: {
			handleClick: ( { context }: Store ) => {
				context.woocommerce.selectedImage = context.woocommerce.imageId;
			},
		},
	},
} );
