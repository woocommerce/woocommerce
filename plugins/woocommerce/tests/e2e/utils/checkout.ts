/**
 * External dependencies
 */
import type { Page } from '@playwright/test';
import { expect } from '@playwright/test';

/**
 * Accept the classic checkout terms through its backing form control.
 *
 * Some test themes hide the native checkbox and render a separate visual
 * control. Setting the backing input keeps the submitted checkout state
 * deterministic for tests whose behavior does not concern the terms UI.
 *
 * @param page Playwright page containing the classic checkout form.
 */
export const acceptClassicCheckoutTerms = async ( page: Page ) => {
	const termsCheckbox = page.locator( '#terms' );
	await termsCheckbox.waitFor( { state: 'attached' } );
	await termsCheckbox.evaluate( ( checkbox: HTMLInputElement ) => {
		checkbox.checked = true;
		checkbox.dispatchEvent( new Event( 'change', { bubbles: true } ) );
	} );
	await expect( termsCheckbox ).toBeChecked();
};
