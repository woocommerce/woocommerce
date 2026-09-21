/**
 * External dependencies
 */
import { store, getContext } from '@wordpress/interactivity';

type AccordionContext = {
	isOpen: string[];
	id: string;
	autoclose: boolean;
	openByDefault: boolean;
};

const { state } = store( 'woocommerce/accordion', {
	state: {
		get isOpen(): boolean {
			const { isOpen, id } = getContext< AccordionContext >();
			return isOpen.includes( id );
		},
	},
	actions: {
		toggle: () => {
			const context = getContext< AccordionContext >();
			const { id, autoclose } = context;

			if ( autoclose ) {
				context.isOpen = state.isOpen ? [] : [ id ];
			} else if ( state.isOpen ) {
				context.isOpen = context.isOpen.filter(
					( item ) => item !== id
				);
			} else {
				context.isOpen.push( id );
			}
		},
	},
	callbacks: {
		initIsOpen: () => {
			const context = getContext< AccordionContext >();
			const { id, openByDefault } = context;
			if ( openByDefault ) {
				context.isOpen.push( id );
			}
		},
	},
} );
