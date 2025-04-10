/**
 * External dependencies
 */
import type { ChangeEvent, KeyboardEvent } from 'react';
import { store, getContext, getElement } from '@wordpress/interactivity';

/**
 * Internal dependencies
 */
import type { AddToCartWithOptionsStore } from '../../frontend';

type Option = {
	value: string;
	label: string;
	isSelected: boolean;
};

type Context = {
	name: string;
	selectedValue: string | null;
	option: Option;
	options: Option[];
};

type PillsContext = Context & {
	focused?: string;
};

// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

const { actions: wooAddToCartWithOptions } = store< AddToCartWithOptionsStore >(
	'woocommerce/add-to-cart-with-options',
	{},
	{ lock: universalLock }
);

function setAttribute( name: string, value: string | null ) {
	if ( value ) {
		wooAddToCartWithOptions.setAttribute( name, value );
	} else {
		wooAddToCartWithOptions.removeAttribute( name );
	}
}

function setDefaultSelectedAttribute() {
	const context = getContext< PillsContext >();
	setAttribute( context.name, context.selectedValue );
}

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
			get index() {
				const context = getContext< PillsContext >();
				return context.options.findIndex(
					( option ) => option.value === context.option.value
				);
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
				setAttribute( context.name, context.selectedValue );
			},
			handleKeyDown( event: KeyboardEvent< HTMLElement > ) {
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
						const context = getContext< PillsContext >();
						const index = state.index;
						if ( index === -1 ) return;
						const at =
							index > 0 ? index - 1 : context.options.length - 1;

						context.selectedValue = context.options[ at ].value;
						context.focused = context.selectedValue;

						setAttribute( context.name, context.selectedValue );
						keyWasProcessed = true;
						break;
					}

					case 'Down':
					case 'ArrowDown':
					case 'Right':
					case 'ArrowRight': {
						const context = getContext< PillsContext >();
						const index = state.index;
						if ( index === -1 ) return;
						const at =
							index < context.options.length - 1 ? index + 1 : 0;

						context.selectedValue = context.options[ at ].value;
						context.focused = context.selectedValue;

						setAttribute( context.name, context.selectedValue );
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
			setDefaultSelectedAttribute,
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
			handleChange( event: ChangeEvent< HTMLSelectElement > ) {
				const context = getContext< Context >();
				context.selectedValue = event.currentTarget.value;
				setAttribute( context.name, context.selectedValue );
			},
		},
		callbacks: {
			setDefaultSelectedAttribute,
		},
	},
	{ lock: true }
);
