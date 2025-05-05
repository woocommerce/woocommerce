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

const MaxPagesToShowControl = ( {
	query,
	setQueryAttribute,
	trackInteraction,
}: QueryControlProps ) => {
	const deselectCallback = () => {
		setQueryAttribute( { pages: DEFAULT_QUERY.pages } );
		trackInteraction( CoreFilterNames.MAX_PAGES_TO_SHOW );
	};

	return (
		<ToolsPanelItem
			label={ __( 'Max pages to show', 'woocommerce' ) }
			hasValue={ () => query.pages !== DEFAULT_QUERY.pages }
			onDeselect={ deselectCallback }
			resetAllFilter={ deselectCallback }
		>
			<NumberControl
				__next40pxDefaultSize
				label={ __( 'Max pages to show', 'woocommerce' ) }
				value={ query.pages }
				min={ 0 }
				onChange={ ( newPages: number ) => {
					if ( isNaN( newPages ) || newPages < 0 ) {
						return;
					}
					setQueryAttribute( { pages: newPages } );
					trackInteraction( CoreFilterNames.MAX_PAGES_TO_SHOW );
				} }
				help={ __(
					'Limit the pages you want to show, even if the query has more results. To show all pages use 0 (zero).',
					'woocommerce'
				) }
			/>
		</ToolsPanelItem>
	);
};

export default MaxPagesToShowControl;
