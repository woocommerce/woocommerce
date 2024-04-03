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
import { LayoutOptions, TemplateLayoutControlProps } from '../../types';
import { getDefaultTemplateLayout } from '../../constants';

const columnsLabel = __( 'Columns', 'woocommerce' );

const ColumnsControl = ( props: TemplateLayoutControlProps ) => {
	const { templateLayout } = props;
	if ( templateLayout.type !== LayoutOptions.GRID ) {
		return null;
	}
	const columnCount = templateLayout.columnCount;
	const hasDefaultColumnCount =
		getDefaultTemplateLayout().columnCount === columnCount;

	const onPanelDeselect = () => {
		props.setAttributes( {
			templateLayout,
		} );
	};

	const onColumnsChange = ( value: number ) =>
		props.setAttributes( {
			templateLayout: {
				...templateLayout,
				columnCount: value,
			},
		} );

	return (
		<>
			<ToolsPanelItem
				label={ columnsLabel }
				hasValue={ () => hasDefaultColumnCount }
				isShownByDefault
				onDeselect={ onPanelDeselect }
			>
				<RangeControl
					label={ columnsLabel }
					onChange={ onColumnsChange }
					value={ templateLayout.columnCount }
					min={ 2 }
					max={ Math.max( 6, templateLayout.columnCount ) }
				/>
			</ToolsPanelItem>
		</>
	);
};

export default ColumnsControl;
