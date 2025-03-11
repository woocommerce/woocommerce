/**
 * External dependencies
 */
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

store(
	'woocommerce/add-to-cart-with-options-variation-selector-attribute-options__pills',
	{
		state: {},
		actions: {
			handleClick() {
				const context = getContext< PillsContext >();
				if ( context.selected === context.option.value ) {
					context.selected = '';
				} else {
					context.selected = context.option.value;
				}
				context.focused = context.option.value;
			},
			handleKeyDown( event: KeyboardEvent ) {
				const context = getContext< PillsContext >();

				let flag = false;

				switch ( event.key ) {
					case ' ':
						if ( context.selected === context.option.value ) {
							context.selected = '';
						} else {
							context.selected = context.option.value;
						}
						context.focused = context.option.value;
						flag = true;
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
						flag = true;
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
				const context = getContext< PillsContext >();

				if ( ! context.selected && ! context.focused ) {
					if ( context.options[ 0 ] === context.option ) {
						context.tabIndex = 0;
					}
					return;
				}

				context.option.isSelected =
					context.selected === context.option.value;

				if (
					context.option.isSelected ||
					context.focused === context.option.value
				) {
					context.tabIndex = 0;
					if ( context.focused ) {
						const { ref } = getElement();
						ref?.focus();
					}
					return;
				}

				context.tabIndex = -1;
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
		state: {},
		actions: {
			handleChange() {
				const context = getContext< DropdownContext >();
				context.selected = context.option.value;
			},
		},
		callbacks: {
			init() {
				const context = getContext< DropdownContext >();
				if ( context.selected === context.option.value ) {
					context.isSelected = 'selected';
				}
			},
		},
	}
);
