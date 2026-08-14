/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useMemo } from '@wordpress/element';
import { getSetting, CURRENT_USER_IS_ADMIN } from '@woocommerce/settings';
import NoticeBanner from '@woocommerce/base-components/notice-banner';
import { useLocalStorageState } from '@woocommerce/base-hooks';

/**
 * localStorage key for the storefront banner's dismissed extensions. Kept
 * distinct from the editor sidebar notice's key so the two surfaces don't share
 * (and overwrite) each other's storage.
 */
export const DISMISSED_INCOMPATIBLE_EXTENSIONS_FRONTEND_STORAGE_KEY =
	'wc-blocks_dismissed_incompatible_extensions_notices_frontend';

/**
 * The key both surfaces shared before the storefront banner moved to its own.
 * Still owned and written by the editor sidebar notice; the storefront only
 * ever reads it, to carry over dismissals made before the rename.
 */
export const DISMISSED_INCOMPATIBLE_EXTENSIONS_STORAGE_KEY =
	'wc-blocks_dismissed_incompatible_extensions_notices';

/**
 * Reads the slugs the merchant acknowledged on the storefront while both
 * surfaces shared one key.
 *
 * The stored value can hold either surface's shape, and often both at once: the
 * storefront wrote bare slug strings, while the editor appends
 * `{ [blockName]: slugs }` objects without discarding what it finds. Only the
 * strings belong to the storefront banner, so the objects are filtered out
 * rather than treated as a reason to skip the whole value.
 */
const readSlugsDismissedBeforeRename = (): string[] => {
	try {
		const stored = window.localStorage.getItem(
			DISMISSED_INCOMPATIBLE_EXTENSIONS_STORAGE_KEY
		);
		if ( ! stored ) {
			return [];
		}
		const parsed = JSON.parse( stored );
		return Array.isArray( parsed )
			? parsed.filter(
					( entry ): entry is string => typeof entry === 'string'
			  )
			: [];
	} catch {
		// A value we can't read is not a dismissal we can honour; showing the
		// banner is the safe fallback.
		return [];
	}
};

// Whether every item in `subset` is also present in `superset`.
const isSubsetOf = ( subset: string[], superset: string[] ): boolean =>
	subset.every( ( item ) => superset.includes( item ) );

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
	// Seeding the initial value migrates pre-rename dismissals in one shot: the
	// hook only falls back to it when the storefront key is genuinely absent,
	// and writes the key itself on mount. Memoised because the argument is
	// evaluated on every render even though only the first one consumes it.
	const slugsDismissedBeforeRename = useMemo(
		readSlugsDismissedBeforeRename,
		[]
	);

	const [ dismissedSlugs, setDismissedSlugs ] = useLocalStorageState<
		string[]
	>(
		DISMISSED_INCOMPATIBLE_EXTENSIONS_FRONTEND_STORAGE_KEY,
		slugsDismissedBeforeRename
	);

	const { extensions, slugs } = getIncompatibleExtensions();
	const count = slugs.length;

	// Stay dismissed while every currently-incompatible extension has already
	// been acknowledged; deactivating one keeps it dismissed, while a new,
	// never-acknowledged extension brings the notice back.
	const isDismissedAndUpToDate = isSubsetOf( slugs, dismissedSlugs );

	const shouldShow =
		CURRENT_USER_IS_ADMIN && count > 0 && ! isDismissedAndUpToDate;

	if ( ! shouldShow ) {
		return null;
	}

	const dismissNotice = () => {
		// Record the union of everything acknowledged so far, so that later
		// reactivating a previously dismissed extension doesn't resurface it.
		setDismissedSlugs( [ ...new Set( [ ...dismissedSlugs, ...slugs ] ) ] );
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
