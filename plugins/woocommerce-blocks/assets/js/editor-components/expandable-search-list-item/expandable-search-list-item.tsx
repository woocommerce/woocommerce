/**
 * External dependencies
 */
import { SearchListItem } from '@woocommerce/editor-components/search-list-control';
import { Spinner } from '@wordpress/components';
import classNames from 'classnames';

interface SearchListItem {
	id: string;
}

interface ExpandableSearchListItemProps {
	className?: string;
	item: SearchListItem;
	isSelected: boolean;
	isLoading: boolean;
	onSelect: () => void;
	disabled: boolean;
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
				isSingle
				disabled={ disabled }
			/>
			{ isSelected && isLoading && (
				<div
					key="loading"
					className={ classNames(
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
