/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { PanelBody, RangeControl } from '@wordpress/components';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { getSetting } from '@woocommerce/settings';
import Noninteractive from '@woocommerce/base-components/noninteractive';

/**
 * Internal dependencies
 */
import Block from './block';
import './editor.scss';

interface Attributes {
	className?: string;
	columns: number;
}

interface Props {
	attributes: Attributes;
	setAttributes: ( attributes: Record< string, unknown > ) => void;
}

export const Edit = ( { attributes, setAttributes }: Props ): JSX.Element => {
	const { className, columns } = attributes;
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'woocommerce' ) }>
					<RangeControl
						label={ __(
							'Cross-Sells products to show',
							'woocommerce'
						) }
						value={ columns }
						onChange={ ( value ) =>
							setAttributes( { columns: value } )
						}
						min={ getSetting( 'minColumns', 1 ) }
						max={ getSetting( 'maxColumns', 6 ) }
					/>
				</PanelBody>
			</InspectorControls>
			<Noninteractive>
				<Block columns={ columns } className={ className } />
			</Noninteractive>
		</div>
	);
};

export const Save = (): JSX.Element => {
	return <div { ...useBlockProps.save() } />;
};
