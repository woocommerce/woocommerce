/**
 * External dependencies
 */
import clsx from 'clsx';
import { __ } from '@wordpress/i18n';

interface SingleProductTab {
	id: string;
	title: string;
	active: boolean;
	content: React.JSX.Element | undefined;
}

const ProductTabTitle = ( {
	id,
	title,
	active,
}: Pick< SingleProductTab, 'id' | 'title' | 'active' > ) => {
	return (
		<li
			className={ clsx( `${ id }_tab`, {
				active,
			} ) }
			id={ `tab-title-${ id }` }
			role="tab"
			aria-controls={ `tab-${ id }` }
		>
			<a href={ `#tab-${ id }` }>{ title }</a>
		</li>
	);
};

const ProductTabContent = ( {
	id,
	content,
}: Pick< SingleProductTab, 'id' | 'content' > ) => {
	return (
		<div
			className={ `${ id }_tab` }
			id={ `tab-title-${ id }` }
			role="tab"
			aria-controls={ `tab-${ id }` }
		>
			{ content }
		</div>
	);
};

export const SingleProductDetails = ( {
	hideTabTitle,
}: {
	hideTabTitle: boolean;
} ) => {
	const productTabs = [
		{
			id: 'description',
			title: 'Description',
			active: true,
			content: (
				<>
					{ ! hideTabTitle && (
						<h2>{ __( 'Description', 'woocommerce' ) }</h2>
					) }
					<p>
						{ __(
							'This block lists description, attributes and reviews for a single product.',
							'woocommerce'
						) }
					</p>
				</>
			),
		},
		{
			id: 'additional_information',
			title: 'Additional Information',
			active: false,
		},
		{ id: 'reviews', title: 'Reviews', active: false },
	];
	const tabsTitle = productTabs.map( ( { id, title, active } ) => (
		<ProductTabTitle
			key={ id }
			id={ id }
			title={ title }
			active={ active }
		/>
	) );
	const tabsContent = productTabs.map( ( { id, content } ) => (
		<ProductTabContent key={ id } id={ id } content={ content } />
	) );

	return (
		<>
			<ul className="wc-tabs tabs" role="tablist">
				{ tabsTitle }
			</ul>
			{ tabsContent }
		</>
	);
};

export default SingleProductDetails;
