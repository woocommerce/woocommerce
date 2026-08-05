/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { getSetting, CURRENT_USER_IS_ADMIN } from '@woocommerce/settings';
import NoticeBanner from '@woocommerce/base-components/notice-banner';
import { useLocalStorageState } from '@woocommerce/base-hooks';

const areArraysEqual = ( a: string[], b: string[] ): boolean => {
	if ( a.length !== b.length ) return false;
	const unique = new Set( [ ...a, ...b ] );
	return unique.size === a.length;
};

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
	const [ dismissedSlugs, setDismissedSlugs ] = useLocalStorageState<
		string[]
	>( 'wc-blocks_dismissed_incompatible_extensions_notices', [] );

	const { extensions, slugs } = getIncompatibleExtensions();
	const count = slugs.length;

	const isDismissedAndUpToDate = areArraysEqual( dismissedSlugs, slugs );

	const shouldShow =
		CURRENT_USER_IS_ADMIN && count > 0 && ! isDismissedAndUpToDate;

	if ( ! shouldShow ) {
		return null;
	}

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
