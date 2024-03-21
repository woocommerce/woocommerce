/**
 * External dependencies
 */
import { MouseEvent } from 'react';
import { Button } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { isInTheFuture } from '@wordpress/date';
import { __ } from '@wordpress/i18n';
import type { Product } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { useProductManager } from '../../../../hooks/use-product-manager';
import type { WPError } from '../../../../utils/get-product-error-message';
import type { PublishButtonProps } from '../../publish-button';

export function usePublish< T = Product >( {
	productType = 'product',
	disabled,
	onClick,
	onPublishSuccess,
	onPublishError,
	...props
}: PublishButtonProps & {
	onPublishSuccess?( product: T ): void;
	onPublishError?( error: WPError ): void;
} ): Button.ButtonProps {
	const { isValidating, isDirty, isPublishing, publish } =
		useProductManager( productType );

	const [ , , prevStatus ] = useEntityProp< Product[ 'status' ] >(
		'postType',
		productType,
		'status'
	);

	const [ editedDate ] = useEntityProp< string >(
		'postType',
		productType,
		'date_created_gmt'
	);

	const isBusy = isPublishing || isValidating;
	const isDisabled = disabled || isBusy || ! isDirty;

	function handleClick( event: MouseEvent< HTMLButtonElement > ) {
		if ( isDisabled ) {
			event.preventDefault?.();
			return;
		}

		if ( onClick ) {
			onClick( event );
		}

		publish().then( onPublishSuccess ).catch( onPublishError );
	}

	function getButtonText() {
		if (
			window.wcAdminFeatures[ 'product-pre-publish-modal' ] &&
			isInTheFuture( editedDate )
		) {
			return __( 'Schedule', 'woocommerce' );
		}

		if ( prevStatus === 'publish' || prevStatus === 'future' ) {
			return __( 'Update', 'woocommerce' );
		}

		return __( 'Publish', 'woocommerce' );
	}

	return {
		children: getButtonText(),
		...props,
		isBusy,
		'aria-disabled': isDisabled,
		variant: 'primary',
		onClick: handleClick,
	};
}
