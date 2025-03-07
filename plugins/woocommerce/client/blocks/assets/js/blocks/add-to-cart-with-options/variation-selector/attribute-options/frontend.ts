/**
 * External dependencies
 */
import { store, getContext } from '@wordpress/interactivity';

type Option = {
	value: string;
	label: string;
};

type Context = {
	selected: string | null;
	item: Option;
	options: Option[];
	isSelected?: boolean;
	tabIndex?: number;
};

store( 'woocommerce/add-to-cart-with-options', {
	state: {},
	actions: {
		handleClick() {
			const context = getContext< Context >();
			if ( context.selected === context.item.value ) {
				context.selected = null;
			} else {
				context.selected = context.item.value;
			}
		},
		handleKeyDown( event: KeyboardEvent ) {
			const context = getContext< Context >();

			const target = event.currentTarget as HTMLDivElement;
			let flag = false;

			switch ( event.key ) {
				case ' ':
					if ( context.selected ) {
						context.selected = null;
					} else {
						context.selected = context.item.value;
					}
					flag = true;
					break;

				case 'Up':
				case 'ArrowUp':
				case 'Left':
				case 'ArrowLeft': {
					const index = context.options.findIndex(
						( item ) => item.value === context.item.value
					);
					if ( index === -1 ) return;
					if ( index > 0 ) {
						const prevSibling =
							target.previousElementSibling as HTMLDivElement;
						prevSibling?.focus();

						context.selected = context.options[ index - 1 ].value;
					} else {
						const lastSibling = target.parentElement
							?.lastElementChild as HTMLDivElement;
						lastSibling?.focus();

						context.selected =
							context.options[ context.options.length - 1 ].value;
					}
					flag = true;
					break;
				}

				case 'Down':
				case 'ArrowDown':
				case 'Right':
				case 'ArrowRight': {
					const index = context.options.findIndex(
						( item ) => item.value === context.item.value
					);
					if ( index === -1 ) return;
					if ( index < context.options.length - 1 ) {
						const nextSibling =
							target.nextElementSibling as HTMLDivElement;
						nextSibling?.focus();

						context.selected = context.options[ index + 1 ].value;
					} else {
						const firstSibling = target.parentElement
							?.firstElementChild as HTMLDivElement;
						firstSibling?.focus();

						context.selected = context.options[ 0 ].value;
					}
					flag = true;
					break;
				}
				default:
					break;
			}

			if ( flag ) {
				event.stopPropagation();
				event.preventDefault();
			}
		},
	},
	callbacks: {
		watchSelected() {
			const context = getContext< Context >();
			context.isSelected = context.selected === context.item.value;
			if ( context.selected ) {
				context.tabIndex = context.isSelected ? 0 : -1;
			} else {
				const index = context.options.findIndex(
					( item ) => item.value === context.item.value
				);
				context.tabIndex = index === 0 ? 0 : -1;
			}
		},
	},
} );
