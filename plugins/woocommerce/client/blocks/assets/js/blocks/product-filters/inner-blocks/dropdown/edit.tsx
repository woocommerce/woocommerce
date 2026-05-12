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

	const blockProps = useBlockProps( {
		className: clsx( 'wc-block-product-filter-dropdown', {
			'is-loading': isLoading,
		} ),
	} );

	const label = selectableItems.groupLabel
		? sprintf(
				/* translators: %s: Attribute or filter type label. */
				__( 'Select %s', 'woocommerce' ),
				selectableItems.groupLabel
		  )
		: __( 'Select options', 'woocommerce' );

	return (
		<div { ...blockProps }>
			<Disabled>
				<fieldset className="wc-block-product-filter-dropdown__fieldset">
					<legend className="screen-reader-text">{ label }</legend>
					{ isLoading ? (
						<div className="wc-block-product-filter-dropdown__skeleton">
							<div className="wc-block-product-filter-dropdown__skeleton-option" />
						</div>
					) : (
						<select
							className="wc-block-product-filter-dropdown__select"
							aria-label={ label }
							disabled
							value=""
						>
							<option value="">{ label }</option>
							{ items.map( ( item, index ) => {
								const optionLabel = getOptionLabel( item );
								if ( ! optionLabel ) {
									return null;
								}
								return (
									<option
										key={ index }
										value={ item.value }
										disabled={ !! item.disabled }
									>
										{ optionLabel }
										{ item.count !== undefined
											? ` (${ item.count })`
											: '' }
									</option>
								);
							} ) }
						</select>
					) }
				</fieldset>
			</Disabled>
		</div>
	);
};

export default Edit;
