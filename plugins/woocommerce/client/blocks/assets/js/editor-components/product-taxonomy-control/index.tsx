/**
 * External dependencies
 */
import clsx from 'clsx';
import { SelectControl } from '@wordpress/components';
import { __, _n, sprintf } from '@wordpress/i18n';
import ErrorMessage from '@woocommerce/editor-components/error-placeholder/error-message';
import {
	SearchListControl,
	SearchListItem,
} from '@woocommerce/editor-components/search-list-control';
import type { ErrorObject } from '@woocommerce/editor-components/error-placeholder';
import type {
	RenderItemArgs,
	SearchListControlProps,
	SearchListItem as SearchListItemProps,
} from '@woocommerce/editor-components/search-list-control/types';

/**
 * Internal dependencies
 */
import './style.scss';

interface OperatorConfig {
	className?: string;
	labels: {
		all: string;
		any: string;
		help: string;
	};
	onChange: ( operator: string ) => void;
	/**
	 * Number of selected taxonomy IDs, including IDs whose list items have not
	 * been resolved yet.
	 */
	selectedCount: number;
	value: string;
}

interface ProductTaxonomyControlProps< T extends object = object >
	extends SearchListControlProps< T > {
	countType?: 'product' | 'review';
	error?: ErrorObject | null;
	itemClassName?: string;
	operator?: OperatorConfig | undefined;
}

interface CountDetails {
	count?: number;
	review_count?: number;
}

const getItemCount = (
	item: SearchListItemProps,
	countType: 'product' | 'review'
) => {
	const details = item.details as CountDetails | undefined;

	return countType === 'review'
		? details?.review_count ?? 0
		: details?.count ?? item.count ?? 0;
};

const getCountLabel = ( count: number, countType: 'product' | 'review' ) =>
	countType === 'review'
		? sprintf(
				/* translators: %d is the count of reviews. */
				_n( '%d review', '%d reviews', count, 'woocommerce' ),
				count
		  )
		: sprintf(
				/* translators: %d is the count of products. */
				_n( '%d product', '%d products', count, 'woocommerce' ),
				count
		  );

const getAriaLabel = (
	item: SearchListItemProps,
	count: number,
	countType: 'product' | 'review'
) => {
	const name = item.breadcrumbs.length
		? `${ item.breadcrumbs.join( ', ' ) }, ${ item.name }`
		: item.name;

	return countType === 'review'
		? sprintf(
				/* translators: %1$s is the item name, %2$d is the count of reviews for the item. */
				_n(
					'%1$s, has %2$d review',
					'%1$s, has %2$d reviews',
					count,
					'woocommerce'
				),
				name,
				count
		  )
		: sprintf(
				/* translators: %1$s is the item name, %2$d is the count of products for the item. */
				_n(
					'%1$s, has %2$d product',
					'%1$s, has %2$d products',
					count,
					'woocommerce'
				),
				name,
				count
		  );
};

const ProductTaxonomyControl = < T extends object = object >( {
	countType = 'product',
	error = null,
	itemClassName,
	operator,
	renderItem,
	...searchListProps
}: ProductTaxonomyControlProps< T > ): JSX.Element => {
	if ( error ) {
		return <ErrorMessage error={ error } />;
	}

	const renderTaxonomyItem = ( args: RenderItemArgs< T > ) => {
		const { item, search, depth = 0 } = args;
		const count = getItemCount( item, countType );

		return (
			<SearchListItem
				{ ...args }
				className={ clsx( itemClassName, 'has-count', {
					'is-searching': search.length > 0,
					'is-skip-level': depth === 0 && item.parent !== 0,
				} ) }
				countLabel={ getCountLabel( count, countType ) }
				aria-label={ getAriaLabel( item, count, countType ) }
			/>
		);
	};

	return (
		<>
			<SearchListControl
				{ ...searchListProps }
				renderItem={ renderItem || renderTaxonomyItem }
			/>
			{ operator && (
				<div hidden={ operator.selectedCount < 2 }>
					<SelectControl< string >
						className={ clsx(
							'woocommerce-product-taxonomy-control__operator',
							operator.className
						) }
						label={ __(
							'Display products matching',
							'woocommerce'
						) }
						help={ operator.labels.help }
						value={ operator.value }
						onChange={ operator.onChange }
						options={ [
							{
								label: operator.labels.any,
								value: 'any',
							},
							{
								label: operator.labels.all,
								value: 'all',
							},
						] }
					/>
				</div>
			) }
		</>
	);
};

export default ProductTaxonomyControl;
