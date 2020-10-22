<?php
/**
 * Define deprecated classes to support changing the naming convention of
 * admin notes.
 */

namespace Automattic\WooCommerce\Admin\Notes;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\DeprecatedClassFacade;

// phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound

/**
 * WC_Admin_Note.
 *
 * @deprecated since 1.7.0, use Note
 */
class WC_Admin_Note extends DeprecatedClassFacade {
	// These constants must be redeclared as to not break plugins that use them.
	const E_WC_ADMIN_NOTE_ERROR         = Note::E_WC_ADMIN_NOTE_ERROR;
	const E_WC_ADMIN_NOTE_WARNING       = Note::E_WC_ADMIN_NOTE_WARNING;
	const E_WC_ADMIN_NOTE_UPDATE        = Note::E_WC_ADMIN_NOTE_UPDATE;
	const E_WC_ADMIN_NOTE_INFORMATIONAL = Note::E_WC_ADMIN_NOTE_INFORMATIONAL;
	const E_WC_ADMIN_NOTE_MARKETING     = Note::E_WC_ADMIN_NOTE_MARKETING;
	const E_WC_ADMIN_NOTE_SURVEY        = Note::E_WC_ADMIN_NOTE_SURVEY;
	const E_WC_ADMIN_NOTE_PENDING       = Note::E_WC_ADMIN_NOTE_PENDING;
	const E_WC_ADMIN_NOTE_UNACTIONED    = Note::E_WC_ADMIN_NOTE_UNACTIONED;
	const E_WC_ADMIN_NOTE_ACTIONED      = Note::E_WC_ADMIN_NOTE_ACTIONED;
	const E_WC_ADMIN_NOTE_SNOOZED       = Note::E_WC_ADMIN_NOTE_SNOOZED;

	/**
	 * The name of the non-deprecated class that this facade covers.
	 *
	 * @var string
	 */
	protected static $facade_over_classname = 'Automattic\WooCommerce\Admin\Notes\Note';

	/**
	 * The version that this class was deprecated in.
	 *
	 * @var string
	 */
	protected static $deprecated_in_version = '1.7.0';

	/**
	 * Note constructor. Loads note data.
	 *
	 * @param mixed $data Note data, object, or ID.
	 */
	public function __construct( $data = '' ) {
		$this->instance = new static::$facade_over_classname( $data );
	}
}

/**
 * WC_Admin_Notes.
 *
 * @deprecated since 1.7.0, use Notes
 */
class WC_Admin_Notes extends DeprecatedClassFacade {
	/**
	 * The name of the non-deprecated class that this facade covers.
	 *
	 * @var string
	 */
	protected static $facade_over_classname = 'Automattic\WooCommerce\Admin\Notes\Notes';

	/**
	 * The version that this class was deprecated in.
	 *
	 * @var string
	 */
	protected static $deprecated_in_version = '1.7.0';
}

/**
 * WC_Admin_Notes_Choose_Niche.
 *
 * @deprecated since 1.7.0, use Choose_Niche
 */
class WC_Admin_Notes_Choose_Niche extends DeprecatedClassFacade {
	/**
	 * The name of the non-deprecated class that this facade covers.
	 *
	 * @var string
	 */
	protected static $facade_over_classname = 'Automattic\WooCommerce\Admin\Notes\Choose_Niche';

	/**
	 * The version that this class was deprecated in.
	 *
	 * @var string
	 */
	protected static $deprecated_in_version = '1.7.0';
}

/**
 * WC_Admin_Notes_Coupon_Page_Moved.
 *
 * @deprecated since 1.7.0, use Coupon_Page_Moved
 */
class WC_Admin_Notes_Coupon_Page_Moved extends DeprecatedClassFacade {
	/**
	 * The name of the non-deprecated class that this facade covers.
	 *
	 * @var string
	 */
	protected static $facade_over_classname = 'Automattic\WooCommerce\Admin\Notes\Coupon_Page_Moved';

	/**
	 * The version that this class was deprecated in.
	 *
	 * @var string
	 */
	protected static $deprecated_in_version = '1.7.0';
}

/**
 * WC_Admin_Notes_Customize_Store_With_Blocks.
 *
 * @deprecated since 1.7.0, use Customize_Store_With_Blocks
 */
