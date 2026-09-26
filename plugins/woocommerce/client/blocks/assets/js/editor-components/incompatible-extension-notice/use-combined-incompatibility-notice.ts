/**
 * External dependencies
 */
import { useState, useEffect, useMemo } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { paymentStore } from '@woocommerce/block-data';
import { useLocalStorageState } from '@woocommerce/base-hooks';

/**
 * Internal dependencies
 */
import { useIncompatiblePaymentGatewaysNotice } from './use-incompatible-payment-gateways-notice';
import { useIncompatibleExtensionNotice } from './use-incompatible-extensions-notice';
import {
	getEditorStorageKey,
	isSubsetOf,
	readDismissalsFromBeforeScoping,
	readInitialDismissals,
} from './storage';

type StoredIncompatibleExtension = { [ k: string ]: string[] };

// The editor's key is its own, but its contents are not guaranteed: anything
// can overwrite localStorage, and the first value can come from the pre-scoping
// key both surfaces shared. Reads below tolerate any JSON value.
const isPlainObject = ( value: unknown ): value is Record< string, unknown > =>
	typeof value === 'object' && value !== null && ! Array.isArray( value );

// The pre-scoping key was shared with the storefront banner, which wrote bare
// slug strings into the same array; only the records were ever the editor's.
// Their contents stay untrusted, and `readSlugsFor` re-checks every slug.
const isStoredNotice = (
	value: unknown
): value is StoredIncompatibleExtension => isPlainObject( value );

const readNoticesDismissedBeforeScoping = (): StoredIncompatibleExtension[] =>
	readDismissalsFromBeforeScoping().filter( isStoredNotice );

const readSlugsFor = ( notice: unknown, blockName: string ): string[] => {
	if ( ! isPlainObject( notice ) ) {
		return [];
	}
	const slugs = notice[ blockName ];
	return Array.isArray( slugs )
		? slugs.filter( ( slug ): slug is string => typeof slug === 'string' )
		: [];
};

const holdsEntryFor = ( notice: unknown, blockName: string ): boolean =>
	isPlainObject( notice ) && blockName in notice;

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
		areIncompatibleExtensionsKnown,
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

	const storageKey = getEditorStorageKey();

	// Seeding the initial value migrates the pre-scoping dismissals in one shot:
	// the hook only falls back to it when this site's key has never been
	// written, and writes the key itself on mount. Memoised because the argument
	// is evaluated on every render even though only the first one consumes it.
	const initialDismissedNotices = useMemo(
		() =>
			readInitialDismissals(
				storageKey,
				readNoticesDismissedBeforeScoping
			),
		[ storageKey ]
	);

	const [ dismissedNotices, setDismissedNotices ] = useLocalStorageState<
		StoredIncompatibleExtension[]
	>( storageKey, initialDismissedNotices );

	const [ isVisible, setIsVisible ] = useState( false );

	const storedNotices: unknown[] = Array.isArray( dismissedNotices )
		? dismissedNotices
		: [];

	// Every incompatible item the merchant has already dismissed for this block.
	// Reduce (not find) so we tolerate the legacy shape where a single block
	// could have accumulated multiple stored entries.
	const dismissedItemSlugs = storedNotices.reduce< string[] >(
		( acc, notice ) => {
			acc.push( ...readSlugsFor( notice, blockName ) );
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
			...( Array.isArray( notices ) ? notices : [] ).filter(
				( notice ) => ! holdsEntryFor( notice, blockName )
			),
			{ [ blockName ]: slugs },
		] );

	// The merchant has just seen and accepted exactly what is incompatible now.
	const dismissNotice = () => {
		storeAcknowledgedSlugs( allIncompatibleItemSlugs );
	};

	// An acknowledgement only lasts while the item stays incompatible: slugs no
	// longer incompatible are dropped, so an item that goes away and comes back
	// counts as fresh and warns again, while the items that never left stay
	// acknowledged (the #42469 fix). It only ever removes slugs, and is held
	// back until both halves of the incompatible set are known — the payment
	// store before it loads and a missing `incompatibleExtensions` setting both
	// report an empty set, indistinguishable from "nothing is incompatible".
	const prunedAcknowledgement =
		arePaymentMethodsLoaded &&
		areIncompatibleExtensionsKnown &&
		! isSubsetOf( dismissedItemSlugs, allIncompatibleItemSlugs )
			? dismissedItemSlugs.filter( ( slug ) =>
					allIncompatibleItemSlugs.includes( slug )
			  )
			: null;

	// Deliberately no dependency array: a pruned set is always strictly smaller
	// than what is stored, so the write settles, and re-checking on every
	// render is what keeps consecutive shrinks of the incompatible set from
	// slipping past a memoised gate.
	useEffect( () => {
		if ( prunedAcknowledgement !== null ) {
			storeAcknowledgedSlugs( prunedAcknowledgement );
		}
	} );

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
