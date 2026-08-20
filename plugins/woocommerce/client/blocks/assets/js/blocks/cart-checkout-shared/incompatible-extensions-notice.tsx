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
 * scoped to a site.
 *
 * That value holds either surface's shape, and often both at once: the
 * storefront wrote bare slug strings, while the editor appends
 * `{ [blockName]: slugs }` records without discarding what it finds. Only the
 * strings were ever the storefront banner's, so the records are filtered out
 * rather than treated as a reason to skip the whole value.
 */
const readSlugsDismissedBeforeScoping = (): string[] =>
	readDismissalsFromBeforeScoping().filter(
		( entry ): entry is string => typeof entry === 'string'
	);

interface IncompatibleExtension {
	id: string;
	title: string;
}

const getIncompatibleExtensions = (): {
	extensions: Record< string, string >;
	slugs: string[];
} => {
	const extensions: Record< string, string > = {};
	const data = getSetting< IncompatibleExtension[] >(
		'incompatibleExtensions',
		[]
	);
	data.forEach( ( ext ) => {
		extensions[ ext.id ] = ext.title;
	} );
	return { extensions, slugs: Object.keys( extensions ) };
};

interface Props {
	block: 'woocommerce/cart' | 'woocommerce/checkout';
}

/**
 * Shows a notice to admin users on the frontend when there are incompatible extensions.
 */
export const IncompatibleExtensionsFrontendNotice = ( {
	block,
}: Props ): JSX.Element | null => {
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

	const { extensions, slugs } = getIncompatibleExtensions();
	const count = slugs.length;

	// Stay dismissed while every currently-incompatible extension has already
	// been acknowledged; deactivating one keeps it dismissed, while a new,
	// never-acknowledged extension brings the notice back.
	const isDismissedAndUpToDate = isSubsetOf( slugs, acknowledgedSlugs );

	const shouldShow =
		CURRENT_USER_IS_ADMIN && count > 0 && ! isDismissedAndUpToDate;

	// An acknowledgement only lasts while the extension stays incompatible.
	// Dropping the ones that no longer are means reactivating (or reinstalling)
	// an extension counts as a fresh incompatibility and warns again, while the
	// ones that never left stay acknowledged — so deactivating a single
	// extension still doesn't resurface the banner for the rest.
	//
	// This only ever removes slugs, never adds, so an extension the merchant
	// hasn't acknowledged can't be marked as accepted while the banner is up.
	// Limited to admins because `incompatibleExtensions` is only exposed to
	// them; for a shopper the empty list would erase the acknowledgement.
	const hasStaleAcknowledgements =
		CURRENT_USER_IS_ADMIN && ! isSubsetOf( acknowledgedSlugs, slugs );

	useEffect( () => {
		if ( hasStaleAcknowledgements ) {
			setDismissedSlugs(
				acknowledgedSlugs.filter( ( slug ) => slugs.includes( slug ) )
			);
		}
		// `slugs` is rebuilt every render; `hasStaleAcknowledgements` is the
		// value that gates the write, and it goes false once the pruned set has
		// been stored.
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ hasStaleAcknowledgements ] );

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
