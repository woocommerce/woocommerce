/**
 * External dependencies
 */
import { ReactNode } from 'react';
import { Slot, Fill } from '@wordpress/components';
import {
	createElement,
	Children,
	Fragment,
	useEffect,
} from '@wordpress/element';
import {
	useSlotContext,
	SlotContextHelpersType,
} from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { createOrderedChildren, sortFillsByOrder } from '../../utils';
import { ProductFillLocationType } from '../woo-product-tab-item';

type WooProductFieldItemProps = {
	id: string;
	sections: ProductFillLocationType[];
	pluginId: string;
};

type WooProductFieldSlotProps = {
	section: string;
};

type WooProductFieldFillProps = {
	fieldName: string;
	sectionName: string;
	order: number;
	children?: ReactNode;
};

const DEFAULT_FIELD_ORDER = 20;

const WooProductFieldFill: React.FC< WooProductFieldFillProps > = ( {
	fieldName,
	sectionName,
	order,
	children,
} ) => {
	const { registerFill, getFillHelpers } = useSlotContext();

	const fieldId = `product_field/${ sectionName }/${ fieldName }`;

	useEffect( () => {
		registerFill( fieldId );
	}, [] );

	return (
		<Fill
			name={ `woocommerce_product_field_${ sectionName }` }
			key={ fieldId }
		>
			{ /* eslint-disable @typescript-eslint/ban-ts-comment */ }
			{
				// @ts-ignore It is okay to pass in a function as a render child of Fill
				( fillProps: Fill.Props ) =>
					createOrderedChildren<
						Fill.Props &
							SlotContextHelpersType & {
								sectionName: string;
							},
						{ _id: string }
					>(
						children,
						order,
						{
							sectionName,
							...fillProps,
							...getFillHelpers(),
						},
						{ _id: fieldId }
					)
			}
			{ /* eslint-enable @typescript-eslint/ban-ts-comment */ }
		</Fill>
	);
};

export const WooProductFieldItem: React.FC< WooProductFieldItemProps > & {
	Slot: React.FC< Slot.Props & WooProductFieldSlotProps >;
} = ( { children, sections, id } ) => {
	return (
		<>
			{ sections.map(
				( { name: sectionName, order = DEFAULT_FIELD_ORDER } ) => (
					<WooProductFieldFill
						fieldName={ id }
						sectionName={ sectionName }
						order={ order }
						key={ sectionName }
					>
						{ children }
					</WooProductFieldFill>
				)
			) }
		</>
	);
};

WooProductFieldItem.Slot = ( { fillProps, section } ) => {
	// eslint-disable-next-line react-hooks/rules-of-hooks
	const { filterRegisteredFills } = useSlotContext();

	return (
		<Slot
			name={ `woocommerce_product_field_${ section }` }
			fillProps={ fillProps }
		>
			{ ( fills ) => {
				if ( ! sortFillsByOrder ) {
					return null;
				}

				return Children.map(
					sortFillsByOrder( filterRegisteredFills( fills ) )?.props
						.children,
					( child ) => (
						<div className="woocommerce-product-form__field">
							{ child }
						</div>
					)
				);
			} }
		</Slot>
	);
};