class WC_Admin_Notes_Customize_Store_With_Blocks extends DeprecatedClassFacade {
	/**
	 * The name of the non-deprecated class that this facade covers.
	 *
	 * @var string
	 */
	protected static $facade_over_classname = 'Automattic\WooCommerce\Admin\Notes\Customize_Store_With_Blocks';

	/**
	 * The version that this class was deprecated in.
	 *
	 * @var string
	 */
	protected static $deprecated_in_version = '1.7.0';
}

/**
 * WC_Admin_Notes_Deactivate_Plugin.
 *
 * @deprecated since 1.7.0, use Deactivate_Plugin
 */
class WC_Admin_Notes_Deactivate_Plugin extends DeprecatedClassFacade {
	/**
	 * The name of the non-deprecated class that this facade covers.
	 *
	 * @var string
	 */
	protected static $facade_over_classname = 'Automattic\WooCommerce\Admin\Notes\Deactivate_Plugin';

	/**
	 * The version that this class was deprecated in.
	 *
	 * @var string
	 */
	protected static $deprecated_in_version = '1.7.0';
}

/**
 * WC_Admin_Notes_Draw_Attention.
 *
 * @deprecated since 1.7.0, use Draw_Attention
 */
class WC_Admin_Notes_Draw_Attention extends DeprecatedClassFacade {
	/**
	 * The name of the non-deprecated class that this facade covers.
	 *
	 * @var string
	 */
	protected static $facade_over_classname = 'Automattic\WooCommerce\Admin\Notes\Draw_Attention';

	/**
	 * The version that this class was deprecated in.
	 *
	 * @var string
	 */
	protected static $deprecated_in_version = '1.7.0';
}

/**
 * WC_Admin_Notes_Edit_Products_On_The_Move.
 *
 * @deprecated since 1.7.0, use Edit_Products_On_The_Move
 */
class WC_Admin_Notes_Edit_Products_On_The_Move extends DeprecatedClassFacade {
	/**
	 * The name of the non-deprecated class that this facade covers.
	 *
	 * @var string
	 */
	protected static $facade_over_classname = 'Automattic\WooCommerce\Admin\Notes\Edit_Products_On_The_Move';

	/**
	 * The version that this class was deprecated in.
	 *
	 * @var string
	 */
	protected static $deprecated_in_version = '1.7.0';
}

/**
 * WC_Admin_Notes_EU_VAT_Number.
 *
 * @deprecated since 1.7.0, use EU_VAT_Number
 */
class WC_Admin_Notes_EU_VAT_Number extends DeprecatedClassFacade {
	/**
	 * The name of the non-deprecated class that this facade covers.
	 *
	 * @var string
	 */
	protected static $facade_over_classname = 'Automattic\WooCommerce\Admin\Notes\EU_VAT_Number';

	/**
	 * The version that this class was deprecated in.
	 *
	 * @var string
	 */
	protected static $deprecated_in_version = '1.7.0';
}

/**
 * WC_Admin_Notes_Facebook_Marketing_Expert.
 *
 * @deprecated since 1.7.0, use Facebook_Marketing_Expert
 */
class WC_Admin_Notes_Facebook_Marketing_Expert extends DeprecatedClassFacade {
	/**
	 * The name of the non-deprecated class that this facade covers.
	 *
	 * @var string
	 */
	protected static $facade_over_classname = 'Automattic\WooCommerce\Admin\Notes\Facebook_Marketing_Expert';

	/**
	 * The version that this class was deprecated in.
	 *
	 * @var string
	 */
	protected static $deprecated_in_version = '1.7.0';
}

/**
 * WC_Admin_Notes_First_Product.
 *
 * @deprecated since 1.7.0, use First_Product
 */
class WC_Admin_Notes_First_Product extends DeprecatedClassFacade {
	/**
	 * The name of the non-deprecated class that this facade covers.
	 *
	 * @var string
	 */
	protected static $facade_over_classname = 'Automattic\WooCommerce\Admin\Notes\First_Product';

	/**
	 * The version that this class was deprecated in.
	 *
	 * @var string
	 */
	protected static $deprecated_in_version = '1.7.0';
}

/**
 * WC_Admin_Notes_Giving_Feedback_Notes.
 *
 * @deprecated since 1.7.0, use Giving_Feedback_Notes
 */
