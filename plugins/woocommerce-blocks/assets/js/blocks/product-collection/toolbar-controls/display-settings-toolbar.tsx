/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { settings } from '@wordpress/icons';
import {
	ToolbarGroup,
	Dropdown,
	ToolbarButton,
	BaseControl,
	// @ts-expect-error Using experimental features
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalNumberControl as NumberControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { ProductCollectionQuery } from '../types';

interface DisplaySettingsToolbarProps {
	query: ProductCollectionQuery;
	setQueryAttribute: ( value: Partial< ProductCollectionQuery > ) => void;
}

interface ToggleButtonProps {
	onToggle?: () => void;
}

const ToggleButton = ( { onToggle }: ToggleButtonProps ) => (
	<ToolbarButton
		icon={ settings }
		label={ __( 'Display settings', 'woo-gutenberg-products-block' ) }
		onClick={ onToggle }
	/>
);

const DisplaySettingsToolbar = ( {
	query,
	setQueryAttribute,
}: DisplaySettingsToolbarProps ) => {
	const handlePerPageChange = ( value: string | undefined ) => {
		const parsedValue = Number( value );
		if (
			! isNaN( parsedValue ) &&
			parsedValue >= 1 &&
			parsedValue <= 100
		) {
			setQueryAttribute( { perPage: parsedValue } );
		}
	};

	const handleOffsetChange = ( value: string | undefined ) => {
		const parsedValue = Number( value );
		if (
			! isNaN( parsedValue ) &&
			parsedValue >= 0 &&
			parsedValue <= 100
		) {
			setQueryAttribute( { offset: parsedValue } );
		}
	};

	const handlePagesChange = ( value: string | undefined ) => {
		const parsedValue = Number( value );
		if ( ! isNaN( parsedValue ) && parsedValue >= 0 ) {
			setQueryAttribute( { pages: parsedValue } );
		}
	};

	const numberControlProps = {
		__unstableInputWidth: '60px',
		labelPosition: 'edge',
		step: '1',
		isDragEnabled: false,
	};

	return (
		<ToolbarGroup>
			<Dropdown
				contentClassName="wc-block-editor-product-collection__display-settings"
				renderToggle={ ( { onToggle } ) => (
					<ToggleButton onToggle={ onToggle } />
				) }
				renderContent={ () => (
					<>
						<NumberControl
							{ ...numberControlProps }
							label={ __(
								'Items per Page',
								'woo-gutenberg-products-block'
							) }
							min={ 1 }
							max={ 100 }
							onChange={ handlePerPageChange }
							value={ query.perPage }
						/>

						<NumberControl
							{ ...numberControlProps }
							label={ __(
								'Offset',
								'woo-gutenberg-products-block'
							) }
							min={ 0 }
							max={ 100 }
							onChange={ handleOffsetChange }
							value={ query.offset }
						/>

						<BaseControl
							help={ __(
								'Limit the pages you want to show, even if the query has more results. To show all pages use 0 (zero).',
								'woo-gutenberg-products-block'
							) }
							id="woocommerce-products-block__display-settings-pages"
						>
							<NumberControl
								{ ...numberControlProps }
								label={ __(
									'Max page to show',
									'woo-gutenberg-products-block'
								) }
								min={ 0 }
								onChange={ handlePagesChange }
								value={ query.pages }
							/>
						</BaseControl>
					</>
				) }
			/>
		</ToolbarGroup>
	);
};

export default DisplaySettingsToolbar;
