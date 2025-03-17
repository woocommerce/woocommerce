/**
 * External dependencies
 */
import type { MouseEvent, KeyboardEvent } from 'react';
import { store, getContext, getElement } from '@wordpress/interactivity';

type Option = {
	value: string;
	label: string;
	isSelected: boolean;
};

type Context = {
	selected: string | null;
	option: Option;
	options: Option[];
};

type PillsContext = Context & {
	tabIndex?: number;
	selected?: string;
	focused?: string;
};

const { state, actions } = store(
	'woocommerce/add-to-cart-with-options-variation-selector-attribute-options__pills',
	{
		state: {
			get isPillSelected() {
				const { selected, option } = getContext< PillsContext >();
				return selected === option.value;
			},
			get pillTabIndex() {
				const { selected, focused, option, options } =
					getContext< PillsContext >();

				// Allow the first pill to be focused when no option is selected.
				if ( ! selected && ! focused && options[ 0 ] === option ) {
					return 0;
				}

				if ( state.isPillSelected || focused === option.value ) {
					return 0;
				}

				return -1;
			},
		},
		actions: {
			toggleSelected() {
				const context = getContext< PillsContext >();
				if ( context.selected === context.option.value ) {
					context.selected = '';
				} else {
					context.selected = context.option.value;
				}
				context.focused = context.option.value;
			},
			handleClick( event: MouseEvent< HTMLElement > ) {
				event.preventDefault();

				actions.toggleSelected();
			},
			handleKeyDown( event: KeyboardEvent< HTMLElement > ) {
				const context = getContext< PillsContext >();

				let keyWasProcessed = false;

				switch ( event.key ) {
					case ' ':
						actions.toggleSelected();
						keyWasProcessed = true;
						break;

					case 'Up':
					case 'ArrowUp':
					case 'Left':
					case 'ArrowLeft': {
						const index = context.options.findIndex(
							( option ) => option.value === context.option.value
						);
						if ( index === -1 ) return;
						const at =
							index > 0 ? index - 1 : context.options.length - 1;

						context.selected = context.options[ at ].value;
						context.focused = context.selected;
						keyWasProcessed = true;
						break;
					}

					case 'Down':
					case 'ArrowDown':
					case 'Right':
					case 'ArrowRight': {
						const index = context.options.findIndex(
							( option ) => option.value === context.option.value
						);
						if ( index === -1 ) return;
						const at =
							index < context.options.length - 1 ? index + 1 : 0;

						context.selected = context.options[ at ].value;
						context.focused = context.selected;
						keyWasProcessed = true;
						break;
					}
					default:
						break;
				}

				if ( keyWasProcessed ) {
					event.stopPropagation();
					event.preventDefault();
				}
			},
		},
		callbacks: {
			watchSelected() {
				const { focused } = getContext< PillsContext >();

				if ( state.pillTabIndex === 0 && focused ) {
					const { ref } = getElement();
					ref?.focus();
				}
			},
		},
	}
);

type DropdownContext = Context & {
	isSelected: 'selected' | undefined;
};

store(
	'woocommerce/add-to-cart-with-options-variation-selector-attribute-options__dropdown',
	{
		state: {
			get isOptionSelected() {
				const context = getContext< DropdownContext >();
				if ( context.selected === context.option.value ) {
					return 'selected';
				}
			},
		},
		actions: {
			handleChange() {
				const context = getContext< DropdownContext >();
				context.selected = context.option.value;
			},
		},
	}
);
