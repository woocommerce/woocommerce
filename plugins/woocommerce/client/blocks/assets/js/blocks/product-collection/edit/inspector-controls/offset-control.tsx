/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

import {
	// @ts-expect-error Using experimental features
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalNumberControl as NumberControl,
	// @ts-expect-error Using experimental features
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanelItem as ToolsPanelItem,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { CoreFilterNames, QueryControlProps } from '../../types';
import { DEFAULT_QUERY } from '../../constants';

const MIN_OFFSET = 0;
const MAX_OFFSET = 100;

const OffsetControl = ( {
	query,
	setQueryAttribute,
	trackInteraction,
}: QueryControlProps ) => {
	const deselectCallback = () => {
		setQueryAttribute( { offset: DEFAULT_QUERY.offset } );
		trackInteraction( CoreFilterNames.OFFSET );
	};

	return (
		<ToolsPanelItem
			label={ __( 'Offset', 'woocommerce' ) }
			hasValue={ () => query.offset !== DEFAULT_QUERY.offset }
			onDeselect={ deselectCallback }
			resetAllFilter={ deselectCallback }
		>
			<NumberControl
				__next40pxDefaultSize
				label={ __( 'Offset', 'woocommerce' ) }
				value={ query.offset }
				min={ MIN_OFFSET }
				onChange={ ( newOffset: number ) => {
					if (
						isNaN( newOffset ) ||
						newOffset < MIN_OFFSET ||
						newOffset > MAX_OFFSET
					) {
						return;
					}
					setQueryAttribute( { offset: newOffset } );
					trackInteraction( CoreFilterNames.OFFSET );
				} }
			/>
		</ToolsPanelItem>
	);
};

export default OffsetControl;
