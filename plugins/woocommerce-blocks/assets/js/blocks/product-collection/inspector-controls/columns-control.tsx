/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	RangeControl,
	// @ts-expect-error Using experimental features
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanelItem as ToolsPanelItem,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { DisplayLayoutControlProps } from '../types';
import { getDefaultDisplayLayout } from '../constants';

const ColumnsControl = ( props: DisplayLayoutControlProps ) => {
	const { type, columns } = props.displayLayout;
	const showColumnsControl = type === 'flex';

	const defaultLayout = getDefaultDisplayLayout();

	return showColumnsControl ? (
		<ToolsPanelItem
			label={ __( 'Columns', 'woo-gutenberg-products-block' ) }
			hasValue={ () =>
				defaultLayout?.columns !== columns ||
				defaultLayout?.type !== type
			}
			isShownByDefault
			onDeselect={ () => {
				props.setAttributes( {
					displayLayout: defaultLayout,
				} );
			} }
		>
			<RangeControl
				label={ __( 'Columns', 'woo-gutenberg-products-block' ) }
				value={ columns }
				onChange={ ( value: number ) =>
					props.setAttributes( {
						displayLayout: {
							...props.displayLayout,
							columns: value,
						},
					} )
				}
				min={ 2 }
				max={ Math.max( 6, columns ) }
			/>
		</ToolsPanelItem>
	) : null;
};

export default ColumnsControl;
