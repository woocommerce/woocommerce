/**
 * External dependencies
 */
import { useDispatch } from '@wordpress/data';
import { ITEMS_STORE_NAME } from '@woocommerce/data';
import { getAdminLink } from '@woocommerce/settings';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ProductTypeKey } from './constants';
import { createNoticesFromResponse } from '../../../lib/notices';

export const useCreateProductByType = () => {
	const { createProductFromTemplate } = useDispatch( ITEMS_STORE_NAME );
	const [ isRequesting, setIsRequesting ] = useState< boolean >( false );

	const createProductByType = async ( type: ProductTypeKey ) => {
		if ( type === 'subscription' ) {
			window.location = getAdminLink(
				'post-new.php?post_type=product&subscription_pointers=true'
			);
			return;
		}

		setIsRequesting( true );
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
				const link = getAdminLink(
					`post.php?post=${ data.id }&action=edit&wc_onboarding_active_task=products&tutorial=true`
				);
				window.location = link;
			} else {
				throw new Error( 'Unexpected empty data response from server' );
			}
		} catch ( error ) {
			createNoticesFromResponse( error );
		}
		setIsRequesting( false );
	};

	return {
		createProductByType,
		isRequesting,
	};
};
