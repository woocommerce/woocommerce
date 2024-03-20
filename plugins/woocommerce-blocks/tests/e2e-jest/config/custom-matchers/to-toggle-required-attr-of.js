/**
 * Internal dependencies
 */
import { findLabelWithText } from '../../../utils';

expect.extend( {
	async toToggleRequiredAttrOf( checkboxLabel, selector ) {
		if ( ! selector ) {
			return {
				message: () =>
					`a selector is required to test element's required attribute`,
				pass: false,
			};
		}

		const isRequired = async () =>
			!! ( await page.$eval( selector, ( e ) => e.required ) );
		const wasInitiallyRequired = await isRequired();
		const noChangeError = {
			message: () =>
				`input's (${ selector }) required attribute did not change after clicking the checkbox`,
			pass: false,
		};

		await ( typeof checkboxLabel === 'string'
			? await findLabelWithText( checkboxLabel )
			: checkboxLabel
		).click();

		if ( wasInitiallyRequired === ( await isRequired() ) ) {
			return noChangeError;
		}

		await ( typeof checkboxLabel === 'string'
			? await findLabelWithText( checkboxLabel )
			: checkboxLabel
		).click();

		if ( wasInitiallyRequired !== ( await isRequired() ) ) {
			return noChangeError;
		}

		return {
			message: () =>
				`input's (${ selector }) required attribute reacted to checkbox changes.`,
			pass: true,
		};
	},
} );
