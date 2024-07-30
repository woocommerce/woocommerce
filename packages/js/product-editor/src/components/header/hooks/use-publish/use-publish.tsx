/**
 * External dependencies
 */
import { MouseEvent } from 'react';
import { Button } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { __ } from '@wordpress/i18n';
import type { Product } from '@woocommerce/data';
import { useShortcut } from '@wordpress/keyboard-shortcuts';

/**
 * Internal dependencies
 */
import { useProductManager } from '../../../../hooks/use-product-manager';
import { useProductScheduled } from '../../../../hooks/use-product-scheduled';
import type { WPError } from '../../../../hooks/use-error-handler';
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

	const { isScheduled } = useProductScheduled( productType );

	const isBusy = isPublishing || isValidating;
	const isDisabled =
		prevStatus !== 'draft' && ( disabled || isBusy || ! isDirty );

	const handlePublish = () =>
		publish().then( onPublishSuccess ).catch( onPublishError );

	function handleClick( event: MouseEvent< HTMLButtonElement > ) {
		if ( isDisabled ) {
			event.preventDefault?.();
			return;
		}

		if ( onClick ) {
			onClick( event );
		}

		handlePublish();
	}

	function getButtonText() {
		if ( isScheduled ) {
			return __( 'Schedule', 'woocommerce' );
		}

		if ( prevStatus === 'publish' || prevStatus === 'future' ) {
			return __( 'Update', 'woocommerce' );
		}

		return __( 'Publish', 'woocommerce' );
	}

	useShortcut( 'core/editor/save', ( event ) => {
		event.preventDefault();
		if (
			! isDisabled &&
			( prevStatus === 'publish' || prevStatus === 'future' )
		) {
			handlePublish();
		}
	} );

	return {
		children: getButtonText(),
		...props,
		isBusy,
		'aria-disabled': isDisabled,
		variant: 'primary',
		onClick: handleClick,
	};
}
