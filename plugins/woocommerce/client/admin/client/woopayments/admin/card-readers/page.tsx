/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { getWooPaymentsCardReaders } from './data';
import type { WooPaymentsCardReader } from './types';

const getErrorMessage = ( error: unknown ) => {
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

	return __( 'Unable to load card readers.', 'woocommerce' );
};

const getStatusLabel = ( reader: WooPaymentsCardReader ) =>
	reader.is_active
		? __( 'Active', 'woocommerce' )
		: __( 'Inactive', 'woocommerce' );

export const WooPaymentsCardReadersPage = () => {
	const [ readers, setReaders ] = useState< WooPaymentsCardReader[] >( [] );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ errorMessage, setErrorMessage ] = useState< string | null >( null );

	useEffect( () => {
		let isMounted = true;

		const loadReaders = async () => {
			setIsLoading( true );

			try {
				const nextReaders = await getWooPaymentsCardReaders( 10 );

				if ( ! isMounted ) {
					return;
				}

				setReaders( nextReaders );
				setErrorMessage( null );
			} catch ( error ) {
				if ( isMounted ) {
					setReaders( [] );
					setErrorMessage( getErrorMessage( error ) );
				}
			} finally {
				if ( isMounted ) {
					setIsLoading( false );
				}
			}
		};

		loadReaders();

		return () => {
			isMounted = false;
		};
	}, [] );

	const hasLoadedReaders =
		! isLoading && ! errorMessage && readers.length > 0;
	const statusMessage =
		( isLoading && __( 'Loading card readers…', 'woocommerce' ) ) ||
		errorMessage ||
		( ! isLoading &&
			! errorMessage &&
			readers.length === 0 &&
			__( 'No card readers found.', 'woocommerce' ) ) ||
		( hasLoadedReaders && __( 'Card readers loaded.', 'woocommerce' ) ) ||
		'';

	return (
		<div className="woocommerce-woopayments-card-readers">
			<section
				className="woocommerce-woopayments-card-readers__settings-card"
				aria-busy={ isLoading }
				aria-labelledby="woocommerce-woopayments-card-readers-heading"
			>
				<h2 id="woocommerce-woopayments-card-readers-heading">
					{ __( 'Connected card readers', 'woocommerce' ) }
				</h2>
				<p>
					{ sprintf(
						/* translators: %s: WooCommerce */
						__(
							'Card readers are marked as active if they’ve processed one or more transactions during the current billing cycle. To connect or disconnect card readers, use the %s mobile application.',
							'woocommerce'
						),
						'WooCommerce'
					) }
				</p>
				<p
					className={
						hasLoadedReaders
							? 'screen-reader-text'
							: 'woocommerce-woopayments-card-readers__status'
					}
					role={ errorMessage ? 'alert' : 'status' }
					aria-live={ errorMessage ? 'assertive' : 'polite' }
				>
					{ statusMessage }
				</p>
				{ hasLoadedReaders && (
					<table className="woocommerce-woopayments-card-readers__table">
						<thead>
							<tr>
								<th scope="col">
									{ __( 'Reader ID', 'woocommerce' ) }
								</th>
								<th scope="col">
									{ __( 'Model', 'woocommerce' ) }
								</th>
								<th scope="col">
									{ __( 'Status', 'woocommerce' ) }
								</th>
							</tr>
						</thead>
						<tbody>
							{ readers.map( ( reader ) => (
								<tr key={ reader.id }>
									<td>{ reader.id }</td>
									<td>{ reader.device_type }</td>
									<td>
										<span
											className={
												reader.is_active
													? 'woocommerce-woopayments-card-readers__status-badge is-active'
													: 'woocommerce-woopayments-card-readers__status-badge is-inactive'
											}
										>
											{ getStatusLabel( reader ) }
										</span>
									</td>
								</tr>
							) ) }
						</tbody>
					</table>
				) }
			</section>
		</div>
	);
};

export default WooPaymentsCardReadersPage;
