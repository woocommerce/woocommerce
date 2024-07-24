/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
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
import {
	CoreFilterNames,
	type ProductCollectionQuery,
	type QueryControlProps,
} from '../../types';
import { DEFAULT_QUERY } from '../../constants';
import {
	getDefaultValueOfInherit,
	getDefaultValueOfFilterable,
} from '../../utils';

const label = __( 'Sync with current query', 'woocommerce' );

const productArchiveHelpText = __(
	'Enable to adjust the displayed products based on the current template and any applied filters.',
	'woocommerce'
);

const productsByCategoryHelpText = __(
	'Enable to adjust the displayed products based on the current category and any applied filters.',
	'woocommerce'
);

const productsByTagHelpText = __(
	'Enable to adjust the displayed products based on the current tag and any applied filters.',
	'woocommerce'
);

const productsByAttributeHelpText = __(
	'Enable to adjust the displayed products based on the current attribute and any applied filters.',
	'woocommerce'
);

const searchResultsHelpText = __(
	'Enable to adjust the displayed products based on the current search and any applied filters.',
	'woocommerce'
);

const filterableHelpText = __(
	'Adjust the displayed products depending on the current template and any applied query filters.',
	'woocommerce'
);

const getHelpTextForTemplate = ( templateId: string ): string => {
	if ( templateId.includes( '//taxonomy-product_cat' ) ) {
		return productsByCategoryHelpText;
	}
	if ( templateId.includes( '//taxonomy-product_tag' ) ) {
		return productsByTagHelpText;
	}
	if ( templateId.includes( '//taxonomy-product_attribute' ) ) {
		return productsByAttributeHelpText;
	}
	if ( templateId.includes( '//product-search-results' ) ) {
		return searchResultsHelpText;
	}
	return productArchiveHelpText;
};

const InheritQueryControl = ( {
	setQueryAttribute,
	trackInteraction,
	query,
}: QueryControlProps ) => {
	const inherit = query?.inherit;
	const editSiteStore = select( 'core/edit-site' );

	const queryObjectBeforeInheritEnabled = usePrevious(
		query,
		( value?: ProductCollectionQuery ) => {
			return value?.inherit === false;
		}
	);

	const defaultValue = useMemo( () => getDefaultValueOfInherit(), [] );

	const currentTemplateId = editSiteStore.getEditedPostId() as string;
	const helpText = getHelpTextForTemplate( currentTemplateId );

	return (
		<ToolsPanelItem
			label={ label }
			hasValue={ () => inherit !== defaultValue }
			isShownByDefault
			onDeselect={ () => {
				setQueryAttribute( {
					inherit: defaultValue,
				} );
				trackInteraction( CoreFilterNames.INHERIT );
			} }
		>
			<ToggleControl
				className="wc-block-product-collection__inherit-query-control"
				label={ label }
				help={ helpText }
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
					trackInteraction( CoreFilterNames.INHERIT );
				} }
			/>
		</ToolsPanelItem>
	);
};

const FilterableControl = ( {
	setQueryAttribute,
	trackInteraction,
	query,
}: QueryControlProps ) => {
	const filterable = query?.filterable;

	const defaultValue = useMemo( () => getDefaultValueOfFilterable(), [] );

	return (
		<ToolsPanelItem
			label={ label }
			hasValue={ () => filterable !== defaultValue }
			isShownByDefault
			onDeselect={ () => {
				setQueryAttribute( {
					filterable: defaultValue,
				} );
				trackInteraction( CoreFilterNames.FILTERABLE );
			} }
		>
			<ToggleControl
				className="wc-block-product-collection__inherit-query-control"
				label={ label }
				help={ filterableHelpText }
				checked={ !! filterable }
				onChange={ ( value ) => {
					setQueryAttribute( {
						filterable: value,
					} );
					trackInteraction( CoreFilterNames.FILTERABLE );
				} }
			/>
		</ToolsPanelItem>
	);
};

export { FilterableControl, InheritQueryControl };
