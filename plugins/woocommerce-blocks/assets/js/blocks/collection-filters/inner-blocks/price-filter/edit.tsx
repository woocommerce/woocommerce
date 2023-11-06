/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { useCollectionData } from '@woocommerce/base-context/hooks';
import { Disabled } from '@wordpress/components';
import FilterResetButton from '@woocommerce/base-components/filter-reset-button';
import { formatQuery } from '@woocommerce/blocks/collection-filters/utils';

/**
 * Internal dependencies
 */
import { getFormattedPrice } from './utils';
import { EditProps } from './types';
import { PriceSlider } from './price-slider';

const Edit = ( props: EditProps ) => {
	const blockProps = useBlockProps();
	const { query } = props.context;
	const { results } = useCollectionData( {
		queryPrices: true,
		isEditor: true,
		queryState: formatQuery( query ),
	} );

	return (
		<div { ...blockProps }>
			<Disabled>
				<div className="controls">
					<PriceSlider
						{ ...props }
						collectionData={ getFormattedPrice( results ) }
					/>
				</div>
				<div className="actions">
					<FilterResetButton onClick={ () => false } />
				</div>
			</Disabled>
		</div>
	);
};

export default Edit;
