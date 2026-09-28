/* global wp */
// Shows one card of the form at a time, with the selected card in the route's `view` parameter.
( function () {
	const { createElement: el } = wp.element;

	function ExampleBody( { definition, view, onChangeView, renderForm } ) {
		const cards = definition.form.fields;
		const current = cards.find( ( card ) => card.id === view ) || cards[ 0 ];
		return el(
			'div',
			null,
			el(
				'div',
				{ role: 'tablist' },
				cards.map( ( card ) =>
					el(
						'button',
						{
							key: card.id,
							role: 'tab',
							type: 'button',
							'aria-selected': card.id === current.id,
							onClick: () => onChangeView( card.id ),
						},
						card.label
					)
				)
			),
			renderForm( { ...definition.form, fields: [ current ] } )
		);
	}

	wp.hooks.addFilter(
		'woocommerce.experimentalPaymentSettings.body',
		'payment-settings-example/body',
		( body, screenId ) => ( screenId === 'example' ? ExampleBody : body )
	);
} )();
