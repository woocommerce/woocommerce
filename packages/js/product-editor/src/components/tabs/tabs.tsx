/**
 * External dependencies
 */
import { Slot } from '@wordpress/components';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { TABS_SLOT_NAME } from './constants';

type TabsProps = {
	onChange: ( tabId: string ) => void;
};

export type TabsFillProps = {
	onClick: ( tabId: string ) => void;
};

export function Tabs( { onChange = () => {} }: TabsProps ) {
	function onClick( tabId: string ) {
		onChange( tabId );
	}

	return (
		<div className="woocommerce-product-tabs">
			<Slot
				fillProps={
					{
						onClick,
					} as TabsFillProps
				}
				name={ TABS_SLOT_NAME }
			/>
		</div>
	);
}