class WC_Admin_Notes_Giving_Feedback_Notes extends DeprecatedClassFacade {
	/**
	 * The name of the non-deprecated class that this facade covers.
	 *
	 * @var string
	 */
	protected static $facade_over_classname = 'Automattic\WooCommerce\Admin\Notes\Giving_Feedback_Notes';

	/**
	 * The version that this class was deprecated in.
	 *
	 * @var string
	 */
	protected static $deprecated_in_version = '1.7.0';
}

/**
 * WC_Admin_Notes_Historical_Data.
 *
 * @deprecated since 1.7.0, use Historical_Data
 */
class WC_Admin_Notes_Historical_Data extends DeprecatedClassFacade {
	/**
	 * The name of the non-deprecated class that this facade covers.
	 *
	 * @var string
	 */
	protected static $facade_over_classname = 'Automattic\WooCommerce\Admin\Notes\Historical_Data';

	/**
	 * The version that this class was deprecated in.
	 *
	 * @var string
	 */
	protected static $deprecated_in_version = '1.7.0';
}

/**
 * WC_Admin_Notes_Home_Screen_Feedback.
 *
 * @deprecated since 1.7.0, use Home_Screen_Feedback
 */
class WC_Admin_Notes_Home_Screen_Feedback extends DeprecatedClassFacade {
	/**
	 * The name of the non-deprecated class that this facade covers.
	 *
	 * @var string
	 */
	protected static $facade_over_classname = 'Automattic\WooCommerce\Admin\Notes\Home_Screen_Feedback';

	/**
	 * The version that this class was deprecated in.
	 *
	 * @var string
	 */
	protected static $deprecated_in_version = '1.7.0';
}

/**
 * WC_Admin_Notes_Insight_First_Sale.
 *
 * @deprecated since 1.7.0, use Insight_First_Sale
 */
class WC_Admin_Notes_Insight_First_Sale extends DeprecatedClassFacade {
	/**
	 * The name of the non-deprecated class that this facade covers.
	 *
	 * @var string
	 */
	protected static $facade_over_classname = 'Automattic\WooCommerce\Admin\Notes\Insight_First_Sale';

	/**
	 * The version that this class was deprecated in.
	 *
	 * @var string
	 */
	protected static $deprecated_in_version = '1.7.0';
}

/**
 * WC_Admin_Notes_Install_JP_And_WCS_Plugins.
 *
 * @deprecated since 1.7.0, use Install_JP_And_WCS_Plugins
 */
class WC_Admin_Notes_Install_JP_And_WCS_Plugins extends DeprecatedClassFacade {
	/**
	 * The name of the non-deprecated class that this facade covers.
	 *
	 * @var string
	 */
	protected static $facade_over_classname = 'Automattic\WooCommerce\Admin\Notes\Install_JP_And_WCS_Plugins';

	/**
	 * The version that this class was deprecated in.
	 *
	 * @var string
	 */
	protected static $deprecated_in_version = '1.7.0';
}

/**
 * WC_Admin_Notes_Launch_Checklist.
 *
 * @deprecated since 1.7.0, use Launch_Checklist
 */
class WC_Admin_Notes_Launch_Checklist extends DeprecatedClassFacade {
	/**
	 * The name of the non-deprecated class that this facade covers.
	 *
	 * @var string
	 */
	protected static $facade_over_classname = 'Automattic\WooCommerce\Admin\Notes\Launch_Checklist';

	/**
	 * The version that this class was deprecated in.
	 *
	 * @var string
	 */
	protected static $deprecated_in_version = '1.7.0';
}

/**
 * WC_Admin_Notes_Marketing.
 *
 * @deprecated since 1.7.0, use Marketing
 */
class WC_Admin_Notes_Marketing extends DeprecatedClassFacade {
	/**
	 * The name of the non-deprecated class that this facade covers.
	 *
	 * @var string
	 */
	protected static $facade_over_classname = 'Automattic\WooCommerce\Admin\Notes\Marketing';

	/**
	 * The version that this class was deprecated in.
	 *
	 * @var string
	 */
	protected static $deprecated_in_version = '1.7.0';
}

/**
 * WC_Admin_Notes_Migrate_From_Shopify.
 *
 * @deprecated since 1.7.0, use Migrate_From_Shopify
 */
