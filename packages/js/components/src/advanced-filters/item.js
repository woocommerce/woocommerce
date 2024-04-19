/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { Button } from '@wordpress/components';
import CrossSmallIcon from 'gridicons/dist/cross-small';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import SelectFilter from './select-filter';
import SearchFilter from './search-filter';
import NumberFilter from './number-filter';
import DateFilter from './date-filter';
import AttributeFilter from './attribute-filter';
import OrderAttributionFilter from './order-attribution-filter';

const AdvancedFilterItem = ( props ) => {
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
	let filterConfig = config.filters[ key ];
	const { input, labels } = filterConfig;

	const componentMap = {
		Currency: NumberFilter,
		Date: DateFilter,
		Number: NumberFilter,
		ProductAttribute: AttributeFilter,
		OrderAttributionFilter,
		Search: SearchFilter,
		SelectControl: SelectFilter,
	};

	if ( ! componentMap.hasOwnProperty( input.component ) ) {
		return;
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
				className={ classnames(
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
