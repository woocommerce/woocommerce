/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import clsx from 'clsx';
import { decodeHtmlEntities } from '@woocommerce/utils';
import { Disabled } from '@wordpress/components';
import { useBlockProps } from '@wordpress/block-editor';
import type { BlockEditProps } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import type { SelectableItemsBlockContext } from '../../../../types/type-defs/selectable-items';
import type { FilterItemFields } from '../../types';
import './editor.scss';
import './style.scss';

export type BlockAttributes = Record< string, never >;

export type EditProps = BlockEditProps< BlockAttributes > & {
	context: SelectableItemsBlockContext< FilterItemFields >;
};

function getOptionLabel( item: {
	label: string | unknown;
	ariaLabel?: string;
} ): string {
	if (
		typeof item.ariaLabel === 'string' &&
		item.ariaLabel.trim().length > 0
	) {
		return item.ariaLabel;
	}
	if ( typeof item.label === 'string' ) {
		return decodeHtmlEntities( item.label );
	}
	return '';
}

const Edit = ( props: EditProps ): JSX.Element => {
	const { context } = props;
	const selectableItems = context?.woocommerceSelectableItems ?? {};
	const isLoading = selectableItems.isLoading ?? false;
	const items = Array.isArray( selectableItems.items )
		? selectableItems.items
		: [];
	const groupLabel = selectableItems.groupLabel ?? '';

	const blockProps = useBlockProps( {
		className: clsx( 'wc-block-product-filter-dropdown', {
			'is-loading': isLoading,
		} ),
	} );

	return (
		<div { ...blockProps }>
			<Disabled>
				<fieldset className="wc-block-product-filter-dropdown__fieldset">
					{ groupLabel ? (
						<legend className="screen-reader-text">
							{ groupLabel }
						</legend>
					) : null }
					{ isLoading ? (
						<div className="wc-block-product-filter-dropdown__skeleton">
							<div className="wc-block-product-filter-dropdown__skeleton-option" />
						</div>
					) : (
						<select
							className="wc-block-product-filter-dropdown__select"
							aria-label={
								groupLabel ||
								__( 'Filter options', 'woocommerce' )
							}
							disabled
							value=""
						>
							<option value="">
								{ groupLabel
									? sprintf(
											/* translators: %s: Attribute or filter type label. */
											__( 'Select a %s', 'woocommerce' ),
											groupLabel
									  )
									: __( 'Select an option', 'woocommerce' ) }
							</option>
							{ items.map( ( item, index ) => (
								<option
									key={ index }
									value={ item.value }
									disabled={ !! item.disabled }
								>
									{ getOptionLabel( item ) }
									{ item.count !== undefined
										? ` (${ item.count })`
										: '' }
								</option>
							) ) }
						</select>
					) }
				</fieldset>
			</Disabled>
		</div>
	);
};

export default Edit;