class WC_Admin_Notes_Migrate_From_Shopify extends DeprecatedClassFacade {
	/**
	 * The name of the non-deprecated class that this facade covers.
	 *
	 * @var string
	 */
	protected static $facade_over_classname = 'Automattic\WooCommerce\Admin\Notes\Migrate_From_Shopify';

	/**
	 * The version that this class was deprecated in.
	 *
	 * @var string
	 */
	protected static $deprecated_in_version = '1.7.0';
}

/**
 * WC_Admin_Notes_Mobile_App.
 *
 * @deprecated since 1.7.0, use Mobile_App
 */
class WC_Admin_Notes_Mobile_App extends DeprecatedClassFacade {
	/**
	 * The name of the non-deprecated class that this facade covers.
	 *
	 * @var string
	 */
	protected static $facade_over_classname = 'Automattic\WooCommerce\Admin\Notes\Mobile_App';

	/**
	 * The version that this class was deprecated in.
	 *
	 * @var string
	 */
	protected static $deprecated_in_version = '1.7.0';
}

/**
 * WC_Admin_Notes_Need_Some_Inspiration.
 *
 * @deprecated since 1.7.0, use Need_Some_Inspiration
 */
class WC_Admin_Notes_Need_Some_Inspiration extends DeprecatedClassFacade {
	/**
	 * The name of the non-deprecated class that this facade covers.
	 *
	 * @var string
	 */
	protected static $facade_over_classname = 'Automattic\WooCommerce\Admin\Notes\Need_Some_Inspiration';

	/**
	 * The version that this class was deprecated in.
	 *
	 * @var string
	 */
	protected static $deprecated_in_version = '1.7.0';
}

/**
 * WC_Admin_Notes_New_Sales_Record.
 *
 * @deprecated since 1.7.0, use New_Sales_Record
 */
class WC_Admin_Notes_New_Sales_Record extends DeprecatedClassFacade {
	/**
	 * The name of the non-deprecated class that this facade covers.
	 *
	 * @var string
	 */
	protected static $facade_over_classname = 'Automattic\WooCommerce\Admin\Notes\New_Sales_Record';

	/**
	 * The version that this class was deprecated in.
	 *
	 * @var string
	 */
	protected static $deprecated_in_version = '1.7.0';
}

/**
 * WC_Admin_Notes_Onboarding_Email_Marketing.
 *
 * @deprecated since 1.7.0, use Onboarding_Email_Marketing
 */
class WC_Admin_Notes_Onboarding_Email_Marketing extends DeprecatedClassFacade {
	/**
	 * The name of the non-deprecated class that this facade covers.
	 *
	 * @var string
	 */
	protected static $facade_over_classname = 'Automattic\WooCommerce\Admin\Notes\Onboarding_Email_Marketing';

	/**
	 * The version that this class was deprecated in.
	 *
	 * @var string
	 */
	protected static $deprecated_in_version = '1.7.0';
}

/**
 * WC_Admin_Notes_Onboarding_Payments.
 *
 * @deprecated since 1.7.0, use Onboarding_Payments
 */
class WC_Admin_Notes_Onboarding_Payments extends DeprecatedClassFacade {
	/**
	 * The name of the non-deprecated class that this facade covers.
	 *
	 * @var string
	 */
	protected static $facade_over_classname = 'Automattic\WooCommerce\Admin\Notes\Onboarding_Payments';

	/**
	 * The version that this class was deprecated in.
	 *
	 * @var string
	 */
	protected static $deprecated_in_version = '1.7.0';
}

/**
 * WC_Admin_Notes_Onboarding_Profiler.
 *
 * @deprecated since 1.7.0, use Onboarding_Profiler
 */
class WC_Admin_Notes_Onboarding_Profiler extends DeprecatedClassFacade {
	/**
	 * The name of the non-deprecated class that this facade covers.
	 *
	 * @var string
	 */
	protected static $facade_over_classname = 'Automattic\WooCommerce\Admin\Notes\Onboarding_Profiler';

	/**
	 * The version that this class was deprecated in.
	 *
	 * @var string
	 */
	protected static $deprecated_in_version = '1.7.0';
}

/**
 * WC_Admin_Notes_Online_Clothing_Store.
 *
 * @deprecated since 1.7.0, use Online_Clothing_Store
 */
