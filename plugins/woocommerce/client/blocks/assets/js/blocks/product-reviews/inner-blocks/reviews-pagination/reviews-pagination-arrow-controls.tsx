/**
 * External dependencies
 */
import { __, _x } from '@wordpress/i18n';
import {
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControl as ToggleGroupControl,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
} from '@wordpress/components';

export function ReviewsPaginationArrowControls( {
	value,
	onChange,
}: {
	value: string | number | undefined;
	onChange: ( value: string | number | undefined ) => void;
} ) {
	return (
		<ToggleGroupControl
			__next40pxDefaultSize
			__nextHasNoMarginBottom
			label={ __( 'Arrow', 'woocommerce' ) }
			value={ value }
			onChange={ onChange }
			help={ __(
				'A decorative arrow appended to the next and previous product reviews link.',
				'woocommerce'
			) }
			isBlock
		>
			<ToggleGroupControlOption
				value="none"
				label={ _x(
					'None',
					'Arrow option for Product Reviews Pagination Next/Previous blocks',
					'woocommerce'
				) }
			/>
			<ToggleGroupControlOption
				value="arrow"
				label={ _x(
					'Arrow',
					'Arrow option for Product Reviews Pagination Next/Previous blocks',
					'woocommerce'
				) }
			/>
			<ToggleGroupControlOption
				value="chevron"
				label={ _x(
					'Chevron',
					'Arrow option for Product Reviews Pagination Next/Previous blocks',
					'woocommerce'
				) }
			/>
		</ToggleGroupControl>
	);
}
