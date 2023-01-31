/**
 * External dependencies
 */
import { useDispatch } from '@wordpress/data';
import { ITEMS_STORE_NAME, OPTIONS_STORE_NAME } from '@woocommerce/data';
import { getNewPath, navigateTo } from '@woocommerce/navigation';
import { getAdminLink } from '@woocommerce/settings';
import { loadExperimentAssignment } from '@woocommerce/explat';
import moment from 'moment';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ProductTypeKey } from './constants';
import { createNoticesFromResponse } from '../../../lib/notices';

const NEW_PRODUCT_MANAGEMENT = 'woocommerce_new_product_management_enabled';

export const useCreateProductByType = () => {
	const { createProductFromTemplate } = useDispatch( ITEMS_STORE_NAME );
	const [ isRequesting, setIsRequesting ] = useState< boolean >( false );
	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );
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
			const momentDate = moment().utc();
			const year = momentDate.format( 'YYYY' );
			const month = momentDate.format( 'MM' );
			const assignment = await loadExperimentAssignment(
				`woocommerce_product_creation_experience_${ year }${ month }_v1`
			);

			if ( isNewExperienceEnabled ) {
				navigateTo( { url: getNewPath( {}, '/add-product', {} ) } );
				return;
			}
			if ( assignment.variationName === 'treatment' ) {
				await updateOptions( {
					[ NEW_PRODUCT_MANAGEMENT ]: 'yes',
				} );
				window.location.href = getAdminLink(
					'admin.php?page=wc-admin&path=/add-product'
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
