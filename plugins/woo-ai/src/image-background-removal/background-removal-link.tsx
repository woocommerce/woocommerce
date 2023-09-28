/**
 * External dependencies
 */
import { __experimentalUseBackgroundRemoval as useBackgroundRemoval } from '@woocommerce/ai';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import MagicIcon from '../../assets/images/icons/magic.svg';

export const BackgroundRemovalLink = () => {
	const { fetchImage, loading, error } = useBackgroundRemoval();

	const onRemoveClick = async () => {
		const imgUrl = (
			document.querySelector(
				'.attachment-details-copy-link'
			) as HTMLInputElement | null
		 )?.value;

		if ( ! imgUrl ) {
			return;
		}

		const original = await fetch( imgUrl ).then( ( res ) => res.blob() );

		const bgRemoved = await fetchImage( {
			imageFile: new File( [ original ], 'original', {
				type: original.type,
			} ),
		} );

		// TODO: upload `bgRemoved` to media library
	};

	if ( error ) {
		return (
			<span>
				{ __( 'Error. Please try again later.', 'woocommerce' ) }
			</span>
		);
	}

	if ( loading ) {
		return <span>{ __( 'Generating…', 'woocommerce' ) }</span>;
	}

	return (
		<>
			<button onClick={ () => onRemoveClick() }>Remove background</button>
			<img src={ MagicIcon } alt="" />
		</>
	);
};
