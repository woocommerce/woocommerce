/**
 * External dependencies
 */
import { Spinner } from '@wordpress/components';
import { SearchListItem } from '@woocommerce/editor-components/search-list-control';
import { RenderItemArgs } from '@woocommerce/editor-components/search-list-control/types';
import clsx from 'clsx';

interface ExpandableSearchListItemProps extends RenderItemArgs {
	isLoading: boolean;
}

const ExpandableSearchListItem = ( {
	className,
	item,
	isSelected,
	isLoading,
	onSelect,
	disabled,
	...rest
}: ExpandableSearchListItemProps ): JSX.Element => {
	return (
		<>
			<SearchListItem
				{ ...rest }
				key={ item.id }
				className={ className }
				isSelected={ isSelected }
				item={ item }
				onSelect={ onSelect }
				disabled={ disabled }
			/>
			{ isSelected && isLoading && (
				<div
					key="loading"
					className={ clsx(
						'woocommerce-search-list__item',
						'woocommerce-product-attributes__item',
						'depth-1',
						'is-loading',
						'is-not-active'
					) }
				>
					<Spinner />
				</div>
			) }
		</>
	);
};

export default ExpandableSearchListItem;
