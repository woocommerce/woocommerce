import config from 'config';
import chai from 'chai';
import chaiAsPromised from 'chai-as-promised';
import test from 'selenium-webdriver/testing';
import { WebDriverManager, WebDriverHelper as helper } from 'wp-e2e-webdriver';
import { WPLogin } from 'wp-e2e-page-objects';
import { WPAdminProductNew } from 'wc-e2e-page-objects';

chai.use( chaiAsPromised );
const assert = chai.assert;

let manager;
let driver;

test.describe( 'Add New Product Page', function() {
	test.before( 'open browser', function() {
		this.timeout( config.get( 'startBrowserTimeoutMs' ) );

		manager = new WebDriverManager( 'chrome', { baseUrl: config.get( 'url' ) } );
		driver = manager.getDriver();

		helper.clearCookiesAndDeleteLocalStorage( driver );
	} );

	this.timeout( config.get( 'mochaTimeoutMs' ) );

	test.before( 'login', () => {
		const wpLogin = new WPLogin( driver, { url: manager.getPageUrl( '/wp-login.php' ) } );
		wpLogin.login( config.get( 'users.admin.username' ), config.get( 'users.admin.password' ) );
	} );

	test.it( 'can create simple virtual product titled "Simple Product" with regular price $9.99', () => {
		const product = new WPAdminProductNew( driver, { url: manager.getPageUrl( '/wp-admin/post-new.php?post_type=product' ) } );
		product.setTitle( 'Simple Product' );

		const productData = product.components.metaBoxProductData;
		productData.selectProductType( 'Simple product' );
		productData.checkVirtual();

		const panelGeneral = productData.clickTab( 'General' );
		panelGeneral.setRegularPrice( '9.99' );

		product.publish();
		assert.eventually.ok( product.hasNotice( 'Product published.' ) );

		product.moveToTrash();
		assert.eventually.ok( product.hasNotice( '1 product moved to the Trash.' ) );
	} );

	test.it( 'can create product with variations', () => {
		const product = new WPAdminProductNew( driver, { url: manager.getPageUrl( '/wp-admin/post-new.php?post_type=product' ) } );
		product.setTitle( 'Variable Product with Two Variations' );

		const productData = product.components.metaBoxProductData;
		productData.selectProductType( 'Variable product' );

		const panelAttributes = productData.clickTab( 'Attributes' );
		panelAttributes.selectAttribute( 'Custom product attribute' );

		const attr1 = panelAttributes.add();
		assert.eventually.ok( attr1.displayed() );
		attr1.setName( 'attr #1' );
		attr1.checkVisibleOnTheProductPage();
		attr1.checkUsedForVariations();
		attr1.setValue( 'val1 | val2' );
		attr1.toggle();

		const attr2 = panelAttributes.add();
		assert.eventually.ok( attr1.displayed() );
		attr2.setName( 'attr #2' );
		attr2.checkVisibleOnTheProductPage();
		attr2.checkUsedForVariations();
		attr2.setValue( 'val1 | val2' );

		const attr3 = panelAttributes.add();
		assert.eventually.ok( attr1.displayed() );
		attr3.setName( 'attr #3' );
		attr3.checkVisibleOnTheProductPage();
		attr3.checkUsedForVariations();
		attr3.setValue( 'val1 | val2' );
		attr3.toggle();	attr2.toggle();

		panelAttributes.saveAttributes();

		const panelVaritions = productData.clickTab( 'Variations' );
		panelVaritions.selectAction( 'Create variations from all attributes' );
		panelVaritions.go();

		const var1 = panelVaritions.getRow( 1 );
		var1.toggle();
		var1.checkEnabled();
		var1.checkVirtual();
		var1.setRegularPrice( '9.99' );

		const var2 = panelVaritions.getRow( 2 );
		var2.toggle();
		var2.checkEnabled();
		var2.checkVirtual();
		var2.setRegularPrice( '11.99' );

		const var3 = panelVaritions.getRow( 3 );
		var3.toggle();
		var3.checkEnabled();
		var3.checkManageStock();
		var3.setRegularPrice( '20' );
		var3.setWeight( '200' );
		var3.setDimensionLength( '10' );
		var3.setDimensionWidth( '20' );
		var3.setDimensionHeight( '15' );

		panelVaritions.saveChanges();

		helper.scrollUp( driver );
		helper.scrollUp( driver );
		helper.scrollUp( driver );

		product.publish();
		assert.eventually.ok( product.hasNotice( 'Product published.' ) );

		product.moveToTrash();
		assert.eventually.ok( product.hasNotice( '1 product moved to the Trash.' ) );
	} );

	test.after( 'quit browser', () => {
		manager.quitBrowser();
	} );
} );
