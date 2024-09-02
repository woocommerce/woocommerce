/**
 * External dependencies
 */
import { getContext, store } from '@woocommerce/interactivity';

export type DrawerContext = {
	isInitiallyOpen: boolean;
	isOpen: boolean;
	isOpenContextProperty: string;
};

console.log('store woocommerce/interactivity-drawer');

store( 'woocommerce/interactivity-drawer', {
	state: {
		get gday() {
			return 'hi frontend';

		},
		
		get slideClasses() {
			console.log('slideClasses');
			const context = getContext< DrawerContext >();

			const isOpenContextProperty = context.isOpenContextProperty;

			console.log(isOpenContextProperty);
			
			if (isOpenContextProperty.split('::').length === 2) {
				const [ namespace, property ] = isOpenContextProperty.split('::');
				const parentContext = getContext( namespace );
				const isOpen = parentContext[ property.split('.')[1] ];

				console.log(parentContext);

				const baseClass = 'wc-block-components-drawer__screen-overlay';			
				const slide = isOpen ? '--with-slide-out' : '--with-slide-in';
				
				return `${baseClass}${slide}`;
			}

			return '';
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
