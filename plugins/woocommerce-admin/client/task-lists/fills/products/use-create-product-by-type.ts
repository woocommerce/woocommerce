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

const PHYSICAL = 'physical';
const VARIABLE = 'variable';

export const useCreateProductByType = () => {
	const { createProductFromTemplate } = useDispatch( ITEMS_STORE_NAME );
	const [ isRequesting, setIsRequesting ] = useState< boolean >( false );

	const isNewExperienceEnabled =
		window.wcAdminFeatures[ 'new-product-management-experience' ];

	const getExperimentName = ( type: string ) => {
		const baseName = 'woocommerce_product_creation_experience';
		const version =
			type === PHYSICAL ? '202308_v3' : 'add_variations_202310_v1';
		return `${ baseName }_${ version }`;
	};

	const redirectToProductBlockEditor = () => {
		const _feature_nonce = getAdminSetting( '_feature_nonce' );
		const url = `post-new.php?post_type=product&product_block_editor=1&_feature_nonce=${ _feature_nonce }`;
		window.location.href = getAdminLink( url );
	};

	const createProductByType = async ( type: ProductTypeKey ) => {
		if ( type === 'subscription' ) {
			window.location.href = getAdminLink(
				'post-new.php?post_type=product&subscription_pointers=true'
			);
			return;
		}

		setIsRequesting( true );

		if ( [ PHYSICAL, VARIABLE ].includes( type ) ) {
			if ( isNewExperienceEnabled ) {
				navigateTo( { url: getNewPath( {}, '/add-product', {} ) } );
				return;
			}
			const experimentName = getExperimentName( type );
			const assignment = await loadExperimentAssignment( experimentName );
			if ( assignment.variationName === 'treatment' ) {
				redirectToProductBlockEditor();
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
