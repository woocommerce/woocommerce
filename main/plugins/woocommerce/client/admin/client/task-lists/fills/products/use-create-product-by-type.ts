/**
 * External dependencies
 */
import { speak } from '@wordpress/a11y';
import { useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { itemsStore } from '@woocommerce/data';
import { navigateTo } from '@woocommerce/navigation';
import { getAdminLink } from '@woocommerce/settings';
import { useEffect, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ProductTypeKey } from './constants';
import { createNoticesFromResponse } from '../../../lib/notices';

export const useCreateProductByType = () => {
	const { createProductFromTemplate } = useDispatch( itemsStore );
	const [ requestingType, setRequestingType ] =
		useState< ProductTypeKey | null >( null );

	// A page restored from the back/forward cache keeps its last state, so clear the spinner.
	useEffect( () => {
		const onPageShow = ( event: PageTransitionEvent ) => {
			if ( event.persisted ) {
				setRequestingType( null );
			}
		};
		window.addEventListener( 'pageshow', onPageShow );
		return () => window.removeEventListener( 'pageshow', onPageShow );
	}, [] );

	const getProductEditPageLink = async ( type: ProductTypeKey ) => {
		try {
			const data: {
				id?: number;
			} = await createProductFromTemplate(
				{
					template_name: type,
					status: 'draft',
				},
				{ _fields: [ 'id' ] }
			);
			if ( data && data.id ) {
				return getAdminLink(
					`post.php?post=${ data.id }&action=edit&wc_onboarding_active_task=products&tutorial=true&tutorial_type=${ type }`
				);
			}
			throw new Error( 'Unexpected empty data response from server' );
		} catch ( error ) {
			createNoticesFromResponse( error );
		}
	};

	const createProductByType = async ( type: ProductTypeKey ) => {
		setRequestingType( type );
		speak( __( 'Creating your product…', 'woocommerce' ) );

		const url = await getProductEditPageLink( type );
		if ( url ) {
			// No reset on success: the page stays busy until the editor loads.
			navigateTo( { url } );
		} else {
			setRequestingType( null );
		}
	};

	return {
		createProductByType,
		requestingType,
	};
};
