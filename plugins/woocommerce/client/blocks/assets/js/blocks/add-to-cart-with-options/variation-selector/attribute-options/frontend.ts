/**
 * External dependencies
 */
import type { KeyboardEvent } from 'react';
import { store, getContext, getElement } from '@wordpress/interactivity';

type Option = {
	value: string;
	label: string;
	isSelected: boolean;
};

type Context = {
	selectedValue: string | null;
	option: Option;
	options: Option[];
};

type PillsContext = Context & {
	focused?: string;
};

const { state, actions } = store(
	'woocommerce/add-to-cart-with-options-variation-selector-attribute-options__pills',
	{
		state: {
			get isPillSelected() {
				const { selectedValue, option } = getContext< PillsContext >();
				return selectedValue === option.value;
			},
			get pillTabIndex() {
				const { selectedValue, focused, option, options } =
					getContext< PillsContext >();

				// Allow the first pill to be focused when no option is selected.
				if (
					! selectedValue &&
					! focused &&
					options[ 0 ]?.value === option.value
				) {
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
				if ( context.selectedValue === context.option.value ) {
					context.selectedValue = '';
				} else {
					context.selectedValue = context.option.value;
				}
				context.focused = context.option.value;
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

						context.selectedValue = context.options[ at ].value;
						context.focused = context.selectedValue;
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

						context.selectedValue = context.options[ at ].value;
						context.focused = context.selectedValue;
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
	},
	{ lock: true }
);

store(
	'woocommerce/add-to-cart-with-options-variation-selector-attribute-options__dropdown',
	{
		actions: {
			handleChange() {
				const context = getContext< Context >();
				context.selectedValue = context.option.value;
			},
		},
	},
	{ lock: true }
);
