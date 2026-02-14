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
	wrapper: '.has-dropdown',
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

store( 'woocommerce/customer-account', {
	actions: {
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
			}

			context.isDropdownOpen = true;
		},
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
	},
} );
