/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { isSiteEditorPage } from '@woocommerce/utils';
import { usePrevious } from '@woocommerce/base-hooks';
import { select } from '@wordpress/data';
import { useMemo } from '@wordpress/element';
import {
	ToggleControl,
	// @ts-expect-error Using experimental features
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanelItem as ToolsPanelItem,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { ProductCollectionQuery } from '../types';
import { DEFAULT_QUERY } from '../constants';
import { getDefaultValueOfInheritQueryFromTemplate } from '../utils';

const label = __(
	'Inherit query from template',
	'woo-gutenberg-products-block'
);

interface InheritQueryControlProps {
	setQueryAttribute: ( value: Partial< ProductCollectionQuery > ) => void;
	query: ProductCollectionQuery | undefined;
}

const InheritQueryControl = ( {
	setQueryAttribute,
	query,
}: InheritQueryControlProps ) => {
	const inherit = query?.inherit;
	const editSiteStore = select( 'core/edit-site' );

	const queryObjectBeforeInheritEnabled = usePrevious(
		query,
		( value?: ProductCollectionQuery ) => {
			return value?.inherit === false;
		}
	);

	const defaultValue = useMemo(
		() => getDefaultValueOfInheritQueryFromTemplate(),
		[]
	);

	// Hide the control if not in site editor.
	const isSiteEditor = isSiteEditorPage( editSiteStore );
	if ( ! isSiteEditor ) {
		return null;
	}

	return (
		<ToolsPanelItem
			label={ label }
			hasValue={ () => inherit !== defaultValue }
			isShownByDefault
			onDeselect={ () => {
				setQueryAttribute( {
					inherit: null,
				} );
			} }
		>
			<ToggleControl
				className="wc-block-product-collection__inherit-query-control"
				label={ label }
				help={ __(
					'Toggle to use the global query context that is set with the current template, such as an archive or search. Disable to customize the settings independently.',
					'woo-gutenberg-products-block'
				) }
				checked={ !! inherit }
				onChange={ ( newInherit ) => {
					if ( newInherit ) {
						// If the inherit is enabled, we want to reset the query to the default.
						setQueryAttribute( {
							...DEFAULT_QUERY,
							inherit: newInherit,
						} );
					} else {
						// If the inherit is disabled, we want to reset the query to the previous query before the inherit was enabled.
						setQueryAttribute( {
							...DEFAULT_QUERY,
							...queryObjectBeforeInheritEnabled,
							inherit: newInherit,
						} );
					}
				} }
			/>
		</ToolsPanelItem>
	);
};

export default InheritQueryControl;
