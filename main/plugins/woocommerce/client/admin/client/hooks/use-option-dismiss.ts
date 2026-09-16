/**
 * External dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';
import { optionsStore } from '@woocommerce/data';

export type DismissState = {
	isDismissed: boolean;
	hasResolved: boolean;
	onDismiss: () => void;
};

/**
 * Dismissal state backed by a WordPress option (via the wc-admin options store).
 *
 * The option is treated as a "yes"/"no" flag: a value of `'yes'` means the
 * related card has been dismissed. While the option is still resolving the card
 * is treated as dismissed so it does not flash before we know its real state.
 *
 * @param optionName The option name used to persist the dismissal.
 * @return The current dismissal state and a callback to dismiss.
 */
export const useOptionDismiss = ( optionName: string ): DismissState => {
	const { isDismissed, hasResolved } = useSelect(
		( select ) => {
			const { getOption, hasFinishedResolution } = select( optionsStore );

			// Read the option first so the resolver is always triggered. Calling
			// `getOption` is what kicks off the fetch; gating it behind the
			// resolution check (e.g. via `||` short-circuit) would mean it is
			// never called while unresolved, so resolution would never start and
			// the card would stay hidden forever.
			const value = getOption( optionName );

			const optionHasResolved = hasFinishedResolution( 'getOption', [
				optionName,
			] );

			// Treat "not yet resolved" as dismissed so the card does not flash
			// before the option value is known.
			return {
				isDismissed: ! optionHasResolved || value === 'yes',
				hasResolved: optionHasResolved,
			};
		},
		[ optionName ]
	);

	const { updateOptions } = useDispatch( optionsStore );

	const onDismiss = () => {
		void updateOptions( { [ optionName ]: 'yes' } );
	};

	return { isDismissed, hasResolved, onDismiss };
};
