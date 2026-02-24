/**
 * External dependencies
 */
import { useQueryLoopProductContextValidation } from '@woocommerce/base-hooks';
import {
	RecursionProvider,
	useBlockProps,
	useHasRecursion,
	Warning,
} from '@wordpress/block-editor';
import { useEntityProp } from '@wordpress/core-data';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { ProductDescriptionEditProps } from './types';

function Placeholder( { layoutClassNames } ) {
	const blockProps = useBlockProps( { className: layoutClassNames } );
	return (
		<div { ...blockProps }>
			<p>
				{ __(
					'This block displays the product description. When viewing a product page, the description content will automatically appear here.',
					'woocommerce'
				) }
			</p>
		</div>
	);
}

function Content( props ) {
	const { context: { postType, postId } = {}, layoutClassNames } = props;

	const [ , , content ] = useEntityProp(
		'postType',
		postType,
		'content',
		postId
	);
	const blockProps = useBlockProps( { className: layoutClassNames } );

	return content?.protected ? (
		<div { ...blockProps }>
			<Warning>
				{ __( 'This content is password protected.', 'woocommerce' ) }
			</Warning>
		</div>
	) : (
		<div
			{ ...blockProps }
			dangerouslySetInnerHTML={ { __html: content?.rendered } }
		></div>
	);
}

function RecursionError() {
	const blockProps = useBlockProps();
	return (
		<div { ...blockProps }>
			<Warning>
				{ __(
					'Block cannot be rendered inside itself.',
					'woocommerce'
				) }
			</Warning>
		</div>
	);
}

export default function ProductDescriptionEdit( {
	context,
	__unstableLayoutClassNames: layoutClassNames,
	__unstableParentLayout: parentLayout,
	clientId,
}: ProductDescriptionEditProps ) {
	const { postId: contextPostId, postType: contextPostType } = context;
	const hasAlreadyRendered = useHasRecursion( contextPostId );
	const { hasInvalidContext, warningElement } =
		useQueryLoopProductContextValidation( {
			clientId,
			postType: contextPostType,
			blockName: __( 'Product Description', 'woocommerce' ),
		} );
	if ( hasInvalidContext ) {
		return warningElement;
	}

	if ( contextPostId && contextPostType && hasAlreadyRendered ) {
		return <RecursionError />;
	}

	return (
		<RecursionProvider uniqueId={ contextPostId }>
			{ contextPostId && contextPostType ? (
				<Content
					context={ context }
					parentLayout={ parentLayout }
					layoutClassNames={ layoutClassNames }
				/>
			) : (
				<Placeholder layoutClassNames={ layoutClassNames } />
			) }
		</RecursionProvider>
	);
}
