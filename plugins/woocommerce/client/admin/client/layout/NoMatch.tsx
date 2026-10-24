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
import { getPages, isPermissionDeniedPath } from './controller';
import { Page } from './hooks/use-page-classes';

const NoMatch = ( { path }: { path?: string } ) => {
	const [ isDelaying, setIsDelaying ] = useState( true );

	// Same check the breadcrumb uses, so the page title and this card never
	// disagree about why the page couldn't be shown. Pages are read with
	// getPages() rather than usePages(): useFilterHook shares one listener
	// namespace per filter, so unmounting this component would also remove
	// the layout's listener. Recomputing once the delay ends picks up pages
	// registered in the meantime.
	const isPermissionDenied = useMemo(
		() =>
			! isDelaying &&
			isPermissionDeniedPath( path, getPages() as Page[] ),
		[ path, isDelaying ]
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
					<Text>
						{ isPermissionDenied
							? __(
									'You do not have permission to view this page. Ask a site administrator for help.',
									'woocommerce'
							  )
							: __(
									'We couldn’t find that page. Check the address for a typo.',
									'woocommerce'
							  ) }
					</Text>
				</CardBody>
			</Card>
		</div>
	);
};

export { NoMatch };
