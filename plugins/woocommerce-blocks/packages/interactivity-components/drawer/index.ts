/**
 * External dependencies
 */
import { getContext, store } from '@woocommerce/interactivity';

/**
 * Internal dependencies
 */
import './style.scss';

export type DrawerContext = {
	isInitiallyOpen: boolean;
	isOpen: boolean;
};

store( 'woocommerce/interactivity-drawer', {
	state: {
		get slideClasses() {
			const context = getContext< DrawerContext >();
			return context.isOpen ? 'wc-block-components-drawer__screen-overlay--is-hidden' : 'wc-block-components-drawer__screen-overlay--is-visible';
		},
	},
	actions: {
		handleEscapeKeyDown: ( e: KeyboardEvent ) => {
			const context = getContext< DrawerContext >();

			if ( e.code === 'Escape' && ! e.defaultPrevented ) {
				e.preventDefault();
				context.isOpen = false;
			}
		}
	},
} );
