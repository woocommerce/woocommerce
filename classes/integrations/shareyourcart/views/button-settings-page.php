<?php if(!class_exists('ShareYourCartBase',false)) die('Access Denied'); ?>
<?php if(!$this->isActive()) return; //if the plugin is not active, do not show this page ?>
<div class="wrap">
    <h3>Button options</h3>
    <div id="visual-options">
    	<table class="form-table">
    		<tr>
    			<th class="titledesc" scope="row"><?php _e( 'Button style', 'woocommerce' ); ?></th>
    			<td class="forminp">
    				<fieldset>
    					<legend class="screen-reader-text"><span><?php _e( 'Button style', 'woocommerce' ); ?></span></legend>
						<p>
							<label>
								<input type="radio" value="1" id="button_type_1" <?php if ($current_button_type == '1'||$current_button_type == '') echo 'checked' ?> name="button_type" class="tog" />
								<?php echo SyC::t('sdk','Use Standard Button'); ?>
							</label>
						</p>
						<ul style="margin-left: 18px;" class="shareyourcart_button_standard">
							<li>
								<label for="button_skin">
									<?php echo SyC::t('sdk','Button skin'); ?>:
									<select name="button_skin" id="button_skin">
										<option name="orange" <?php echo $current_skin == 'orange' ? 'selected="selected"' : ''; ?> value="orange"><?php echo SyC::t('sdk','Orange'); ?></option>
										<option name="blue" <?php echo $current_skin == 'blue' ? 'selected="selected"' : ''; ?> value="blue"><?php echo SyC::t('sdk','Blue'); ?></option>
										<option name="light" <?php echo $current_skin == 'light' ? 'selected="selected"' : ''; ?> value="light"><?php echo SyC::t('sdk','Light'); ?></option>
										<option name="dark" <?php echo $current_skin == 'dark' ? 'selected="selected"' : ''; ?> value="dark"><?php echo SyC::t('sdk','Dark'); ?></option>
									</select>
								</label>
							</li>
							<li>
								<label for="button_position">
									<?php echo SyC::t('sdk','Button position'); ?>:
									<select name="button_position" id="button_position">
			                            <option name="normal" <?php echo $current_position == 'normal' ? 'selected="selected"' : ''; ?> value="normal"><?php echo SyC::t('sdk','Normal'); ?></option>
			                            <option name="floating" <?php echo $current_position == 'floating' ? 'selected="selected"' : ''; ?> value="floating"><?php echo SyC::t('sdk','Floating'); ?></option>
			                        </select>
								</label>
							</li>
						</ul>

						<p>
							<label>
								<input type="radio" value="2" id="button_type_2" name="button_type" <?php if ($current_button_type == '2') echo 'checked' ?> class="tog" />
								<?php echo SyC::t('sdk','Use Image Button'); ?>
							</label>
						</p>
						<ul style="margin-left: 18px;" class="shareyourcart_button_image">
							<li>
								<label>
									<?php echo SyC::t('sdk','Image'); ?>:
									<?php if ( ! empty( $button_img ) ): ?>
										<img src="<?php echo $button_img ?>" height="40" /><br/>
									<?php endif; ?>

									<input type="hidden" name="MAX_FILE_SIZE" value="100000000000" />
									<input type="file" accept="image/gif, image/jpeg, image/jpg, image/png" name="button-img" id="button-img" />
								</label>
							</li>
							<li>
								<label>
									<?php echo SyC::t('sdk','Hover Image'); ?>:
									<?php if ( ! empty( $button_img_hover ) ): ?>
										<img src="<?php echo $button_img_hover ?>" height="40" /><br/>
									<?php endif; ?>

									<input type="hidden" name="MAX_FILE_SIZE" value="100000000000" />
									<input type="file" accept="image/gif, image/jpeg, image/jpg, image/png" name="button-img-hover" id="button-img-hover" />
								</label>
							</li>
						</ul>

						<p>
							<label>
								<input type="radio" value="3" id="button_type_3" name="button_type" <?php if ($current_button_type == '3') echo 'checked' ?> class="tog" />
								<?php echo SyC::t('sdk','Custom HTML button'); ?>
							</label>
						</p>
						<ul style="margin-left: 18px;" class="shareyourcart_button_html">
							<li>
								<label>
									<?php echo SyC::t('sdk','HTML'); ?>:
									<textarea style="vertical-align:top" id="syc_button_textarea" class="buttonTextarea" rows="3" cols="56" name="button_html"><?php echo ($button_html!=''?$button_html:'<button>'.SyC::t('sdk','Get a {value} discount',array('{value}' => '<div class="shareyourcart-discount"></div>')).'</button>'); ?></textarea>
								</label>
							</li>
						</ul>
					</fieldset>
        		</td>
    		</tr>
    	</table>
    	<?php echo $html; ?>

        <fieldset>
            <table class="form-table " name="shareyourcart_settings">
                <tr>
                    <th scope="row" valign="top"><?php echo SyC::t('sdk','Show button by default on: '); ?></th>
                    <td>
                        <input class="buttonCheckbox" name="show_on_product" <?php echo $show_on_product ? 'checked="checked"' : ''; ?>  type='checkbox'><?php echo SyC::t('sdk','Product page'); ?></input>
                        <br />
                        <input class="buttonCheckbox" name="show_on_checkout" <?php echo $show_on_checkout ? 'checked="checked"' : ''; ?> type='checkbox'><?php echo SyC::t('sdk','Checkout page'); ?></input>
                    </td>
                </tr>
    			 <tr>
                    <th scope="row" valign="top"><?php echo SyC::t('sdk','Position product button after: '); ?></th>
                    <td>
                        <input name="product_button_position" class="regular-text" value="<?php echo $this->getProductButtonPosition(); ?>" />
                        <span class="description"><?php echo SyC::t('sdk','<strong>jQuery selector</strong>. Start with <strong>{elem}</strong> to position the button before the actual object',array('{elem}' => "/*before*/")); ?></span>
                    </td>
                </tr>
    			 <tr>
                    <th scope="row" valign="top"><?php echo SyC::t('sdk','Position cart button after: '); ?></th>
                    <td>
                        <input name="cart_button_position" class="regular-text" value="<?php echo $this->getCartButtonPosition(); ?>" />
                        <span class="description"><?php echo SyC::t('sdk','<strong>jQuery selector</strong>. Start with <strong>{elem}</strong> to position the button before the actual object',array('{elem}' => "/*before*/")); ?></span>
                    </td>
                </tr>
            </table>
        </fieldset>

        <div class="submit">
        	<input type="submit" class="button button-primary" name="syc-visual-form" value="<?php _e('Save changes', 'woocommerce'); ?>" />
        </div>
    </div>
</div>