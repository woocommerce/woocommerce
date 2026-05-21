/**
 * External dependencies
 */
import { store, getContext, getElement } from '@wordpress/interactivity';

export interface CustomerAccountContext {
	isDropdownOpen: boolean;
	showAbove: boolean;
	alignRight: boolean;
}

const SELECTORS = {
	wrapper: '.wc-block-customer-account--has-dropdown',
	dropdown: '.wc-block-customer-account__dropdown',
	trigger: '.wc-block-customer-account__toggle',
};

const FLIP_THRESHOLD = 16;
const SHOW_ABOVE_THRESHOLD = 0.6;

const getWrapper = (): HTMLElement | null => {
	const { ref } = getElement();
	return ref?.closest( SELECTORS.wrapper ) as HTMLElement | null;
};

const getDropdown = ( wrapper: HTMLElement ): HTMLElement | null => {
	return wrapper.querySelector( SELECTORS.dropdown ) as HTMLElement | null;
};

const focusTrigger = () => {
	const { ref } = getElement();
	const trigger = ref?.querySelector(
		SELECTORS.trigger
	) as HTMLElement | null;
	trigger?.focus();
};

// Set the background color from body so it's reliable. Pattern used in other blocks too
// e.g. Mini Cart (see mini-cart/utils/set-styles.ts).
const getBodyBackgroundColor = (): string => {
	const color = getComputedStyle( document.body ).backgroundColor;
	if ( ! color || color === 'rgba(0, 0, 0, 0)' || color === 'transparent' ) {
		return '#fff';
	}
	return color;
};

const updateDropdownPosition = (
	context: CustomerAccountContext,
	wrapper: HTMLElement
) => {
	const rect = wrapper.getBoundingClientRect();
	const viewportHeight = window.innerHeight;
	const viewportWidth = document.documentElement.clientWidth;

	context.showAbove = rect.bottom > viewportHeight * SHOW_ABOVE_THRESHOLD;

	const dropdown = getDropdown( wrapper );
	if ( ! dropdown ) {
		return;
	}

	dropdown.hidden = false;
	const dropdownWidth = dropdown.offsetWidth;
	dropdown.hidden = true;

	const rightSpace = viewportWidth - ( rect.left + dropdownWidth );
	context.alignRight = rightSpace < FLIP_THRESHOLD;
};

const { actions: privateActions } = store(
	'woocommerce/customer-account/private',
	{
		actions: {
			handleDocumentClick: ( event: MouseEvent ) => {
				const context = getContext< CustomerAccountContext >();
				if ( ! context.isDropdownOpen ) {
					return;
				}
				const { ref } = getElement();
				if ( ref && ! ref.contains( event.target as Node ) ) {
					context.isDropdownOpen = false;
				}
			},
			handleKeydown: ( event: KeyboardEvent ) => {
				if ( event.key !== 'Escape' ) {
					return;
				}

				const context = getContext< CustomerAccountContext >();
				if ( ! context.isDropdownOpen ) {
					return;
				}

				context.isDropdownOpen = false;
				focusTrigger();
			},
			handleFocusOut: ( event: FocusEvent ) => {
				const context = getContext< CustomerAccountContext >();
				if ( ! context.isDropdownOpen ) {
					return;
				}

				const { ref } = getElement();
				const relatedTarget = event.relatedTarget as Node | null;
				if (
					ref &&
					( ! relatedTarget || ! ref.contains( relatedTarget ) )
				) {
					context.isDropdownOpen = false;
				}
			},
			toggleDropdown: ( event: MouseEvent ) => {
				event.preventDefault();
				event.stopPropagation();

				const context = getContext< CustomerAccountContext >();
				if ( context.isDropdownOpen ) {
					context.isDropdownOpen = false;
					return;
				}

				const wrapper = getWrapper();
				if ( wrapper ) {
					updateDropdownPosition( context, wrapper );
					wrapper.style.setProperty(
						'--wc-customer-account-dropdown-surface',
						getBodyBackgroundColor()
					);
				}

				context.isDropdownOpen = true;
			},
		},
	}
);

store( 'woocommerce/customer-account', {
	state: {
		/**
		 * Whether the dropdown is open.
		 *
		 * @type {boolean}
		 */
		get isDropdownOpen() {
			const context = getContext< CustomerAccountContext >();
			return context.isDropdownOpen;
		},
	},
	actions: {
		/**
		 * Toggle the dropdown.
		 *
		 * Public API for third-party toggling of the dropdown.
		 *
		 * @param event MouseEvent The event that triggered the toggle.
		 */
		toggleDropdown: ( event: MouseEvent ) => {
			privateActions.toggleDropdown( event as MouseEvent );
		},
	},
} );
