/**
 * External dependencies
 */
import { ProductImage } from '@woocommerce/components';

export default () => (
	<div>
		<ProductImage product={ null } />
		<ProductImage product={ { images: [] } } />
		<ProductImage
			product={ {
				images: [
					{
						src: 'https://cldup.com/6L9h56D9Bw.jpg',
					},
				],
			} }
		/>
	</div>
);