class WC_Admin_Notes_Online_Clothing_Store extends DeprecatedClassFacade {
	/**
	 * The name of the non-deprecated class that this facade covers.
	 *
	 * @var string
	 */
	protected static $facade_over_classname = 'Automattic\WooCommerce\Admin\Notes\Online_Clothing_Store';

	/**
	 * The version that this class was deprecated in.
	 *
	 * @var string
	 */
	protected static $deprecated_in_version = '1.7.0';
}

/**
 * WC_Admin_Notes_Order_Milestones.
 *
 * @deprecated since 1.7.0, use Order_Milestones
 */
class WC_Admin_Notes_Order_Milestones extends DeprecatedClassFacade {
	/**
	 * The name of the non-deprecated class that this facade covers.
	 *
	 * @var string
	 */
	protected static $facade_over_classname = 'Automattic\WooCommerce\Admin\Notes\Order_Milestones';

	/**
	 * The version that this class was deprecated in.
	 *
	 * @var string
	 */
	protected static $deprecated_in_version = '1.7.0';
}

/**
 * WC_Admin_Notes_Performance_On_Mobile.
 *
 * @deprecated since 1.7.0, use Performance_On_Mobile
 */
class WC_Admin_Notes_Performance_On_Mobile extends DeprecatedClassFacade {
	/**
	 * The name of the non-deprecated class that this facade covers.
	 *
	 * @var string
	 */
	protected static $facade_over_classname = 'Automattic\WooCommerce\Admin\Notes\Performance_On_Mobile';

	/**
	 * The version that this class was deprecated in.
	 *
	 * @var string
	 */
	protected static $deprecated_in_version = '1.7.0';
}

/**
 * WC_Admin_Notes_Personalize_Store.
 *
 * @deprecated since 1.7.0, use Personalize_Store
 */
class WC_Admin_Notes_Personalize_Store extends DeprecatedClassFacade {
	/**
	 * The name of the non-deprecated class that this facade covers.
	 *
	 * @var string
	 */
	protected static $facade_over_classname = 'Automattic\WooCommerce\Admin\Notes\Personalize_Store';

	/**
	 * The version that this class was deprecated in.
	 *
	 * @var string
	 */
	protected static $deprecated_in_version = '1.7.0';
}

/**
 * WC_Admin_Notes_Real_Time_Order_Alerts.
 *
 * @deprecated since 1.7.0, use Real_Time_Order_Alerts
 */
class WC_Admin_Notes_Real_Time_Order_Alerts extends DeprecatedClassFacade {
	/**
	 * The name of the non-deprecated class that this facade covers.
	 *
	 * @var string
	 */
	protected static $facade_over_classname = 'Automattic\WooCommerce\Admin\Notes\Real_Time_Order_Alerts';

	/**
	 * The version that this class was deprecated in.
	 *
	 * @var string
	 */
	protected static $deprecated_in_version = '1.7.0';
}

/**
 * WC_Admin_Notes_Review_Shipping_Settings.
 *
 * @deprecated since 1.7.0, use Review_Shipping_Settings
 */
class WC_Admin_Notes_Review_Shipping_Settings extends DeprecatedClassFacade {
	/**
	 * The name of the non-deprecated class that this facade covers.
	 *
	 * @var string
	 */
	protected static $facade_over_classname = 'Automattic\WooCommerce\Admin\Notes\Review_Shipping_Settings';

	/**
	 * The version that this class was deprecated in.
	 *
	 * @var string
	 */
	protected static $deprecated_in_version = '1.7.0';
}

/**
 * WC_Admin_Notes_Selling_Online_Courses.
 *
 * @deprecated since 1.7.0, use Selling_Online_Courses
 */
class WC_Admin_Notes_Selling_Online_Courses extends DeprecatedClassFacade {
	/**
	 * The name of the non-deprecated class that this facade covers.
	 *
	 * @var string
	 */
	protected static $facade_over_classname = 'Automattic\WooCommerce\Admin\Notes\Selling_Online_Courses';

	/**
	 * The version that this class was deprecated in.
	 *
	 * @var string
	 */
	protected static $deprecated_in_version = '1.7.0';
}

/**
 * WC_Admin_Notes_Set_Up_Additional_Payment_Types.
 *
 * @deprecated since 1.7.0, use Set_Up_Additional_Payment_Types
 */
