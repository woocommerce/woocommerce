(() => {
  var __defProp = Object.defineProperty;
  var __defProps = Object.defineProperties;
  var __getOwnPropDescs = Object.getOwnPropertyDescriptors;
  var __getOwnPropSymbols = Object.getOwnPropertySymbols;
  var __hasOwnProp = Object.prototype.hasOwnProperty;
  var __propIsEnum = Object.prototype.propertyIsEnumerable;
  var __defNormalProp = (obj, key, value) => key in obj ? __defProp(obj, key, { enumerable: true, configurable: true, writable: true, value }) : obj[key] = value;
  var __spreadValues = (a, b) => {
    for (var prop in b || (b = {}))
      if (__hasOwnProp.call(b, prop))
        __defNormalProp(a, prop, b[prop]);
    if (__getOwnPropSymbols)
      for (var prop of __getOwnPropSymbols(b)) {
        if (__propIsEnum.call(b, prop))
          __defNormalProp(a, prop, b[prop]);
      }
    return a;
  };
  var __spreadProps = (a, b) => __defProps(a, __getOwnPropDescs(b));

  // plugins/woocommerce-react-settings-custom-field/custom-field.tsx
  var _a;
  var register = (_a = window.wcReactSettings) == null ? void 0 : _a.registerFieldTypeTransformer;
  var _a2;
  var element = (_a2 = window.wp) == null ? void 0 : _a2.element;
  var _a3;
  var i18n = (_a3 = window.wp) == null ? void 0 : _a3.i18n;
  var _a4;
  var components = (_a4 = window.wp) == null ? void 0 : _a4.components;
  var _a5, _b;
  if (register && element && i18n && components) {
    const { __ } = i18n;
    const { createInterpolateElement } = element;
    const { Card, CardBody, Button } = components;
    const assetUrl = ((_b = (_a5 = window.wcSettings) == null ? void 0 : _a5.admin) == null ? void 0 : _b.wcAdminAssetUrl) || "";
    const HelloIncentiveBanner = ({
      value,
      onChange
    }) => /* @__PURE__ */ wp.element.createElement(Card, { className: "woocommerce-incentive-banner", isRounded: true }, /* @__PURE__ */ wp.element.createElement("div", { className: "woocommerce-incentive-banner__content" }, /* @__PURE__ */ wp.element.createElement("div", { className: "woocommerce-incentive-banner__image" }, /* @__PURE__ */ wp.element.createElement(
      "img",
      {
        src: assetUrl + "/settings-payments/incentives-illustration.svg",
        alt: __("Incentive illustration", "woocommerce")
      }
    )), /* @__PURE__ */ wp.element.createElement(CardBody, { className: "woocommerce-incentive-banner__body" }, /* @__PURE__ */ wp.element.createElement("span", { className: "woocommerce-status-badge woocommerce-status-badge--success" }, __("Limited time offer", "woocommerce")), /* @__PURE__ */ wp.element.createElement("div", { className: "woocommerce-incentive-banner__copy" }, /* @__PURE__ */ wp.element.createElement("h2", null, __(
      "Save 10% on processing fees during your first 3 months when you sign up for WooPayments.",
      "woocommerce"
    )), /* @__PURE__ */ wp.element.createElement("p", null, __(
      "Use the native payments solution built and supported by Woo to accept online and in-person payments, track revenue, and handle all payment activity in one place.",
      "woocommerce"
    ))), /* @__PURE__ */ wp.element.createElement("div", { className: "woocommerce-incentive-banner__terms" }, createInterpolateElement ? createInterpolateElement(
      __("See <termsLink /> for details.", "woocommerce"),
      {
        termsLink: /* @__PURE__ */ wp.element.createElement(
          "a",
          {
            href: "https://woocommerce.com/terms-conditions/",
            target: "_blank",
            rel: "noreferrer"
          },
          __(
            "Terms and Conditions",
            "woocommerce"
          )
        )
      }
    ) : null), /* @__PURE__ */ wp.element.createElement("div", { className: "woocommerce-incentive-banner__actions" }, /* @__PURE__ */ wp.element.createElement(Button, { variant: "primary" }, __("Install and save 10%", "woocommerce")), /* @__PURE__ */ wp.element.createElement(Button, { variant: "tertiary" }, __("Dismiss", "woocommerce"))))));
    register("incentive_field", (_setting, baseField) => __spreadProps(__spreadValues({}, baseField), {
      type: "text",
      Edit: HelloIncentiveBanner
    }));
  }
})();
