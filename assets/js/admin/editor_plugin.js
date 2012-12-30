(
	function(){

		tinymce.create(
			"tinymce.plugins.WooCommerceShortcodes",
			{
				init: function(d,e) {},
				createControl:function(d,e)
				{

					var ed = tinymce.activeEditor;

					if(d=="woocommerce_shortcodes_button"){

						d=e.createMenuButton( "woocommerce_shortcodes_button",{
							title: ed.getLang('woocommerce.insert'),
							icons: false
							});

							var a=this;d.onRenderMenu.add(function(c,b){

								a.addImmediate(b, ed.getLang('woocommerce.order_tracking'),"[woocommerce_order_tracking]" );
								a.addImmediate(b, ed.getLang('woocommerce.price_button'), '[add_to_cart id="" sku=""]');
								a.addImmediate(b, ed.getLang('woocommerce.product_by_sku'), '[product id="" sku=""]');
								a.addImmediate(b, ed.getLang('woocommerce.products_by_sku'), '[products ids="" skus=""]');
								a.addImmediate(b, ed.getLang('woocommerce.product_categories'), '[product_categories number=""]');
								a.addImmediate(b, ed.getLang('woocommerce.products_by_cat_slug'), '[product_category category="" per_page="12" columns="4" orderby="date" order="desc"]');

								b.addSeparator();

								a.addImmediate(b, ed.getLang('woocommerce.recent_products'), '[recent_products per_page="12" columns="4" orderby="date" order="desc"]');
								a.addImmediate(b, ed.getLang('woocommerce.featured_products'), '[featured_products per_page="12" columns="4" orderby="date" order="desc"]');

								b.addSeparator();

								a.addImmediate(b, ed.getLang('woocommerce.shop_messages'), '[woocommerce_messages]');

								b.addSeparator();

								c=b.addMenu({title:"Pages"});
										a.addImmediate(c, ed.getLang('woocommerce.cart'),"[woocommerce_cart]" );
										a.addImmediate(c, ed.getLang('woocommerce.checkout'),"[woocommerce_checkout]" );
										a.addImmediate(c, ed.getLang('woocommerce.my_account'),"[woocommerce_my_account]" );
										a.addImmediate(c, ed.getLang('woocommerce.edit_address'),"[woocommerce_edit_address]" );
										a.addImmediate(c, ed.getLang('woocommerce.change_password'),"[woocommerce_change_password]" );
										a.addImmediate(c, ed.getLang('woocommerce.view_order'),"[woocommerce_view_order]" );
										a.addImmediate(c, ed.getLang('woocommerce.pay'),"[woocommerce_pay]" );
										a.addImmediate(c, ed.getLang('woocommerce.thankyou'),"[woocommerce_thankyou]" );

							});
						return d

					} // End IF Statement

					return null
				},

				addImmediate:function(d,e,a){d.add({title:e,onclick:function(){tinyMCE.activeEditor.execCommand( "mceInsertContent",false,a)}})}

			}
		);

		tinymce.PluginManager.add( "WooCommerceShortcodes", tinymce.plugins.WooCommerceShortcodes);
	}
)();