/**
 * External dependencies
 */
import { useMemo } from '@wordpress/element';
import classnames from 'classnames';
import { useBlockProps } from '@wordpress/block-editor';
import { Disabled } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { CheckboxList } from '@woocommerce/blocks-components';
import Label from '@woocommerce/base-components/filter-element-label';
import type { BlockEditProps } from '@wordpress/blocks';
import { getSetting } from '@woocommerce/settings';
import { useCollectionData } from '@woocommerce/base-context/hooks';

/**
 * Internal dependencies
 */
import { BlockProps } from './types';
import { Inspector } from './components/inspector';
import { PreviewDropdown } from '../components/preview-dropdown';

type CollectionData = {
	stock_status_counts: StockStatusCount[];
};

type StockStatusCount = {
	status: string;
	count: number;
};

const Edit = ( props: BlockEditProps< BlockProps > ) => {
	const blockProps = useBlockProps( {
		className: classnames(
			'wc-block-stock-filter',
			props.attributes.className
		),
	} );

	const { showCounts, displayStyle } = props.attributes;
	const stockStatusOptions: Record< string, string > = getSetting(
		'stockStatusOptions',
		{}
	);

	const { results: filteredCounts } = useCollectionData( {
		queryStock: true,
		queryState: {},
		isEditor: true,
	} );

	const listOptions = useMemo( () => {
		return Object.entries( stockStatusOptions )
			.map( ( [ key, value ] ) => {
				const count = (
					filteredCounts as unknown as CollectionData
				 )?.stock_status_counts?.find(
					( item: StockStatusCount ) => item.status === key
				)?.count;

				return {
					value: key,
					label: (
						<Label
							name={ value }
							count={
								showCounts && count ? Number( count ) : null
							}
						/>
					),
					count: count || 0,
				};
			} )
			.filter( ( item ) => item.count > 0 );
	}, [ stockStatusOptions, filteredCounts, showCounts ] );

	return (
		<>
			{
				<div { ...blockProps }>
					<Inspector { ...props } />
					<Disabled>
						<div
							className={ classnames( `style-${ displayStyle }`, {
								'is-loading': false,
							} ) }
						>
							{ displayStyle === 'dropdown' ? (
								<>
									<PreviewDropdown
										placeholder={
											props.attributes.selectType ===
											'single'
												? __(
														'Select stock status',
														'woocommerce'
												  )
												: __(
														'Select stock statuses',
														'woocommerce'
												  )
										}
									/>
								</>
							) : (
								<CheckboxList
									className={ 'wc-block-stock-filter-list' }
									options={ listOptions }
									checked={ [] }
									onChange={ () => {
										// noop
									} }
									isLoading={ false }
									isDisabled={ true }
								/>
							) }
						</div>
					</Disabled>
				</div>
			}
		</>
	);
};

export default Edit;
