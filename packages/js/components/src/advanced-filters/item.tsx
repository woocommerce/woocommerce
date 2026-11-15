/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { Button } from '@wordpress/components';
import CrossSmallIcon from 'gridicons/dist/cross-small';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import SelectFilter from './select-filter';
import SearchFilter from './search-filter';
import NumberFilter from './number-filter';
import DateFilter from './date-filter';
import AttributeFilter from './attribute-filter';
import type {
	ActiveFilter,
	AdvancedFilterConfig,
	Currency,
	FilterConfig,
	OnFilterChange,
	Query,
} from './types';

export type AdvancedFilterItemProps = {
	config: AdvancedFilterConfig;
	currency: Currency;
	filter: ActiveFilter;
	isEnglish: boolean;
	onFilterChange: OnFilterChange;
	query: Query;
	removeFilter: () => void;
};

const componentMap = {
	Currency: NumberFilter,
	Date: DateFilter,
	Number: NumberFilter,
	ProductAttribute: AttributeFilter,
	Search: SearchFilter,
	SelectControl: SelectFilter,
};

const isKnownComponent = (
	component: string
): component is keyof typeof componentMap =>
	componentMap.hasOwnProperty( component );

const AdvancedFilterItem = ( props: AdvancedFilterItemProps ) => {
	const {
		config,
		currency,
		filter: filterValue,
		isEnglish,
		onFilterChange,
		query,
		removeFilter,
	} = props;
	const { key } = filterValue;
	let filterConfig: FilterConfig = config.filters[ key ];
	const { input, labels } = filterConfig;

	if ( ! isKnownComponent( input.component ) ) {
		return null;
	}

	if ( input.component === 'Currency' ) {
		filterConfig = {
			...filterConfig,
			...{
				input: {
					type: 'currency',
					component: 'Currency',
				},
			},
		};
	}

	const FilterComponent = componentMap[ input.component ];

	return (
		<li className="woocommerce-filters-advanced__list-item">
			<FilterComponent
				className="woocommerce-filters-advanced__fieldset-item"
				currency={ currency }
				filter={ filterValue }
				config={ filterConfig }
				onFilterChange={ onFilterChange }
				isEnglish={ isEnglish }
				query={ query }
			/>
			<Button
				className={ clsx(
					'woocommerce-filters-advanced__line-item',
					'woocommerce-filters-advanced__remove'
				) }
				label={ labels.remove }
				onClick={ removeFilter }
			>
				<CrossSmallIcon />
			</Button>
		</li>
	);
};

export default AdvancedFilterItem;
