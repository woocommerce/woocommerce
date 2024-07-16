/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	ToggleControl,
	// @ts-expect-error Using experimental features
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanelItem as ToolsPanelItem,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import {
	CoreFilterNames,
	type TrackInteraction,
	type ProductCollectionSetAttributes,
} from '../../types';
import { DEFAULT_ATTRIBUTES } from '../../constants';

const label = __( 'Sync with product filters', 'woocommerce' );

const helpText = __(
	'Adjust the displayed products depending on any applied query filters.',
	'woocommerce'
);

const SyncWithFiltersControl = ( {
	setAttributes,
	trackInteraction,
	syncWithFilters,
}: {
	setAttributes: ProductCollectionSetAttributes;
	trackInteraction: TrackInteraction;
	syncWithFilters: boolean;
} ) => {
	return (
		<ToolsPanelItem
			label={ label }
			hasValue={ () =>
				syncWithFilters !== DEFAULT_ATTRIBUTES.syncWithFilters
			}
			isShownByDefault
			onDeselect={ () => {
				setAttributes( {
					syncWithFilters: false,
				} );
				trackInteraction( CoreFilterNames.SYNC_WITH_FILTERS );
			} }
		>
			<ToggleControl
				className="wc-block-product-collection__sync-with-filters-control"
				label={ label }
				help={ helpText }
				checked={ !! syncWithFilters }
				onChange={ ( value ) => {
					setAttributes( {
						syncWithFilters: value,
					} );
					trackInteraction( CoreFilterNames.SYNC_WITH_FILTERS );
				} }
			/>
		</ToolsPanelItem>
	);
};

export default SyncWithFiltersControl;
