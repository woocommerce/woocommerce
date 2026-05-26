/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	CheckboxControl,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanel as ToolsPanel,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanelItem as ToolsPanelItem,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { QueryControlProps, RelatedBy, CoreFilterNames } from '../../types';

const RelatedByControl = ( {
	query,
	setQueryAttribute,
	trackInteraction,
}: QueryControlProps ) => {
	const relatedBy = query?.relatedBy as RelatedBy;

	const handleRelatedByChange = (
		value: boolean,
		type: 'categories' | 'tags'
	) => {
		const newRelatedBy = {
			...relatedBy,
			[ type ]: value,
		};

		setQueryAttribute( {
			relatedBy: newRelatedBy,
		} );

		trackInteraction( CoreFilterNames.RELATED_BY );
	};

	return (
		<ToolsPanel
			label={ __( 'Related by', 'woocommerce' ) }
			className="wc-block-editor-product-collection-inspector-controls__relate-by"
			resetAll={ () => {
				setQueryAttribute( {
					relatedBy: { categories: true, tags: true },
				} );
				trackInteraction( CoreFilterNames.RELATED_BY );
			} }
		>
			<ToolsPanelItem
				label={ __( 'Categories', 'woocommerce' ) }
				hasValue={ () => relatedBy?.categories !== true }
				onDeselect={ () => handleRelatedByChange( true, 'categories' ) }
				isShownByDefault
			>
				<CheckboxControl
					__nextHasNoMarginBottom
					label={ __( 'Categories', 'woocommerce' ) }
					checked={ relatedBy?.categories }
					onChange={ ( value ) => {
						handleRelatedByChange( value, 'categories' );
					} }
				/>
			</ToolsPanelItem>
			<ToolsPanelItem
				label={ __( 'Tags', 'woocommerce' ) }
				hasValue={ () => relatedBy?.tags !== true }
				onDeselect={ () => handleRelatedByChange( true, 'tags' ) }
				isShownByDefault
			>
				<CheckboxControl
					__nextHasNoMarginBottom
					label={ __( 'Tags', 'woocommerce' ) }
					checked={ relatedBy?.tags }
					onChange={ ( value ) => {
						handleRelatedByChange( value, 'tags' );
					} }
				/>
			</ToolsPanelItem>
		</ToolsPanel>
	);
};

export default RelatedByControl;