class WC_Admin_Notes_Set_Up_Additional_Payment_Types extends DeprecatedClassFacade {
	/**
	 * The name of the non-deprecated class that this facade covers.
	 *
	 * @var string
	 */
	protected static $facade_over_classname = 'Automattic\WooCommerce\Admin\Notes\Set_Up_Additional_Payment_Types';

	/**
	 * The version that this class was deprecated in.
	 *
	 * @var string
	 */
	protected static $deprecated_in_version = '1.7.0';
}

/**
 * WC_Admin_Notes_Start_Dropshipping_Business.
 *
 * @deprecated since 1.7.0, use Start_Dropshipping_Business
 */
class WC_Admin_Notes_Start_Dropshipping_Business extends DeprecatedClassFacade {
	/**
	 * The name of the non-deprecated class that this facade covers.
	 *
	 * @var string
	 */
	protected static $facade_over_classname = 'Automattic\WooCommerce\Admin\Notes\Start_Dropshipping_Business';

	/**
	 * The version that this class was deprecated in.
	 *
	 * @var string
	 */
	protected static $deprecated_in_version = '1.7.0';
}

/**
 * WC_Admin_Notes_Test_Checkout.
 *
 * @deprecated since 1.7.0, use Test_Checkout
 */
class WC_Admin_Notes_Test_Checkout extends DeprecatedClassFacade {
	/**
	 * The name of the non-deprecated class that this facade covers.
	 *
	 * @var string
	 */
	protected static $facade_over_classname = 'Automattic\WooCommerce\Admin\Notes\Test_Checkout';

	/**
	 * The version that this class was deprecated in.
	 *
	 * @var string
	 */
	protected static $deprecated_in_version = '1.7.0';
}

/**
 * WC_Admin_Notes_Tracking_Opt_In.
 *
 * @deprecated since 1.7.0, use Tracking_Opt_In
 */
class WC_Admin_Notes_Tracking_Opt_In extends DeprecatedClassFacade {
	/**
	 * The name of the non-deprecated class that this facade covers.
	 *
	 * @var string
	 */
	protected static $facade_over_classname = 'Automattic\WooCommerce\Admin\Notes\Tracking_Opt_In';

	/**
	 * The version that this class was deprecated in.
	 *
	 * @var string
	 */
	protected static $deprecated_in_version = '1.7.0';
}

/**
 * WC_Admin_Notes_Woo_Subscriptions_Notes.
 *
 * @deprecated since 1.7.0, use Woo_Subscriptions_Notes
 */
class WC_Admin_Notes_Woo_Subscriptions_Notes extends DeprecatedClassFacade {
	/**
	 * The name of the non-deprecated class that this facade covers.
	 *
	 * @var string
	 */
	protected static $facade_over_classname = 'Automattic\WooCommerce\Admin\Notes\Woo_Subscriptions_Notes';

	/**
	 * The version that this class was deprecated in.
	 *
	 * @var string
	 */
	protected static $deprecated_in_version = '1.7.0';
}

/**
 * WC_Admin_Notes_WooCommerce_Payments.
 *
 * @deprecated since 1.7.0, use WooCommerce_Payments
 */
class WC_Admin_Notes_WooCommerce_Payments extends DeprecatedClassFacade {
	/**
	 * The name of the non-deprecated class that this facade covers.
	 *
	 * @var string
	 */
	protected static $facade_over_classname = 'Automattic\WooCommerce\Admin\Notes\WooCommerce_Payments';

	/**
	 * The version that this class was deprecated in.
	 *
	 * @var string
	 */
	protected static $deprecated_in_version = '1.7.0';
}

/**
 * WC_Admin_Notes_WooCommerce_Subscriptions.
 *
 * @deprecated since 1.7.0, use WooCommerce_Subscriptions
 */
class WC_Admin_Notes_WooCommerce_Subscriptions extends DeprecatedClassFacade {
	/**
	 * The name of the non-deprecated class that this facade covers.
	 *
	 * @var string
	 */
	protected static $facade_over_classname = 'Automattic\WooCommerce\Admin\Notes\WooCommerce_Subscriptions';

	/**
	 * The version that this class was deprecated in.
	 *
	 * @var string
	 */
	protected static $deprecated_in_version = '1.7.0';
}
