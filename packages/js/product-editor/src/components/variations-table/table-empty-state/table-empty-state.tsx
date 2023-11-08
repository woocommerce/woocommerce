/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { TableEmptyStateProps } from './types';
import { EmptyVariationsImage } from '../../../images/empty-variations-image';

export function EmptyTableState( { onActionClick }: TableEmptyStateProps ) {
	return (
		<div className="woocommerce-variations-table-empty-state">
			<EmptyVariationsImage
				className="woocommerce-variations-table-empty-state__image"
				aria-hidden="true"
			/>

			<p className="woocommerce-variations-table-empty-state__message">
				{ __( 'No variations yet', 'woocommerce' ) }
			</p>

			<div className="woocommerce-variations-table-empty-state__actions">
				<Button variant="link" onClick={ onActionClick }>
					{ __( 'Generate from options', 'woocommerce' ) }
				</Button>
			</div>
		</div>
	);
}
