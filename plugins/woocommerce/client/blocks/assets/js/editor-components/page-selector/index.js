/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import {
	SelectControl,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanel as ToolsPanel,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanelItem as ToolsPanelItem,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { formatTitle } from '../utils';

const PageSelector = ( { setPageId, pageId, labels } ) => {
	const pages =
		useSelect( ( select ) => {
			return select( 'core' ).getEntityRecords( 'postType', 'page', {
				status: 'publish',
				orderby: 'title',
				order: 'asc',
				per_page: 100,
			} );
		}, [] ) || null;
	if ( pages ) {
		return (
			<ToolsPanel
				label={ labels.title }
				resetAll={ () => setPageId( 0 ) }
			>
				<ToolsPanelItem
					label={ __( 'Link to', 'woocommerce' ) }
					hasValue={ () =>
						typeof pageId === 'number' && pageId !== 0
					}
					onDeselect={ () => setPageId( 0 ) }
					isShownByDefault
				>
					<SelectControl
						label={ __( 'Link to', 'woocommerce' ) }
						value={ pageId }
						options={ [
							{
								label: labels.default,
								value: 0,
							},
							...pages.map( ( page ) => {
								return {
									label: formatTitle( page, pages ),
									value: parseInt( page.id, 10 ),
								};
							} ),
						] }
						onChange={ ( value ) =>
							setPageId( parseInt( value, 10 ) )
						}
					/>
				</ToolsPanelItem>
			</ToolsPanel>
		);
	}
	return null;
};

export default PageSelector;
