/**
 * External dependencies
 */
import { useState, useEffect } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { paymentStore } from '@woocommerce/block-data';
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

	// Until both are true the payment store reports an empty incompatible set,
	// which is indistinguishable from "nothing is incompatible any more".
	const arePaymentMethodsLoaded = useSelect( ( select ) => {
		const { paymentMethodsInitialized, expressPaymentMethodsInitialized } =
			select( paymentStore );
		return (
			paymentMethodsInitialized() && expressPaymentMethodsInitialized()
		);
	}, [] );

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

	// Replaces every entry this block may have accumulated with a single record
	// of `slugs`, leaving other blocks' entries untouched.
	const storeAcknowledgedSlugs = ( slugs: string[] ) =>
		setDismissedNotices( ( notices ) => [
			...notices.filter(
				( notice ) => ! Object.keys( notice ).includes( blockName )
			),
			{ [ blockName ]: slugs },
		] );

	// The merchant has just seen and accepted exactly what is incompatible now.
	const dismissNotice = () => {
		storeAcknowledgedSlugs( allIncompatibleItemSlugs );
	};

	// An acknowledgement only lasts while the item stays incompatible. Dropping
	// the ones that no longer are means re-enabling an item (or reinstalling an
	// extension) counts as a fresh incompatibility and warns again, while the
	// items that never left stay acknowledged — so disabling one item still
	// doesn't resurface the notice for the rest, which is the #42469 fix.
	//
	// This only ever removes slugs, never adds, so an item the merchant hasn't
	// acknowledged can't be marked as accepted while the notice is on screen.
	// Held back until the payment store has loaded, because the empty set it
	// reports until then is indistinguishable from "nothing is incompatible"
	// and would erase the acknowledgement.
	const hasStaleAcknowledgements =
		arePaymentMethodsLoaded &&
		! isSubsetOf( dismissedItemSlugs, allIncompatibleItemSlugs );

	useEffect( () => {
		if ( hasStaleAcknowledgements ) {
			storeAcknowledgedSlugs(
				dismissedItemSlugs.filter( ( slug ) =>
					allIncompatibleItemSlugs.includes( slug )
				)
			);
		}
		// `storeAcknowledgedSlugs` and the slug arrays are rebuilt every render;
		// `hasStaleAcknowledgements` is the value that actually gates the write,
		// and it goes false as soon as the pruned set is stored.
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ hasStaleAcknowledgements ] );

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
