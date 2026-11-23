/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import clsx from 'clsx';
import { decodeHtmlEntities } from '@woocommerce/utils';
import { Disabled } from '@wordpress/components';
import { useRefEffect } from '@wordpress/compose';
import {
	useBlockProps,
	// @ts-expect-error - no types.
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalUseColorProps as useColorProps,
	// @ts-expect-error - no types.
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalUseBorderProps as useBorderProps,
} from '@wordpress/block-editor';
import type { BlockAttributes, BlockEditProps } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import type { SelectableItemsBlockContext } from '@woocommerce/types';
import { getSelectionSummary } from './utils';
import {
	getVisualAttributeTermStyle,
	isVisualAttributeTermEmpty,
} from '../../base/utils/visual-attribute-terms';
import type { VisualAttributeTerm } from '../../base/utils/visual-attribute-terms';
import { getClosestColor } from '../product-filters/utils/get-closest-color';
import './editor.scss';
import './style.scss';

export type EditProps = BlockEditProps< BlockAttributes > & {
	context: Partial<
		SelectableItemsBlockContext< {
			count?: number;
			visual?: VisualAttributeTerm;
		} >
	>;
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

const Edit = ( props: EditProps ) => {
	const { context, attributes, isSelected, clientId } = props;
	const selectableItems = context?.[ 'woocommerce/selectableItems' ];
	const isLoading = selectableItems?.isLoading ?? false;
	const items = Array.isArray( selectableItems?.items )
		? selectableItems.items
		: [];
	const isMultiple = selectableItems?.selectionMode === 'multiple';
	const borderProps = useBorderProps( attributes );
	const colorProps = useColorProps( attributes );
	const fieldProps = {
		className: clsx( borderProps.className, colorProps.className ),
		style: { ...borderProps.style, ...colorProps.style },
	};
	const backgroundRef = useRefEffect( ( node: HTMLDivElement ) => {
		const updateBackground = () => {
			const background = getClosestColor( node, 'backgroundColor' );
			if ( background ) {
				node.style.setProperty(
					'--wc-dropdown-background',
					background
				);
			}
		};
		const observer = new MutationObserver( updateBackground );
		for (
			let ancestor = node.parentElement;
			ancestor;
			ancestor = ancestor.parentElement
		) {
			observer.observe( ancestor, {
				attributes: true,
				attributeFilter: [ 'class', 'style' ],
			} );
		}
		updateBackground();
		return () => observer.disconnect();
	}, [] );

	const blockProps = useBlockProps( {
		ref: backgroundRef,
		className: clsx( 'wc-block-dropdown', {
			'is-loading': isLoading,
			'wc-block-dropdown--multiple': isMultiple,
		} ),
	} );

	if ( isMultiple ) {
		return (
			<div { ...blockProps }>
				<Disabled>
					<button
						type="button"
						className={ clsx(
							'wc-block-dropdown__trigger',
							fieldProps.className
						) }
						style={ {
							...fieldProps.style,
							...( isSelected && {
								borderBottomLeftRadius: 0,
								borderBottomRightRadius: 0,
							} ),
						} }
						aria-expanded={ isSelected }
					>
						<span className="wc-block-dropdown__summary">
							{ getSelectionSummary(
								items,
								__( 'Choose options', 'woocommerce' )
							) }
						</span>
						<span
							className="wc-block-dropdown__chevron"
							aria-hidden="true"
						/>
					</button>
					<div
						className={ clsx(
							'wc-block-dropdown__panel wc-block-product-filter-checkbox-list',
							fieldProps.className
						) }
						style={ {
							...fieldProps.style,
							borderTopWidth: 0,
							borderTopLeftRadius: 0,
							borderTopRightRadius: 0,
						} }
						hidden={ ! isSelected }
					>
						{ isLoading && (
							<div className="wc-block-dropdown__skeleton-option" />
						) }
						{ items
							.filter( ( item ) => ! item.hidden )
							.map( ( item ) => (
								<div
									className="wc-block-product-filter-checkbox-list__item"
									key={ item.value }
								>
									<label
										className="wc-block-product-filter-checkbox-list__label"
										htmlFor={ `${ clientId }-${ item.value }` }
									>
										<span className="wc-block-product-filter-checkbox-list__input-wrapper">
											<input
												id={ `${ clientId }-${ item.value }` }
												type="checkbox"
												className="wc-block-product-filter-checkbox-list__input"
												defaultChecked={
													!! item.selected
												}
												disabled={ !! item.disabled }
											/>
											<svg
												viewBox="0 0 10 8"
												fill="none"
												className="wc-block-product-filter-checkbox-list__mark"
												aria-hidden="true"
											>
												<path
													d="M9.25 1.19922L3.75 6.69922L1 3.94922"
													stroke="currentColor"
													strokeWidth="1.5"
													strokeLinecap="round"
													strokeLinejoin="round"
												/>
											</svg>
										</span>
										<span className="wc-block-product-filter-checkbox-list__text-wrapper">
											{ item.visual !== undefined && (
												<span
													className={ clsx(
														'wc-block-product-filter-checkbox-list__color-swatch',
														{
															'is-empty':
																isVisualAttributeTermEmpty(
																	item.visual
																),
														}
													) }
													style={ getVisualAttributeTermStyle(
														item.visual
													) }
													aria-hidden="true"
												/>
											) }
											<span className="wc-block-product-filter-checkbox-list__text">
												{ getOptionLabel( item ) }
											</span>
											{ item.count !== undefined && (
												<span className="wc-block-product-filter-checkbox-list__count">
													({ item.count })
												</span>
											) }
										</span>
									</label>
								</div>
							) ) }
					</div>
				</Disabled>
			</div>
		);
	}

	return (
		<div { ...blockProps }>
			<Disabled>
				<fieldset className="wc-block-dropdown__fieldset">
					<legend className="screen-reader-text">
						{ __( 'Choose an option', 'woocommerce' ) }
					</legend>
					{ isLoading ? (
						<div className="wc-block-dropdown__skeleton">
							<div className="wc-block-dropdown__skeleton-option" />
						</div>
					) : (
						<select
							className={ clsx(
								'wc-block-dropdown__select',
								fieldProps.className
							) }
							style={ fieldProps.style }
							aria-label={ selectableItems?.groupLabel }
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
