/**
 * External dependencies
 */
import { useState, useEffect, useMemo } from '@wordpress/element';

/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Card, CardBody } from '@wordpress/components';
import { Spinner } from '@woocommerce/components';
import { Text } from '@woocommerce/experimental';
import { WooHeaderPageTitle } from '@woocommerce/admin-layout';

/**
 * Internal dependencies
 */
import { usePages, isPermissionDeniedPath } from './controller';
import { Page } from './hooks/use-page-classes';

const NoMatch = ( { path }: { path?: string } ) => {
	const [ isDelaying, setIsDelaying ] = useState( true );
	const pages = usePages() as Page[];

	// Same check the breadcrumb uses, so the page title and this card never
	// disagree about why the page couldn't be shown.
	const isPermissionDenied = useMemo(
		() => isPermissionDeniedPath( path, pages ),
		[ path, pages ]
	);

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
					<Text as="h3">
						{ isPermissionDenied
							? __( 'Access denied', 'woocommerce' )
							: __( 'Page not found', 'woocommerce' ) }
					</Text>
					<Text>
						{ isPermissionDenied
							? __(
									'You do not have permission to view this page. Ask a site administrator for help.',
									'woocommerce'
							  )
							: __(
									'We couldn’t find that page. Check the address for a typo, or return to WooCommerce Home.',
									'woocommerce'
							  ) }
					</Text>
				</CardBody>
			</Card>
		</div>
	);
};

export { NoMatch };
