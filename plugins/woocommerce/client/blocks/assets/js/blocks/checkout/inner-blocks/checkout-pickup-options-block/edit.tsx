/* eslint-disable @wordpress/no-unsafe-wp-apis */
/**
 * External dependencies
 */
import clsx from 'clsx';
import { useBlockProps } from '@wordpress/block-editor';
import { innerBlockAreas } from '@woocommerce/blocks-checkout';
import { useSelect } from '@wordpress/data';
import { checkoutStore as checkoutStoreDescriptor } from '@woocommerce/block-data';
import { LOCAL_PICKUP_ENABLED } from '@woocommerce/block-settings';
import { Disabled } from '@wordpress/components';

/**
 * Internal dependencies
 */
import {
	FormStepBlock,
	AdditionalFields,
	AdditionalFieldsContent,
} from '../../form-step';
import Block from './block';

export const Edit = ( {
	attributes,
	setAttributes,
}: {
	attributes: {
		title: string;
		description: string;
		showStepNumber: boolean;
		className: string;
	};
	setAttributes: ( attributes: Record< string, unknown > ) => void;
} ): JSX.Element | null => {
	const { prefersCollection } = useSelect( ( select ) => {
		const checkoutStore = select( checkoutStoreDescriptor );
		return {
			prefersCollection: checkoutStore.prefersCollection(),
		};
	} );
	const { className } = attributes;

	if ( ! prefersCollection || ! LOCAL_PICKUP_ENABLED ) {
		return null;
	}

	return (
		<FormStepBlock
			attributes={ attributes }
			setAttributes={ setAttributes }
			className={ clsx(
				'wc-block-checkout__shipping-method',
				className
			) }
		>
			<Disabled>
				<Block />
			</Disabled>
			<AdditionalFields block={ innerBlockAreas.PICKUP_LOCATION } />
		</FormStepBlock>
	);
};

export const Save = (): JSX.Element => {
	return (
		<div { ...useBlockProps.save() }>
			<AdditionalFieldsContent />
		</div>
	);
};
