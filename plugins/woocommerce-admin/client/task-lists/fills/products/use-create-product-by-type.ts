/**
 * External dependencies
 */
import { useDispatch } from '@wordpress/data';
import { ITEMS_STORE_NAME } from '@woocommerce/data';
import { getNewPath, navigateTo } from '@woocommerce/navigation';
import { getAdminLink } from '@woocommerce/settings';
import { loadExperimentAssignment } from '@woocommerce/explat';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ProductTypeKey } from './constants';
import { createNoticesFromResponse } from '../../../lib/notices';
import { getAdminSetting } from '~/utils/admin-settings';

export const useCreateProductByType = () => {
	const { createProductFromTemplate } = useDispatch( ITEMS_STORE_NAME );
	const [ isRequesting, setIsRequesting ] = useState< boolean >( false );
	const isNewExperienceEnabled =
		window.wcAdminFeatures[ 'new-product-management-experience' ];

	const createProductByType = async ( type: ProductTypeKey ) => {
		if ( type === 'subscription' ) {
			window.location.href = getAdminLink(
				'post-new.php?post_type=product&subscription_pointers=true'
			);
			return;
		}

		setIsRequesting( true );

		if ( type === 'physical' ) {
			if ( isNewExperienceEnabled ) {
				navigateTo( { url: getNewPath( {}, '/add-product', {} ) } );
				return;
			}

			const assignment = await loadExperimentAssignment(
				'woocommerce_product_creation_experience_202308_v3'
			);

			if ( assignment.variationName === 'treatment' ) {
				const _feature_nonce = getAdminSetting( '_feature_nonce' );
				window.location.href = getAdminLink(
					`post-new.php?post_type=product&product_block_editor=1&_feature_nonce=${ _feature_nonce }`
				);
				return;
			}
		}

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
				window.location.href = link;
				return;
			}
			throw new Error( 'Unexpected empty data response from server' );
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
