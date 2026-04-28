/**
 * External dependencies
 */
import {
	BaseControl,
	FlexBlock,
	FormToggle,
	__experimentalHStack as HStack,
} from '@wordpress/components';

import { useInstanceId } from '@wordpress/compose';

import { useState } from '@wordpress/element';

import { __ } from '@wordpress/i18n';

import type { Field } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../types';

import { getLocalDefaultSaleStart } from '../price/utils';

const fieldDefinition = {
	type: 'boolean',
	label: __( 'Schedule sale', 'woocommerce' ),
	enableSorting: false,
	enableHiding: false,
	filterBy: false,
} satisfies Partial< Field< ProductEntityRecord > >;

export const fieldExtensions: Partial< Field< ProductEntityRecord > > = {
	...fieldDefinition,
	isVisible: ( item ) => {
		return !! item.on_sale || !! item.sale_price;
	},
	Edit: ( { data, onChange, field } ) => {
		const toggleId = useInstanceId( FormToggle, 'schedule-sale-toggle' );
		const [ tempDateOnSaleFrom, setTempDateOnSaleFrom ] = useState(
			data.date_on_sale_from || ''
		);
		const [ tempDateOnSaleTo, setTempDateOnSaleTo ] = useState(
			data.date_on_sale_to || ''
		);
		const checked = !! data.date_on_sale_to || !! data.date_on_sale_from;
		return (
			<BaseControl className="components-toggle-control">
				<HStack justify="flex-start" spacing={ 2 }>
					<FormToggle
						id={ toggleId }
						checked={ checked }
						onChange={ () => {
							if ( checked ) {
								setTempDateOnSaleFrom(
									data.date_on_sale_from || ''
								);
								setTempDateOnSaleTo(
									data.date_on_sale_to || ''
								);
								onChange( {
									date_on_sale_from: '',
									date_on_sale_to: '',
								} );
							} else {
								let dateOnSaleFrom =
									data.date_on_sale_from ||
									tempDateOnSaleFrom;
								const dateOnSaleTo =
									data.date_on_sale_to || tempDateOnSaleTo;

								if ( ! dateOnSaleFrom && ! dateOnSaleTo ) {
									dateOnSaleFrom = getLocalDefaultSaleStart();
								}

								onChange( {
									date_on_sale_from: dateOnSaleFrom,
									date_on_sale_to: dateOnSaleTo,
								} );
							}
						} }
					/>
					<FlexBlock
						as="label"
						htmlFor={ toggleId }
						className="components-toggle-control__label"
					>
						{ field.label }
					</FlexBlock>
				</HStack>
			</BaseControl>
		);
	},
};
