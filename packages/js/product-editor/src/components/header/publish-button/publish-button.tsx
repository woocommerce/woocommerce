/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { useDispatch } from '@wordpress/data';
import { createElement } from '@wordpress/element';
import { type Product } from '@woocommerce/data';
import { getNewPath, navigateTo } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { useErrorHandler } from '../../../hooks/use-error-handler';
import { recordProductEvent } from '../../../utils/record-product-event';
import { useFeedbackBar } from '../../../hooks/use-feedback-bar';
import { usePublish } from '../hooks/use-publish';
import { PublishButtonMenu } from './publish-button-menu';
import { showSuccessNotice } from './utils';
import type { PublishButtonProps } from './types';

export function PublishButton( {
	productType = 'product',
	isMenuButton,
	visibleTab = 'general',
	...props
}: PublishButtonProps ) {
	const { createErrorNotice } = useDispatch( 'core/notices' );
	const { maybeShowFeedbackBar } = useFeedbackBar();
	const { getProductErrorMessageAndProps } = useErrorHandler();

	const [ , , prevStatus ] = useEntityProp< Product[ 'status' ] >(
		'postType',
		productType,
		'status'
	);

	const publishButtonProps = usePublish( {
		productType,
		...props,
		onPublishSuccess( savedProduct: Product ) {
			const isPublished =
				savedProduct.status === 'publish' ||
				savedProduct.status === 'future';

			if ( isPublished ) {
				recordProductEvent( 'product_update', savedProduct );
			}

			showSuccessNotice( savedProduct, prevStatus );

			maybeShowFeedbackBar();

			if ( prevStatus === 'auto-draft' || prevStatus === 'draft' ) {
				const url = getNewPath( {}, `/product/${ savedProduct.id }` );
				navigateTo( { url } );
			}
		},
		async onPublishError( error ) {
			const { message, errorProps } =
				await getProductErrorMessageAndProps( error, visibleTab );
			createErrorNotice( message, errorProps );
		},
	} );

	if ( productType === 'product' && isMenuButton ) {
		function renderPublishButtonMenu( menuProps: {
			onClose: () => void;
		} ): React.ReactElement {
			return (
				<PublishButtonMenu
					{ ...menuProps }
					postType={ productType }
					visibleTab={ visibleTab }
				/>
			);
		}

		return (
			<PublishButtonMenu
				{ ...publishButtonProps }
				postType={ productType }
				controls={ undefined }
				renderMenu={ renderPublishButtonMenu }
				visibleTab={ visibleTab }
			/>
		);
	}

	return <Button { ...publishButtonProps } />;
}
