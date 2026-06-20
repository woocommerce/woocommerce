/**
 * External dependencies
 */
import {
	Button,
	CheckboxControl,
	Modal,
	Notice,
	TextControl,
} from '@wordpress/components';
import { useEffect, useRef, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import {
	saveWooPaymentsVatDetails,
	validateWooPaymentsVatNumber,
} from './data';
import type {
	WooPaymentsVatDetails,
	WooPaymentsVatValidationResponse,
} from './types';
import './vat-modal.scss';

type WooPaymentsVatModalProps = {
	country?: string;
	onClose: () => void;
	onCompleted: ( details: WooPaymentsVatDetails ) => void;
};

const countryTaxIdLabels: Record< string, string > = {
	AU: __( 'Australian Business Number', 'woocommerce' ),
	JP: __( 'Corporate Number', 'woocommerce' ),
	NZ: __( 'New Zealand Business Number', 'woocommerce' ),
	SG: __( 'Goods and Services Tax Number', 'woocommerce' ),
};

const getTaxIdLabel = ( country?: string ) =>
	countryTaxIdLabels[ ( country || '' ).toUpperCase() ] ||
	__( 'VAT Number', 'woocommerce' );

const getErrorMessage = ( error: unknown, fallback: string ) => {
	if ( error instanceof Error && error.message ) {
		return error.message;
	}

	if (
		error &&
		typeof error === 'object' &&
		'message' in error &&
		typeof error.message === 'string'
	) {
		return error.message;
	}

	return fallback;
};

const normalizeValidationDetails = (
	response: WooPaymentsVatValidationResponse,
	vatNumber: string
): WooPaymentsVatDetails => ( {
	vat_number: response.vat_number || vatNumber,
	name: response.name || '',
	address: response.address || '',
} );

export const WooPaymentsVatModal = ( {
	country,
	onClose,
	onCompleted,
}: WooPaymentsVatModalProps ) => {
	const taxIdLabel = getTaxIdLabel( country );
	const detailsFieldsRef = useRef< HTMLDivElement | null >( null );
	const isClosedRef = useRef( false );
	const hasFocusedDetailsFieldsRef = useRef( false );
	const [ hasValidVatNumber, setHasValidVatNumber ] = useState( false );
	const [ vatNumber, setVatNumber ] = useState( '' );
	const [ details, setDetails ] = useState< WooPaymentsVatDetails | null >(
		null
	);
	const [ errorMessage, setErrorMessage ] = useState< string | null >( null );
	const [ isBusy, setIsBusy ] = useState( false );

	useEffect( () => {
		return () => {
			isClosedRef.current = true;
		};
	}, [] );

	useEffect( () => {
		if ( ! details ) {
			hasFocusedDetailsFieldsRef.current = false;
			return;
		}

		if ( hasFocusedDetailsFieldsRef.current ) {
			return;
		}

		hasFocusedDetailsFieldsRef.current = true;
		detailsFieldsRef.current
			?.querySelector< HTMLInputElement >( 'input' )
			?.focus();
	}, [ details ] );

	const handleClose = () => {
		isClosedRef.current = true;
		onClose();
	};

	const handleContinue = async () => {
		if ( ! hasValidVatNumber ) {
			setDetails( {
				vat_number: null,
				name: '',
				address: '',
			} );
			return;
		}

		if ( ! vatNumber.trim() ) {
			return;
		}

		setIsBusy( true );
		setErrorMessage( null );

		try {
			const response = await validateWooPaymentsVatNumber(
				vatNumber.trim()
			);

			if ( response.valid === false ) {
				if ( isClosedRef.current ) {
					return;
				}

				setErrorMessage(
					sprintf(
						/* translators: %s: Tax ID label, such as VAT Number. */
						__( 'Enter a valid %s.', 'woocommerce' ),
						taxIdLabel
					)
				);
				return;
			}

			if ( isClosedRef.current ) {
				return;
			}

			setDetails(
				normalizeValidationDetails( response, vatNumber.trim() )
			);
		} catch ( error ) {
			if ( isClosedRef.current ) {
				return;
			}

			setErrorMessage(
				getErrorMessage(
					error,
					sprintf(
						/* translators: %s: Tax ID label, such as VAT Number. */
						__(
							'There was a problem validating the %s.',
							'woocommerce'
						),
						taxIdLabel
					)
				)
			);
		} finally {
			if ( ! isClosedRef.current ) {
				setIsBusy( false );
			}
		}
	};

	const handleConfirm = async () => {
		if ( ! details ) {
			return;
		}

		setIsBusy( true );
		setErrorMessage( null );

		try {
			const savedDetails = await saveWooPaymentsVatDetails( details );

			if ( isClosedRef.current ) {
				return;
			}

			onCompleted( savedDetails );
		} catch ( error ) {
			if ( isClosedRef.current ) {
				return;
			}

			setErrorMessage(
				getErrorMessage(
					error,
					__(
						'There was a problem saving your tax details.',
						'woocommerce'
					)
				)
			);
		} finally {
			if ( ! isClosedRef.current ) {
				setIsBusy( false );
			}
		}
	};

	return (
		<Modal
			className="woocommerce-woopayments-documents-vat-modal"
			title={ __( 'Set your tax details', 'woocommerce' ) }
			onRequestClose={ handleClose }
		>
			{ errorMessage && (
				<Notice status="error" isDismissible={ false }>
					{ errorMessage }
				</Notice>
			) }
			{ ! details ? (
				<div className="woocommerce-woopayments-documents-vat-modal__content">
					<CheckboxControl
						label={ sprintf(
							/* translators: %s: Tax ID label, such as VAT Number. */
							__( 'I have a valid %s', 'woocommerce' ),
							taxIdLabel
						) }
						checked={ hasValidVatNumber }
						onChange={ setHasValidVatNumber }
						__nextHasNoMarginBottom
					/>
					<TextControl
						label={ taxIdLabel }
						value={ vatNumber }
						disabled={ ! hasValidVatNumber }
						onChange={ setVatNumber }
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
					<div className="woocommerce-woopayments-documents-vat-modal__actions">
						<Button variant="tertiary" onClick={ handleClose }>
							{ __( 'Cancel', 'woocommerce' ) }
						</Button>
						<Button
							variant="primary"
							isBusy={ isBusy }
							accessibleWhenDisabled
							disabled={
								isBusy ||
								( hasValidVatNumber && ! vatNumber.trim() )
							}
							onClick={ handleContinue }
						>
							{ __( 'Continue', 'woocommerce' ) }
						</Button>
					</div>
				</div>
			) : (
				<div className="woocommerce-woopayments-documents-vat-modal__content">
					<div ref={ detailsFieldsRef }>
						<TextControl
							label={ __( 'Business name', 'woocommerce' ) }
							value={ details.name }
							onChange={ ( name ) =>
								setDetails( {
									...details,
									name,
								} )
							}
							__next40pxDefaultSize
							__nextHasNoMarginBottom
						/>
						<TextControl
							label={ __( 'Address', 'woocommerce' ) }
							value={ details.address }
							onChange={ ( address ) =>
								setDetails( {
									...details,
									address,
								} )
							}
							__next40pxDefaultSize
							__nextHasNoMarginBottom
						/>
					</div>
					<div className="woocommerce-woopayments-documents-vat-modal__actions">
						<Button variant="tertiary" onClick={ handleClose }>
							{ __( 'Cancel', 'woocommerce' ) }
						</Button>
						<Button
							variant="primary"
							isBusy={ isBusy }
							accessibleWhenDisabled
							disabled={
								isBusy ||
								! details.name.trim() ||
								! details.address.trim()
							}
							onClick={ handleConfirm }
						>
							{ __( 'Confirm', 'woocommerce' ) }
						</Button>
					</div>
				</div>
			) }
		</Modal>
	);
};
