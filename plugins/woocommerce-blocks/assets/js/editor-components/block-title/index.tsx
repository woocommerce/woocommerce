/**
 * External dependencies
 */
import { PlainText } from '@wordpress/block-editor';
import { withInstanceId } from '@wordpress/compose';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import './editor.scss';
interface BlockTitleProps {
	className: string;
	headingLevel: number;
	onChange: ( value: string ) => void;
	heading: string;
	instanceId: number;
}
const BlockTitle = ( {
	className,
	headingLevel,
	onChange,
	heading,
	instanceId,
}: BlockTitleProps ) => {
	const TagName = `h${ headingLevel }` as keyof JSX.IntrinsicElements;
	return (
		<TagName className={ className }>
			<label
				className="screen-reader-text"
				htmlFor={ `block-title-${ instanceId }` }
			>
				{ __( 'Block title', 'woo-gutenberg-products-block' ) }
			</label>
			<PlainText
				id={ `block-title-${ instanceId }` }
				className="wc-block-editor-components-title"
				value={ heading }
				onChange={ onChange }
				style={ { backgroundColor: 'transparent' } }
			/>
		</TagName>
	);
};

export default withInstanceId( BlockTitle );
