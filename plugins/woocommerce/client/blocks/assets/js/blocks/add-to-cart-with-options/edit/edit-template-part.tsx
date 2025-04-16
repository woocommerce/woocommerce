/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { Spinner } from '@wordpress/components';
import { useEntityBlockEditor, store as coreStore } from '@wordpress/core-data';
import {
	InnerBlocks,
	useInnerBlocksProps,
	useBlockProps,
} from '@wordpress/block-editor';
import { getSetting } from '@woocommerce/settings';

const TemplatePartInnerBlocks = ( {
	blockProps,
	templatePartId,
}: {
	blockProps: Record< string, unknown >;
	templatePartId: string | undefined;
} ) => {
	const [ blocks, onInput, onChange ] = useEntityBlockEditor(
		'postType',
		'wp_template_part',
		{ id: templatePartId }
	);

	const { isLoading } = useSelect(
		( select ) => {
			const { hasFinishedResolution } = select( coreStore );

			const hasResolvedEntity = templatePartId
				? hasFinishedResolution( 'getEditedEntityRecord', [
						'postType',
						'wp_template_part',
						templatePartId,
				  ] )
				: false;

			return {
				isLoading: ! hasResolvedEntity,
			};
		},
		[ templatePartId ]
	);

	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		value: blocks,
		onInput,
		onChange,
		renderAppender: () =>
			! isLoading && blocks.length === 0
				? InnerBlocks.ButtonBlockAppender
				: null,
	} );

	if ( isLoading ) {
		return (
			<div { ...blockProps }>
				<Spinner />
			</div>
		);
	}

	return <div { ...innerBlocksProps } />;
};

export const AddToCartWithOptionsEditTemplatePart = ( {
	productType,
}: {
	productType: string;
} ) => {
	const themeTemplatePartsExist = getSetting(
		'themeTemplatePartsExist',
		{}
	) as Record< string, boolean >;
 
	const { templatePartId } = useSelect(
		( select ) => {
			const { getCurrentTheme } = select( coreStore );
			const currentTheme = getCurrentTheme()?.stylesheet;

			if ( ! currentTheme ) {
				return {
					templatePartId: null,
				};
			}

			const templatePartSlug = `${ productType }-product-add-to-cart-with-options`;
			const themeTemplatePartId = `${ currentTheme }//${ templatePartSlug }`;
			const wooCommerceTemplatePartId = `woocommerce/woocommerce//${ templatePartSlug }`;

			const themeTemplatePartExists =
				themeTemplatePartsExist[ productType ] || false;

			return {
				templatePartId: themeTemplatePartExists
					? themeTemplatePartId
					: wooCommerceTemplatePartId,
			};
		},
		[ productType, themeTemplatePartsExist ]
	);

	const blockProps = useBlockProps();

	if ( ! templatePartId ) {
		return (
			<div { ...blockProps }>
				<Spinner />
			</div>
		);
	}

	return (
		<TemplatePartInnerBlocks
			blockProps={ blockProps }
			templatePartId={ templatePartId }
		/>
	);
};
