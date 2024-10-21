/**
 * External dependencies
 */
import { useDispatch } from '@wordpress/data';
import { ITEMS_STORE_NAME } from '@woocommerce/data';
import { navigateTo } from '@woocommerce/navigation';
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
	'woocommerce_product_creation_experience_pricing_to_general_202406';

export const useCreateProductByType = () => {
	const { createProductFromTemplate } = useDispatch( ITEMS_STORE_NAME );
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

		if (
			type === 'physical' ||
			type === 'variable' ||
			type === 'digital' ||
			type === 'grouped' ||
			type === 'external'
		) {
			const assignment = await loadExperimentAssignment(
				EXPERIMENT_NAME
			);
			if ( assignment.variationName === 'treatment' ) {
				const url = await getProductEditPageLink( type );
				const _feature_nonce = getAdminSetting( '_feature_nonce' );
				window.location.href =
					url +
					`&product_block_editor=1&_feature_nonce=${ _feature_nonce }`;
				return;
			}
		}

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
