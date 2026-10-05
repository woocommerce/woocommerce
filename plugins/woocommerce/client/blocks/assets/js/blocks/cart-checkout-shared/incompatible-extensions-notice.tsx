/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useEffect, useMemo } from '@wordpress/element';
import { getSetting, CURRENT_USER_IS_ADMIN } from '@woocommerce/settings';
import NoticeBanner from '@woocommerce/base-components/notice-banner';
import { useLocalStorageState } from '@woocommerce/base-hooks';
import {
	getFrontendStorageKey,
	isSubsetOf,
	readDismissalsFromBeforeScoping,
	readInitialDismissals,
} from '@woocommerce/editor-components/incompatible-extension-notice/storage';

/**
 * The slugs the merchant acknowledged on the storefront before the keys were
 * scoped to a site. The shared pre-scoping value usually holds both surfaces'
 * shapes at once; only the bare strings were ever the storefront banner's, so
 * the editor's records are filtered out rather than failing the whole value.
 */
const readSlugsDismissedBeforeScoping = (): string[] =>
	readDismissalsFromBeforeScoping().filter(
		( entry ): entry is string => typeof entry === 'string'
	);

interface IncompatibleExtension {
	id: string;
	title: string;
}

/**
 * The extensions this site currently declares incompatible, and whether that
 * list was delivered at all. `incompatibleExtensions` is registered by the
 * Cart and Checkout blocks rather than by core data, so the payload can arrive
 * without it — and an absent list means "we don't know", which must not be
 * mistaken for "nothing is incompatible any more".
 */
const getIncompatibleExtensions = (): {
	extensions: Record< string, string >;
	slugs: string[];
	isKnown: boolean;
} => {
	const data = getSetting< IncompatibleExtension[] | undefined >(
		'incompatibleExtensions',
		undefined
	);

	if ( ! Array.isArray( data ) ) {
		return { extensions: {}, slugs: [], isKnown: false };
	}

	const extensions: Record< string, string > = {};
	data.forEach( ( ext ) => {
		extensions[ ext.id ] = ext.title;
	} );
	return { extensions, slugs: Object.keys( extensions ), isKnown: true };
};

interface Props {
	block: 'woocommerce/cart' | 'woocommerce/checkout';
}

/**
 * The banner itself, split out so the storage hooks below never mount for a
 * shopper, who would otherwise persist an empty `[]` under this site's key and
 * close the one-shot migration for the administrator who comes along later.
 */
const IncompatibleExtensionsBanner = ( { block }: Props ) => {
	const storageKey = getFrontendStorageKey();

	// Seeding the initial value migrates the pre-scoping dismissals in one shot:
	// the hook only falls back to it when this site's key has never been
	// written, and writes the key itself on mount. Memoised because the argument
	// is evaluated on every render even though only the first one consumes it.
	const initialDismissedSlugs = useMemo(
		() =>
			readInitialDismissals(
				storageKey,
				readSlugsDismissedBeforeScoping
			),
		[ storageKey ]
	);

	const [ dismissedSlugs, setDismissedSlugs ] = useLocalStorageState<
		string[]
	>( storageKey, initialDismissedSlugs );

	// Plain localStorage that anything can overwrite, so nothing about the
	// stored value's shape is guaranteed. Narrow it rather than let a corrupt
	// value throw on the storefront.
	const acknowledgedSlugs = Array.isArray( dismissedSlugs )
		? dismissedSlugs.filter(
				( slug ): slug is string => typeof slug === 'string'
		  )
		: [];

	const { extensions, slugs, isKnown } = getIncompatibleExtensions();
	const count = slugs.length;

	// Stay dismissed while every currently-incompatible extension has already
	// been acknowledged; deactivating one keeps it dismissed, while a new,
	// never-acknowledged extension brings the notice back.
	const isDismissedAndUpToDate = isSubsetOf( slugs, acknowledgedSlugs );

	const shouldShow = count > 0 && ! isDismissedAndUpToDate;

	// An acknowledgement only lasts while the extension stays incompatible:
	// slugs no longer incompatible are dropped, so a reactivated extension
	// counts as fresh and warns again, while the ones that never left stay
	// acknowledged. It only ever removes slugs, and only when the list was
	// actually delivered — a payload that lost the setting reports an empty
	// list, indistinguishable from "nothing is incompatible", and pruning on
	// that would erase a real acknowledgement.
	const prunedAcknowledgement =
		isKnown && ! isSubsetOf( acknowledgedSlugs, slugs )
			? acknowledgedSlugs.filter( ( slug ) => slugs.includes( slug ) )
			: null;

	// Deliberately no dependency array: a pruned set is always strictly smaller
	// than what is stored, so the write settles.
	useEffect( () => {
		if ( prunedAcknowledgement !== null ) {
			setDismissedSlugs( prunedAcknowledgement );
		}
	} );

	if ( ! shouldShow ) {
		return null;
	}

	// The merchant has just seen and accepted exactly what is incompatible now.
	const dismissNotice = () => {
		setDismissedSlugs( slugs );
	};

	const extensionNames = Object.values( extensions );
	const blockLabel =
		block === 'woocommerce/cart'
			? __( 'Cart', 'woocommerce' )
			: __( 'Checkout', 'woocommerce' );

	const message =
		count === 1
			? sprintf(
					/* translators: %1$s is extension name, %2$s is block name */
					__(
						'%1$s may not be compatible with the %2$s block.',
						'woocommerce'
					),
					extensionNames[ 0 ],
					blockLabel
			  )
			: sprintf(
					/* translators: %s is block name */
					__(
						'Some extensions may not be compatible with the %s block:',
						'woocommerce'
					),
					blockLabel
			  );

	return (
		<NoticeBanner
			status="warning"
			isDismissible={ true }
			onRemove={ dismissNotice }
		>
			{ message }
			{ count > 1 && (
				<ul style={ { margin: '0.5em 0 0 1.5em', padding: 0 } }>
					{ extensionNames.map( ( name ) => (
						<li key={ name }>{ name }</li>
					) ) }
				</ul>
			) }
			<em>
				{ __( '(Only administrators see this notice)', 'woocommerce' ) }
			</em>
		</NoticeBanner>
	);
};

/**
 * Shows a notice to admin users on the frontend when there are incompatible extensions.
 *
 * Returns before the banner mounts for anyone else, so a shopper's page view
 * never reads or writes this site's dismissal storage.
 */
export const IncompatibleExtensionsFrontendNotice = ( { block }: Props ) => {
	if ( ! CURRENT_USER_IS_ADMIN ) {
		return null;
	}

	return <IncompatibleExtensionsBanner block={ block } />;
};
