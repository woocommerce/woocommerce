/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { Button, Placeholder } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Icon, grid } from '@wordpress/icons';
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import deprecated from './deprecated';
import save from './save';

const { name } = metadata;
export { metadata, name };

let loadedEdit = null;
let loadEditPromise = null;

const loadAllProductsEdit = () => {
	if ( loadedEdit ) {
		return Promise.resolve( loadedEdit );
	}

	if ( ! loadEditPromise ) {
		loadEditPromise = import(
			/* webpackChunkName: "all-products-edit" */ './edit'
		).then( ( module ) => {
			loadedEdit = module.default;
			return loadedEdit;
		} );
	}

	return loadEditPromise;
};

const AllProductsEditShell = ( props ) => {
	const [ EditComponent, setEditComponent ] = useState( () => loadedEdit );
	const [ isLoading, setIsLoading ] = useState( false );
	const blockProps = useBlockProps();

	if ( EditComponent ) {
		return <EditComponent { ...props } />;
	}

	const loadEditor = () => {
		setIsLoading( true );
		loadAllProductsEdit()
			.then( ( LoadedEdit ) => {
				setEditComponent( () => LoadedEdit );
			} )
			.finally( () => {
				setIsLoading( false );
			} );
	};

	return (
		<div { ...blockProps }>
			<Placeholder
				icon={ <Icon icon={ grid } /> }
				label={ __(
					'All Products Block is a soft-deprecated block',
					'woocommerce'
				) }
			>
				<p>
					{ __(
						'For better performance and more flexible layouts, use the Product Collection block. You can continue editing this block if needed.',
						'woocommerce'
					) }
				</p>
				<Button
					variant="primary"
					onClick={ loadEditor }
					isBusy={ isLoading }
					disabled={ isLoading }
				>
					{ __( 'Edit All Products', 'woocommerce' ) }
				</Button>
			</Placeholder>
		</div>
	);
};

const settings = {
	icon: {
		src: (
			<Icon
				icon={ grid }
				className="wc-block-editor-components-block-icon"
			/>
		),
	},
	edit: AllProductsEditShell,
	// Save the props to post content.
	save,
	deprecated,
	defaults: {
		columns: 3,
		rows: 3,
		alignButtons: false,
		contentVisibility: {
			orderBy: true,
		},
		orderby: 'date',
		layoutConfig: [
			[ 'woocommerce/product-image', { imageSizing: 'thumbnail' } ],
			[ 'woocommerce/product-title' ],
			[ 'woocommerce/product-price' ],
			[ 'woocommerce/product-rating' ],
			[ 'woocommerce/product-button' ],
		],
		isPreview: false,
	},
};

registerBlockType( name, settings );
