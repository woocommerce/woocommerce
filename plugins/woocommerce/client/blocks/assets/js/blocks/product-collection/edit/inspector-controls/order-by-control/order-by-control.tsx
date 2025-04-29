/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	SelectControl,
	// @ts-expect-error Using experimental features
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanelItem as ToolsPanelItem,
} from '@wordpress/components';

const OrderByControl = ( {
	hasValue = () => true,
	orderOptions,
	onChange,
	onDeselect = () => void 0,
	selectedValue,
	label,
	help,
}: {
	hasValue?: () => boolean;
	orderOptions: { value: string; label: string }[];
	onChange: ( value: string ) => void;
	onDeselect?: () => void;
	selectedValue: string;
	label?: string;
	help?: string;
} ) => {
	return (
		<ToolsPanelItem
			label={ label || __( 'Order by', 'woocommerce' ) }
			hasValue={ hasValue }
			isShownByDefault
			onDeselect={ onDeselect }
			resetAllFilter={ onDeselect }
		>
			<SelectControl
				value={ selectedValue }
				options={ orderOptions }
				label={ label || __( 'Order by', 'woocommerce' ) }
				onChange={ onChange }
				help={ help }
			/>
		</ToolsPanelItem>
	);
};

export default OrderByControl;
