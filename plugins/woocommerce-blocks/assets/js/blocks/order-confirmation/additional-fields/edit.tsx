/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { getSetting } from '@woocommerce/settings';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import './style.scss';
import type { AdditionalField } from './types';

const Edit = (): JSX.Element => {
	const blockProps = useBlockProps( {
		className: 'wc-block-order-confirmation-additional-fields',
	} );

	const additionalFields = getSetting< AdditionalField[] >(
		'checkoutAdditionalFields',
		{}
	);

	return (
		<div { ...blockProps }>
			<dl className="wc-block-order-confirmation-additional-fields-list">
				{ Object.entries( additionalFields ).map( ( [ , field ] ) => {
					const { label, type, options } = field;
					let sampleValue = __(
						'Customer provided value',
						'woocommerce'
					);

					if ( type === 'checkbox' ) {
						sampleValue = __( 'Yes', 'woocommerce' );
					}

					if ( type === 'select' ) {
						sampleValue = options[ 0 ].label;
					}

					return (
						<>
							<dt>{ label }</dt>
							<dd>{ sampleValue }</dd>
						</>
					);
				} ) }
			</dl>
		</div>
	);
};

export default Edit;
