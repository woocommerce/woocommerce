/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { usePrevious } from '@woocommerce/base-hooks';
import { useMemo } from '@wordpress/element';
import {
	// @ts-expect-error Using experimental features
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanelItem as ToolsPanelItem,
	// @ts-expect-error Using experimental features
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControl as ToggleGroupControl,
	// @ts-expect-error Using experimental features
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import {
	CoreFilterNames,
	type ProductCollectionQuery,
	type QueryControlProps,
} from '../../types';
import { DEFAULT_QUERY } from '../../constants';
import { getDefaultValueOfInherit } from '../../utils';

const label = __( 'Query type', 'woocommerce' );
const defaultOptionLabel = __( 'Default', 'woocommerce' );
const customOptionLabel = __( 'Custom', 'woocommerce' );

const defaultInheritHelpText = __(
	'Display products based on the current template.',
	'woocommerce'
);
const customHelpText = __(
	'Show products based on specific criteria.',
	'woocommerce'
);

const InheritQueryControl = ( {
	setQueryAttribute,
	trackInteraction,
	query,
}: QueryControlProps ) => {
	const inherit = query?.inherit;

	const queryObjectBeforeInheritEnabled = usePrevious(
		query,
		( value?: ProductCollectionQuery ) => {
			return value?.inherit === false;
		}
	);

	const defaultValue = useMemo( () => getDefaultValueOfInherit(), [] );

	return (
		<ToolsPanelItem
			label={ label }
			hasValue={ () => inherit !== defaultValue }
			isShownByDefault
			onDeselect={ () => {
				setQueryAttribute( {
					inherit: defaultValue,
				} );
				trackInteraction( CoreFilterNames.INHERIT );
			} }
		>
			<ToggleGroupControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				className="wc-block-product-collection__inherit-query-control"
				isBlock
				label={ label }
				help={ inherit ? defaultInheritHelpText : customHelpText }
				value={ !! inherit ? 'default' : 'custom' }
				onChange={ ( value: 'default' | 'custom' ) => {
					if ( value === 'default' ) {
						// If the inherit is enabled, we want to reset the query to the default.
						setQueryAttribute( {
							...DEFAULT_QUERY,
							inherit: true,
						} );
					} else {
						// If the inherit is disabled, we want to reset the query to the previous query before the inherit was enabled.
						setQueryAttribute( {
							...DEFAULT_QUERY,
							...queryObjectBeforeInheritEnabled,
							inherit: false,
						} );
					}
					trackInteraction( CoreFilterNames.INHERIT );
				} }
			>
				<ToggleGroupControlOption
					value="default"
					label={ defaultOptionLabel }
				/>
				<ToggleGroupControlOption
					value="custom"
					label={ customOptionLabel }
				/>
			</ToggleGroupControl>
		</ToolsPanelItem>
	);
};

export { InheritQueryControl };
