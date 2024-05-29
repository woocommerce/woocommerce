/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { TableEmptyOrErrorStateProps } from './types';
import { ErrorVariationsImage } from '../../../images/error-variations-image';
import { EmptyVariationsImage } from '../../../images/empty-variations-image';

export function EmptyOrErrorTableState( {
	message,
	actionText,
	isError,
	onActionClick,
}: TableEmptyOrErrorStateProps ) {
	return (
		<div className="woocommerce-variations-table-error-or-empty-state">
			{ isError ? <ErrorVariationsImage /> : <EmptyVariationsImage /> }
			<p className="woocommerce-variations-table-error-or-empty-state__message">
				{ isError
					? __( 'We couldn’t load the variations', 'woocommerce' )
					: message ?? __( 'No variations yet', 'woocommerce' ) }
			</p>

			<div className="woocommerce-variations-table-error-or-empty-state__actions">
				<Button variant="link" onClick={ onActionClick }>
					{ isError
						? __( 'Try again', 'woocommerce' )
						: actionText ??
						  __( 'Generate from options', 'woocommerce' ) }
				</Button>
			</div>
		</div>
	);
}
