/**
 * External dependencies
 */
import { useDispatch } from '@wordpress/data';
import { itemsStore } from '@woocommerce/data';
import { navigateTo } from '@woocommerce/navigation';
import { getAdminLink } from '@woocommerce/settings';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ProductTypeKey } from './constants';
import { createNoticesFromResponse } from '../../../lib/notices';

export const useCreateProductByType = () => {
	const { createProductFromTemplate } = useDispatch( itemsStore );
	const [ isRequesting, setIsRequesting ] = useState< boolean >( false );

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
		setIsRequesting( true );

		const url = await getProductEditPageLink( type );
		if ( url ) {
			navigateTo( { url } );
		}
		setIsRequesting( false );
	};

	return {
		createProductByType,
		isRequesting,
	};
};
