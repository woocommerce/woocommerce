/**
 * Internal dependencies
 */
import { findLabelWithText } from '../../../utils';

expect.extend( {
	async toToggleElement( toggleLabel, selector ) {
		if ( ! selector ) {
			return {
				message: () =>
					`a selector is required to test element's presence`,
				pass: false,
			};
		}

		const hasSelectorMatch = async () => !! ( await page.$( selector ) );
		const initiallyHadSelectorMatch = await hasSelectorMatch();
		const noChangeError = {
			message: () =>
				`element presence did not change after clicking the toggle`,
			pass: false,
		};

		await ( typeof toggleLabel === 'string'
			? await findLabelWithText( toggleLabel )
			: toggleLabel
		).click();

		if ( initiallyHadSelectorMatch === ( await hasSelectorMatch() ) ) {
			return noChangeError;
		}

		await ( typeof toggleLabel === 'string'
			? await findLabelWithText( toggleLabel )
			: toggleLabel
		).click();

		if ( initiallyHadSelectorMatch !== ( await hasSelectorMatch() ) ) {
			return noChangeError;
		}

		return {
			message: () => `element presence reacted to toggle changes.`,
			pass: true,
		};
	},
} );
