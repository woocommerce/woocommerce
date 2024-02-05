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

const EXPERIMENT_NAME =
	'woocommerce_product_creation_experience_linked_products_202402_v1';

export const useCreateProductByType = () => {
	const { createProductFromTemplate } = useDispatch( ITEMS_STORE_NAME );
	const [ isRequesting, setIsRequesting ] = useState< boolean >( false );
	const isNewExperienceEnabled =
		window.wcAdminFeatures[ 'new-product-management-experience' ];

	const getProductEditPageLink = async (
		type: ProductTypeKey,
		classicEditor: boolean
	) => {
		if (
			type === 'physical' ||
			type === 'variable' ||
			type === 'digital'
		) {
			return classicEditor
				? getAdminLink( 'post-new.php?post_type=product' )
				: getNewPath( {}, '/add-product', {} );
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
				return classicEditor
					? getAdminLink(
							`post.php?post=${ data.id }&action=edit&wc_onboarding_active_task=products&tutorial=true`
					  )
					: getNewPath( {}, '/product/' + data.id, {} );
			}
			throw new Error( 'Unexpected empty data response from server' );
		} catch ( error ) {
			createNoticesFromResponse( error );
		}
	};

	const createProductByType = async ( type: ProductTypeKey ) => {
		setIsRequesting( true );

		if (
			type === 'physical' ||
			type === 'variable' ||
			type === 'digital' ||
			type === 'grouped' ||
			type === 'external'
		) {
			if ( isNewExperienceEnabled ) {
				const url = await getProductEditPageLink( type, false );
				if ( url ) {
					navigateTo( { url } );
				}
				return;
			}
			const assignment = await loadExperimentAssignment(
				EXPERIMENT_NAME
			);
			if ( assignment.variationName === 'treatment' ) {
				const url = await getProductEditPageLink( type, true );
				const _feature_nonce = getAdminSetting( '_feature_nonce' );
				window.location.href =
					url +
					`&product_block_editor=1&_feature_nonce=${ _feature_nonce }`;
				return;
			}
		}

		const url = await getProductEditPageLink( type, true );
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
