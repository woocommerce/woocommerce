/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
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
	if ( typeof item.label === 'string' && item.label.trim().length > 0 ) {
		return decodeHtmlEntities( item.label.trim() );
	}
	if (
		typeof item.ariaLabel === 'string' &&
		item.ariaLabel.trim().length > 0
	) {
		return item.ariaLabel.trim();
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

	return (
		<div { ...blockProps }>
			<Disabled>
				<fieldset className="wc-block-product-filter-dropdown__fieldset">
					<legend className="screen-reader-text">
						{ __( 'Choose an option', 'woocommerce' ) }
					</legend>
					{ isLoading ? (
						<div className="wc-block-product-filter-dropdown__skeleton">
							<div className="wc-block-product-filter-dropdown__skeleton-option" />
						</div>
					) : (
						<select
							className="wc-block-product-filter-dropdown__select"
							aria-label={ selectableItems.groupLabel }
							disabled
						>
							<option value="">
								{ __( 'Choose an option', 'woocommerce' ) }
							</option>
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
