---
post_title: Accessibility Standards for WooCommerce extensions
sidebar_label: Accessibility
sidebar_position: 1
---

# Accessibility Best Practices

In many places around the world, ecommerce stores are required to be accessible to people with disabilities. As a WooCommerce extension developer, your code directly impacts the accessibility of the shops that use it and can impact merchants’ compliance with accessibility laws.

This page is a resource for developers who want to ensure their extensions comply with accessibility standards and best practices. It is recommended that all WooCommerce extensions follow accessibility best practices to provide the best experience for merchants and their customers.

## Accessibility Laws

Two examples of laws that require ecommerce websites to be accessible include the [Americans with Disabilities Act (ADA)](https://ada.gov/) in the United States and the [European Accessibility Act (EAA)](https://employment-social-affairs.ec.europa.eu/policies-and-activities/social-protection-social-inclusion/persons-disabilities/union-equality-strategy-rights-persons-disabilities-2021-2030/european-accessibility-act_en), a directive that applies to all EU member countries. There are also laws requiring website accessibility in Australia, Canada, Israel, and many other countries. The W3C maintains a list of [web accessibility laws worldwide](https://www.w3.org/WAI/policies/) if you want to research laws in your country.

Most laws require websites to conform to Web Content Accessibility Guidelines (WCAG) as a measure of accessibility. To ensure that merchants can use your extension on their websites, it should also be WCAG conformant. 

If your extension is not WCAG conformant, you may receive complaints about accessibility issues from merchants. You may also be included in lawsuits or legal cases about accessibility problems caused by your extension. 

## Web Content Accessibility Guidelines (WCAG)

The [Web Content Accessibility Guidelines (WCAG)](https://www.w3.org/TR/WCAG22) are internationally recognized standards designed to make websites accessible to everyone, including people with disabilities. WCAG is made up of *success criteria* (specific, testable rules) that fall under four key principles:

### Four Key Principles

- **Perceivable**  
  Content should be available in ways users can perceive, even if they do not have all five senses. Information should be transformable from one form to another (e.g., text alternatives for images or captions for videos).

- **Operable**  
  Users should be able to interact with the site easily using alternative devices (such as a keyboard only or screen reader without a mouse), and there should be no time-sensitive actions.

- **Understandable**  
  Content and interfaces should be clear and consistent with simple language and predictable navigation.

- **Robust**  
  Websites should work well with assistive technologies and across different devices.

### Compliance Levels

WCAG has three levels of compliance:

- **Level A** – Basic
- **Level AA** – Mid-range (required by most laws)
- **Level AAA** – Highest

Most regulations require **Level AA**, meaning you need to meet all **A** and **AA** success criteria.

### Current Version

The current version of WCAG is **2.2**.  
**Extension developers should aim for WCAG 2.2 Level AA compliance** as a best practice.

## Manual compatibility testing

It’s important to test your extension for [WCAG conformance](https://www.w3.org/TR/WCAG22) as you design and develop new features. Testing for accessibility is just as important as testing for security or WordPress coding standards. If you have not previously tested your extension for accessibility, start testing today and add accessibility bug fixes in future releases.

### Automated Testing

The easiest way to start accessibility testing is with an automated testing tool. Automated tools can quickly identify problems like empty buttons, ambiguous links, color contrast failures, missing alternative text, and more.

[Accessibility Checker](https://wordpress.org/plugins/accessibility-checker/) is a free WordPress plugin that you can use to test your extension. Simply install it in your test environment and add blocks or shortcodes created by your extension to a page. When you save the page, Accessibility Checker will scan the blocks or rendered shortcodes and provide a list of issues to address.

The [WAVE browser extension](https://wave.webaim.org/extension/) is another free automated testing tool. This browser extension can be used on any website and is helpful if you want to find accessibility problems on your extension’s admin pages.

### Keyboard Testing

After resolving issues from automated testing, the next step is to ensure that your extension can be used without a mouse and with a keyboard alone.

To test your extension for keyboard accessibility, go to the part of a test site controlled by your extension. Using the Tab key, move forward through the web page, ensuring that the following is true:

- All interactive components (buttons, links, inputs, etc.) can be reached by hitting the Tab key.
- Focus moves intuitively from left to right and top to bottom down the page.
- You can move backward through the components by hitting Shift + Tab.
- There are no keyboard traps. A keyboard trap is when focus lands on an element and then cannot go forward or backward.
- There is a visible focus outline on all interactive components. Also confirm that you have never set `:focus` in your stylesheet to `outline:none;` or `outline:0;`. It is never correct to remove focus outlines.
- If a button triggers a modal or dialog, focus should be moved into the dialog, and it should not be possible to tab out of the dialog without closing it.
- Buttons should be able to be triggered with both the Spacebar and the Return key. 
- Links should be able to be triggered with the Return key.

Test all aspects of your extension on the front end and in the admin for full functionality without a mouse.

### Test When Zoomed 

Ensure your extension can be used by low-vision users interacting with their browser zoomed in. Set your browser zoom to both 200% and 400% and ensure that no content overlaps or is lost and that the extension’s components can be used while the site is zoomed in.

### Screen Reader Testing

Familiarize yourself with screen readers, the assistive technology used by people who are blind or visually impaired, and use them to test your extension. There are two free screen readers that you can use for testing:

- **VoiceOver**: If you have a Mac, VoiceOver is built into your computer and is a great place to start, but it is not the preferred screen reader for blind desktop users.
- **NVDA**: NVDA is a free, open-source screen reader that you can download and use on your PC. This is the preferred screen reader for testing, as most blind people are Windows users.

To test your extension for screen reader accessibility, turn on the screen reader and use your keyboard to navigate the web page. Have the screen reader read the entire web page for you and listen for the following:

- All content and elements are read out – nothing meaningful is skipped.
- Images and graphics have accurate descriptions.
- Decorative images are skipped.
- Elements have the correct role: buttons should be announced as buttons, links as links, etc.
- Links and buttons have names that describe their purpose.
- Form fields are correctly labeled.
- States of components like tabs, accordions, and toggle buttons are announced as collapsed, selected, pressed, etc.
- Status changes are announced by the screen reader (e.g., success and error messages, changes in the number of visible products, opening of a modal or dialog, etc.).
- Hidden content is not read out.

### Test for Motion Sensitivity 

Website animations and motion can be distracting or cause physical illness for some users. To ensure your extension passes WCAG success criteria for motion:

- Provide a pause button for any auto-playing content that lasts longer than 5 seconds (e.g., videos, background videos, sliders/carousels, or animated GIFs).
- Avoid flashing content that exceeds three flashes per second, as it can potentially trigger seizures.
- Ensure all animations are disabled if the user has enabled the “reduce motion” setting in their operating system.

You can test if your animations respect the “prefers reduced motion” setting by enabling it on your operating system. Here’s how to do this:

- **On Windows 11**: Go to *Settings > Accessibility > Visual Effects > Animation Effects*.
- **On macOS**: Navigate to *System Preferences > Accessibility > Display > Reduce motion*.

Learn more about coding [prefers-reduced-motion media queries](https://developer.mozilla.org/en-US/docs/Web/CSS/@media/prefers-reduced-motion).

### User Testing 

Consider bringing in actual users with disabilities to test your extensions for accessibility. Seeing someone who relies on a screen reader or other assistive device use your products can be very enlightening. Ask your customer base to see if you already have people with disabilities in your audience available to test and provide feedback. Here’s a great resource on [how to run user testing sessions](https://www.w3.org/WAI/test-evaluate/involving-users/).

If you don’t want to run your own user testing sessions, there are [WooExperts](https://woocommerce.com/for-agencies/) who can help with this.

## Additional Considerations

### Make Your Website Accessible

In addition to testing your extension for accessibility best practices, you should be thinking about the accessibility of your own website and documentation. If your business meets certain revenue thresholds, the ecommerce store where people buy your products must be accessible and WCAG conformant.

### Include Captions for Your Videos 

If your documentation includes videos, those videos must be captioned and include transcripts. This is important to ensure everyone can access your documentation and learn to use your extension.

### Guiding Your Customers 

Even if your extension is perfectly accessible, merchants can introduce accessibility problems when configuring your extension or adding content. Whenever possible, define accessible defaults. For example, the default colors in your extension should always pass WCAG AA color contrast requirements. Merchants may change these colors to a combination that fails contrast, but you can say your extension is “accessibility-ready” if the default passes.

Consider adding functionality in the admin that alerts merchants when they make a choice that negatively impacts accessibility. For example, you can flag color combinations that fail contrast checks, similar to how it’s done in WordPress core. Other things you might warn merchants about include empty field labels, headings out of order, empty alt text, or choosing settings you must maintain for backward compatibility but know are not accessible.

### Create an Accessibility Conformance Report 

Many organizations, especially in government and education, require an Accessibility Conformance Report (ACR) before purchasing software. An ACR can speed up the procurement process if your extension is sold to large businesses, government, or educational institutions.

An Accessibility Conformance Report is a document that outlines the accessibility compliance of a digital product, against recognized accessibility standards like WCAG, Section 508 (US), and Europe’s EN 301 549 accessibility standard.

ACRs are created using a Voluntary Product Accessibility Template (VPAT), a standardized format for assessing and reporting accessibility features. You can download this template for free and fill it in yourself, or hire an accessibility specialist to audit your extension and complete the VPAT for you.

### Get Help With Compliance 

WCAG compliance doesn’t have to feel overwhelming! You can test and fix one issue at a time, incrementally improving your extension. However, accessibility goes faster if you don’t have to memorize WCAG or learn how to test first. Consider hiring an accessibility specialist to help with auditing or user testing your extension. The right company or consultant will help you find problems quickly, prioritize fixes, and train your team so fewer accessibility issues will be added in the future.

You can find accessibility-focused developers in our [WooExperts directory](https://woocommerce.com/for-agencies/).

## Learning More About Accessibility 

We recommend these resources if you want to learn more about website accessibility:

- [WordPress Accessibility Meetup](https://www.meetup.com/wordpress-accessibility-meetup/)
- [WP Accessibility Day Conference](https://wpaccessibility.day/)
- [WordPress Accessibility Ready Requirements](https://make.wordpress.org/themes/handbook/review/accessibility/)
- [WordCamp Europe Accessibility Testing Workshop](https://europe.wordcamp.org/2023/accessibility-testing-workshop/)
- [Web Accessibility Specialist Certification from the International Association of Accessibility Professionals](https://www.accessibilityassociation.org/specialist)
