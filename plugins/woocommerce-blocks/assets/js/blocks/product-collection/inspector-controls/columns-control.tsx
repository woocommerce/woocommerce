/**
 * External dependencies
 */
import { RangeControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { BlockEditProps } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { ProductCollectionAttributes } from '../types';

const ColumnsControl = (
	props: BlockEditProps< ProductCollectionAttributes >
) => {
	const { type, columns } = props.attributes.displayLayout;
	const showColumnsControl = type === 'flex';

	return showColumnsControl ? (
		<RangeControl
			label={ __( 'Columns', 'woo-gutenberg-products-block' ) }
			value={ columns }
			onChange={ ( value: number ) =>
				props.setAttributes( {
					displayLayout: {
						...props.attributes.displayLayout,
						columns: value,
					},
				} )
			}
			min={ 2 }
			max={ Math.max( 6, columns ) }
		/>
	) : null;
};

export default ColumnsControl;
