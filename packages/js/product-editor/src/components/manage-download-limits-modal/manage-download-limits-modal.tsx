/**
 * External dependencies
 */
import { FormEvent } from 'react';
import classNames from 'classnames';
import { useInstanceId } from '@wordpress/compose';
import { createElement, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { BaseControl, Button, Modal } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { ManageDownloadLimitsModalProps } from './types';
import { NumberControl } from '../number-control';

const DOWNLOAD_LIMIT_MIN = 0;
const DOWNLOAD_LIMIT_MAX = 10_000_000_000;
const DOWNLOAD_EXPIRY_MIN = 0;
const DOWNLOAD_EXPIRY_MAX = 10_000_000_000;

/**
 * Download limit and download expiry currently support
 * `-1`, `0`/`null` and a positive integer.
 * When the value is `-1` downloads can be unlimited.
 * When the value is `0` or `null` downloads are unabled.
 * When the value is greater then `0` downloads are fixed
 * to the amount set as value.
 *
 * @param value The amount of downloads
 * @return A valid number as string or empty
 */
function getInitialValue( value: number | null ): string {
	if ( value === null ) {
		return '0';
	}
	if ( value === -1 ) {
		return '';
	}
	return String( value );
}

export function ManageDownloadLimitsModal( {
	initialValue,
	onSubmit,
	onClose,
}: ManageDownloadLimitsModalProps ) {
	const [ downloadLimit, setDownloadLimit ] = useState< string >(
		getInitialValue( initialValue.downloadLimit )
	);
	const [ downloadExpiry, setDownloadExpiry ] = useState< string >(
		getInitialValue( initialValue.downloadExpiry )
	);
	const [ errors, setErrors ] = useState< Record< string, string > >( {} );

	function validateDownloadLimit() {
		if ( downloadLimit && ! Number.isInteger( Number( downloadLimit ) ) ) {
			setErrors( ( current ) => ( {
				...current,
				downloadLimit: __(
					'Download limit must be an integer number',
					'woocommerce'
				),
			} ) );
			return false;
		}

		if ( Number.parseInt( downloadLimit, 10 ) < DOWNLOAD_LIMIT_MIN ) {
			setErrors( ( current ) => ( {
				...current,
				downloadLimit: sprintf(
					// translators: %d is the minimum value of the number input.
					__(
						'Download limit must be greater than or equal to %d',
						'woocommerce'
					),
					DOWNLOAD_LIMIT_MIN
				),
			} ) );
			return false;
		}

		setErrors( ( { downloadLimit: _, ...current } ) => current );
		return true;
	}

	function validateDownloadExpiry() {
		if (
			downloadExpiry &&
			! Number.isInteger( Number( downloadExpiry ) )
		) {
			setErrors( ( current ) => ( {
				...current,
				downloadExpiry: __(
					'Expiry period must be an integer number',
					'woocommerce'
				),
			} ) );
			return false;
		}

		if ( Number.parseInt( downloadExpiry, 10 ) < DOWNLOAD_EXPIRY_MIN ) {
			setErrors( ( current ) => ( {
				...current,
				downloadExpiry: sprintf(
					// translators: %d is the minimum value of the number input.
					__(
						'Expiry period must be greater than or equal to %d',
						'woocommerce'
					),
					DOWNLOAD_EXPIRY_MIN
				),
			} ) );
			return false;
		}

		setErrors( ( { downloadExpiry: _, ...current } ) => current );
		return true;
	}

	const downloadLimitProps = {
		value: downloadLimit,
		onChange: setDownloadLimit,
		id: useInstanceId(
			BaseControl,
			'product_download_limit_field'
		) as string,
		min: DOWNLOAD_LIMIT_MIN,
		max: DOWNLOAD_LIMIT_MAX,
		className: classNames( {
			'has-error': errors.downloadLimit,
		} ),
		label: __( 'Download limit', 'woocommerce' ),
		help: __(
			'Decide how many times customers can download files after purchasing the product. Leave blank for unlimited re-downloads.',
			'woocommerce'
		),
		error: errors.downloadLimit,
		placeholder: __( 'Unlimited', 'woocommerce' ),
		suffix: __( 'times', 'woocommerce' ),

		onBlur() {
			validateDownloadLimit();
		},
	};

	const downloadExpiryProps = {
		value: downloadExpiry,
		onChange: setDownloadExpiry,
		id: useInstanceId(
			BaseControl,
			'product_download_expiry_field'
		) as string,
		min: DOWNLOAD_EXPIRY_MIN,
		max: DOWNLOAD_EXPIRY_MAX,
		className: classNames( {
			'has-error': errors.downloadExpiry,
		} ),
		label: __( 'Expiry period', 'woocommerce' ),
		help: __(
			'Decide how long customers can access the files after purchasing the product. Leave blank for unlimited access.',
			'woocommerce'
		),
		error: errors.downloadExpiry,
		placeholder: __( 'Unlimited', 'woocommerce' ),
		suffix: __( 'days', 'woocommerce' ),
		onBlur() {
			validateDownloadExpiry();
		},
	};

	function handleSubmit( event: FormEvent< HTMLFormElement > ) {
		event.preventDefault();

		const isDownloadLimitValid = validateDownloadLimit();
		const isDownloadExpiryValid = validateDownloadExpiry();

		if ( isDownloadLimitValid && isDownloadExpiryValid ) {
			onSubmit( {
				downloadLimit:
					downloadLimit === ''
						? -1
						: Number.parseInt( downloadLimit, 10 ),
				downloadExpiry:
					downloadExpiry === ''
						? -1
						: Number.parseInt( downloadExpiry, 10 ),
			} );
		}
	}

	function handleCancelClick() {
		onClose();
	}

	return (
		<Modal
			title={ __( 'Manage download limits', 'woocommerce' ) }
			className="woocommerce-manage-download-limits-modal"
			onRequestClose={ onClose }
		>
			<form noValidate onSubmit={ handleSubmit }>
				<div className="woocommerce-manage-download-limits-modal__content">
					<NumberControl { ...downloadLimitProps } />

					<NumberControl { ...downloadExpiryProps } />
				</div>

				<div className="woocommerce-manage-download-limits-modal__actions">
					<Button
						variant="tertiary"
						type="button"
						onClick={ handleCancelClick }
					>
						{ __( 'Cancel', 'woocommerce' ) }
					</Button>
					<Button variant="primary" type="submit">
						{ __( 'Save', 'woocommerce' ) }
					</Button>
				</div>
			</form>
		</Modal>
	);
}
