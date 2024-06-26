/**
 * External dependencies
 */
import { useState, useEffect } from '@wordpress/element';

/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Card, CardBody } from '@wordpress/components';
import { Spinner } from '@woocommerce/components';
import { Text } from '@woocommerce/experimental';
import { WooHeaderPageTitle } from '@woocommerce/admin-layout';

const NoMatch: React.FC = () => {
	const [ isDelaying, setIsDelaying ] = useState( true );

	/*
	 * Delay for 3 seconds to wait if there are routing pages added after the
	 * initial routing pages to reduce the chance of flashing the error message
	 * on this page.
	 */
	useEffect( () => {
		const timerId = setTimeout( () => {
			setIsDelaying( false );
		}, 3000 );

		return () => {
			clearTimeout( timerId );
		};
	}, [] );

	if ( isDelaying ) {
		return (
			<>
				<WooHeaderPageTitle>
					{ __( 'Loading…', 'woocommerce' ) }
				</WooHeaderPageTitle>
				<div className="woocommerce-layout__loading">
					<Spinner />
				</div>
			</>
		);
	}

	return (
		<div className="woocommerce-layout__no-match">
			<Card>
				<CardBody>
					<Text>
						{ __(
							'Sorry, you are not allowed to access this page.',
							'woocommerce'
						) }
					</Text>
				</CardBody>
			</Card>
		</div>
	);
};

export { NoMatch };
