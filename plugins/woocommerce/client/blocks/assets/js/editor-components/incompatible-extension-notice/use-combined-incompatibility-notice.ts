/**
 * External dependencies
 */
import { useState, useEffect } from '@wordpress/element';
import { useLocalStorageState } from '@woocommerce/base-hooks';

/**
 * Internal dependencies
 */
import { useIncompatiblePaymentGatewaysNotice } from './use-incompatible-payment-gateways-notice';
import { useIncompatibleExtensionNotice } from './use-incompatible-extensions-notice';

type StoredIncompatibleExtension = { [ k: string ]: string[] };
const initialDismissedNotices: StoredIncompatibleExtension[] = [];

// Whether every item in `subset` is also present in `superset`.
const isSubsetOf = ( subset: string[], superset: string[] ) =>
	subset.every( ( item ) => superset.includes( item ) );

const sortAlphabetically = ( obj: {
	[ key: string ]: string;
} ): { [ key: string ]: string } =>
	Object.fromEntries(
		Object.entries( obj ).sort( ( [ , a ], [ , b ] ) =>
			a.localeCompare( b )
		)
	);

export const useCombinedIncompatibilityNotice = (
	blockName: string
): [ boolean, () => void, { [ k: string ]: string }, number ] => {
	const [
		incompatibleExtensions,
		incompatibleExtensionSlugs,
		incompatibleExtensionCount,
	] = useIncompatibleExtensionNotice();

	const [
		incompatiblePaymentMethods,
		incompatiblePaymentMethodSlugs,
		incompatiblePaymentMethodCount,
	] = useIncompatiblePaymentGatewaysNotice();

	const allIncompatibleItems = {
		...incompatibleExtensions,
		...incompatiblePaymentMethods,
	};

	const allIncompatibleItemSlugs = [
		...incompatibleExtensionSlugs,
		...incompatiblePaymentMethodSlugs,
	];

	const allIncompatibleItemCount =
		incompatibleExtensionCount + incompatiblePaymentMethodCount;

	// The storefront banner reads this same key once, to carry over dismissals
	// made before it moved to its own — see
	// `DISMISSED_INCOMPATIBLE_EXTENSIONS_STORAGE_KEY` in
	// `blocks/cart-checkout-shared/incompatible-extensions-notice.tsx` if this
	// key ever changes.
	const [ dismissedNotices, setDismissedNotices ] = useLocalStorageState<
		StoredIncompatibleExtension[]
	>(
		`wc-blocks_dismissed_incompatible_extensions_notices`,
		initialDismissedNotices
	);

	const [ isVisible, setIsVisible ] = useState( false );

	// Every incompatible item the merchant has already dismissed for this block.
	// Reduce (not find) so we tolerate the legacy shape where a single block
	// could have accumulated multiple stored entries.
	const dismissedItemSlugs = dismissedNotices.reduce< string[] >(
		( acc, notice ) => {
			if ( Object.keys( notice ).includes( blockName ) ) {
				acc.push( ...notice[ blockName ] );
			}
			return acc;
		},
		[]
	);

	// The notice stays dismissed as long as every currently-incompatible item
	// has already been acknowledged. Removing an item (e.g. disabling a gateway)
	// keeps it dismissed; a brand-new, never-acknowledged item brings it back.
	const isDismissedNoticeUpToDate = isSubsetOf(
		allIncompatibleItemSlugs,
		dismissedItemSlugs
	);

	const shouldBeDismissed =
		allIncompatibleItemCount === 0 || isDismissedNoticeUpToDate;

	const dismissNotice = () => {
		// Consolidate any existing entries for this block into a single record
		// holding the union of everything acknowledged so far, so that later
		// re-enabling a previously-dismissed item doesn't resurface the notice.
		const otherBlockNotices = dismissedNotices.filter(
			( notice ) => ! Object.keys( notice ).includes( blockName )
		);
		const acknowledgedSlugs = [
			...new Set( [
				...dismissedItemSlugs,
				...allIncompatibleItemSlugs,
			] ),
		];
		setDismissedNotices( [
			...otherBlockNotices,
			{ [ blockName ]: acknowledgedSlugs },
		] );
	};

	// This ensures the notice is not shown on first render. This is required so
	// Gutenberg doesn't steal the focus from the Guide and focuses the block.
	useEffect( () => {
		setIsVisible( ! shouldBeDismissed );
	}, [ shouldBeDismissed ] );

	return [
		isVisible,
		dismissNotice,
		sortAlphabetically( allIncompatibleItems ),
		allIncompatibleItemCount,
	];
};
