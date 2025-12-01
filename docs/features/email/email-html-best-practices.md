---
post_title: HTML best practices
sidebar_label: HTML best practices
---

# Email HTML - Best Practices

<!-- markdownlint-disable MD024 -->

## Overview

Email design and development require a different approach than traditional web development. This guide outlines best practices for ensuring your HTML emails render correctly across all major email clients.

### Key principles

- **Width**: Keep your email width between 600-640px - This ensures your email displays properly across all devices without horizontal scrolling on smaller screens.
- **File size**: Keep total email size under 100KB - Large emails may be clipped by Gmail and other providers, and take longer to load on mobile devices.
- **Tables**: Use tables for layout (despite being deprecated for the web) - Email clients have inconsistent CSS support but reliable table rendering.
- **Testing**: Test across multiple email clients and devices - Email clients render HTML differently, and testing helps identify and fix rendering issues.

## HTML

### Use older, simpler HTML standards

- Use HTML 4.01 or XHTML 1.0 - Many email clients use outdated rendering engines and don't support modern HTML5 features.
- Avoid HTML5 elements in the main structure - Elements like `<section>`, `<article>`, and `<aside>` aren't supported in all email clients.
- Always use lowercase for tags and attributes - This ensures maximum compatibility and prevents rendering issues in strict clients.
- Always use quotes for attribute values - Unquoted attributes can cause parsing errors in some email clients.
- Close all tags, even self-closing ones with a trailing slash (`<br />`) - This prevents rendering issues in clients that expect XHTML-style syntax.

### Tables as the foundation

- Use nested tables for layout instead of div-based layouts - Tables provide consistent structure across email clients with poor CSS support.
- Set explicit cell padding, spacing, and dimensions - This prevents inconsistent spacing and layout across different email clients.
- Declare width on table elements and on cells - Double-declaring widths improves rendering consistency, especially in Outlook.
- Use `align` and `valign` attributes instead of CSS equivalents - These HTML attributes have better support than CSS positioning in email clients.

```html
<table border="0" cellpadding="0" cellspacing="0" width="100%">
    <tr>
        <td align="center" valign="top">
            <!-- Content here -->
        </td>
    </tr>
</table>
```

### Avoid problematic elements

- No JavaScript - Most email clients strip JavaScript for security reasons, so any functionality depending on it will fail.
- No forms (though some clients support them) - Form support is extremely inconsistent; many clients disable or strip form elements.
- No iframes - These are stripped by most email clients for security reasons.
- No background images (use with fallbacks if necessary) - Many email clients disable background images by default.
- No embedded audio/video (link to hosted content instead) - Direct embedding is poorly supported; linking to external content is more reliable.

## CSS

### CSS Support Limitations

- Use inline CSS for everything critical - Many email clients strip `<style>` tags or ignore them entirely.
- Avoid CSS shorthand properties (use `margin-top` instead of `margin`) - Some email clients only recognize individual properties, not shorthand.
- Avoid CSS positioning properties (`position`, `float`, `clear`) - These are poorly supported and can cause layout issues.
- Avoid advanced selectors (stick to element, class, and ID selectors) - Complex selectors often fail in email clients with limited CSS support.

### Always use inline styles

```html
<p style="font-family: Arial, sans-serif; font-size: 16px; line-height: 24px; color: #333333;">
    Your content here
</p>
```

### Limited use of style tags

- Use for email client-specific hacks only
- Be careful with media queries (support varies)
- Always include the type attribute: `<style type="text/css">`

### Supported vs unsupported CSS

**Well-supported:**

- `font-family`, `font-size`, `color` - Basic text styling has consistent support across email clients.
- `text-align`, `line-height` - Text alignment and spacing properties work reliably in most clients.
- `width`, `height`, `padding`, `margin` (with caution) - Basic box model properties work when used conservatively. Margin doesn't work properly in Outlook.
- `border`, `background-color` - These visual properties have good support in most email clients.

**Poorly supported or inconsistent:**

- Flexbox, Grid - Modern layout systems are not supported in many email clients, particularly Outlook.
- Positioning properties (`position`, `display`) - These can cause emails to render differently or break layouts in some clients.
- CSS transitions, animations - These are stripped or ignored by most email clients.
- Many pseudo-classes and pseudo-elements - Support is inconsistent; Outlook and some webmail clients ignore them completely.

## Layout Techniques

### Single column design

- Most reliable across all clients - Simple layouts have fewer points of failure.
- Recommended width: 600px - This fits within most preview panes and mobile screens.
- Works well on mobile without responsiveness - Single column naturally adapts to narrow screens with minimal issues.

### Multi-column layouts with tables

```html
<table border="0" cellpadding="0" cellspacing="0" width="600">
    <tr>
        <td width="300" valign="top">
            <!-- Column 1 content -->
        </td>
        <td width="300" valign="top">
            <!-- Column 2 content -->
        </td>
    </tr>
</table>
```

### Column stacking techniques

- Use media queries where supported - Media queries allow columns to stack on mobile devices in clients that support them.
- Use MSO conditional comments for Outlook - These target Outlook specifically, which needs special handling for responsive layouts.
- Consider hybrid/spongy approach for clients without media query support - This technique creates layouts that adapt reasonably even without media query support.

```html
<!--[if mso]>
<table border="0" cellpadding="0" cellspacing="0" width="600">
<tr><td width="300" valign="top"><![endif]-->
<div style="display: inline-block; width: 300px; vertical-align: top;">
    <!-- Column 1 content -->
</div>
<!--[if mso]></td><td width="300" valign="top"><![endif]-->
<div style="display: inline-block; width: 300px; vertical-align: top;">
    <!-- Column 2 content -->
</div>
<!--[if mso]></td></tr></table><![endif]-->
```

## Responsiveness

### Mobile-first approach

- Design for small screens first - Mobile email opens often exceed desktop, making mobile optimization critical.
- Use proportional widths where possible - This allows content to adapt to different screen sizes automatically.
- Use appropriate font sizes (minimum 14px) - Smaller fonts are difficult to read on mobile devices and may require zooming.
- Ensure touch targets are at least 44x44px - This follows accessibility guidelines and makes interaction easier on touchscreens.

### Media queries

Limited but important support:

```html
<style type="text/css">
    @media screen and (max-width: 480px) {
        .mobile-full-width {
            width: 100% !important;
            height: auto !important;
        }
        
        .mobile-font {
            font-size: 16px !important;
        }
    }
</style>
```

### Fluid hybrid approach

Uses max-width and MSO conditionals to work across clients with or without media query support:

```html
<!--[if mso]>
<table width="600" cellpadding="0" cellspacing="0" border="0"><tr><td>
<![endif]-->
<div style="width:100%; max-width:600px; margin:0 auto;">
    <!-- Content -->
</div>
<!--[if mso]>
</td></tr></table>
<![endif]-->
```

## Typography

### Email-safe fonts

Always use a font stack with widely supported fonts:

```css
font-family: Arial, Helvetica, sans-serif;
font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
font-family: 'Times New Roman', Times, serif;
font-family: Verdana, Geneva, sans-serif;
font-family: Georgia, Times, 'Times New Roman', serif;
font-family: 'Courier New', Courier, monospace;
```

### Web fonts

- Limited support - use only with proper fallbacks - Many email clients don't support web fonts, so fallbacks are essential.
- Most reliable support: Google Fonts in Apple Mail, iOS Mail, and Android - These clients render web fonts consistently.
- Outlook, Gmail, and Yahoo! often don't support web fonts - These popular clients will display your fallback fonts instead.

```html
<style>
    @import url('https://fonts.googleapis.com/css2?family=Open+Sans&display=swap');
</style>

<p style="font-family: 'Open Sans', Arial, sans-serif;">Your text</p>
```

### Text formatting best practices

- Set a base font size of 14-16px - Smaller fonts can be difficult to read, especially on mobile devices.
- Line height should be 1.4-1.6 times the font size - Proper spacing improves readability and prevents text from appearing cramped.
- Break up large blocks of text - Chunking content improves scanability and engagement with your email.
- Left-align text for better readability - This creates a consistent reading experience and prevents ragged edges on both sides.
- Ensure sufficient contrast between text and background - This improves readability and accessibility for all users.

## Images

### Image guidelines

- Always include the `alt` attribute - This provides text alternatives when images are blocked and improves accessibility.
- Set explicit width and height on all images - This prevents layout shifts when images load and maintains structure when images are blocked.
- Keep image file sizes small (optimize for web) - Large images increase loading time and may exceed file size limits.
- Consider what happens when images are blocked - Many email clients block images by default, so your email should still make sense without them.

```html
<img src="https://example.com/image.jpg" alt="Description of image" width="600" height="400" style="display: block; width: 100%; max-width: 600px; height: auto;" border="0">
```

### Background images

Limited support; always provide a fallback bgcolor:

```html
<table background="https://example.com/bg.jpg" bgcolor="#f7f7f7" width="600" cellpadding="0" cellspacing="0" border="0">
    <tr>
        <td>
            Content
        </td>
    </tr>
</table>
```

## Email Client Specifics

### Outlook (Windows)

- Uses Word rendering engine (MSO) - This explains Outlook's unique rendering quirks and limited CSS support.
- Requires VML for background images and rounded corners - Standard CSS methods for these effects don't work in Outlook.
- Use MSO conditional comments for Outlook-specific code - These allow you to target Outlook versions specifically.

```html
<!--[if mso]>
    Outlook-specific content here
<![endif]-->
```

### Gmail

- Strips style tags and head section - Inline styles must be used for all critical styling.
- Removes class and ID attributes in some cases - Don't rely on these for critical styling or functionality.
- Use inline styles for everything - This is the only reliable way to style content in Gmail.
- Limits email size (clipping if too large) - Emails over 102KB get clipped with a "View entire message" link.

### Apple Mail/iOS

- Best rendering capabilities - These clients support modern CSS and HTML standards.
- Supports most modern CSS - Features like flexbox and grid can be used if you're targeting primarily Apple users.
- Good support for media queries - Responsive design works reliably on these platforms.

## Accessibility

### Semantic structure

- Use semantic HTML where possible (`p`, `h1`, `h2`, etc.) - This improves screen reader interpretation and overall accessibility.
- Add `role="presentation"` to layout tables - This tells screen readers the table is for layout only, not data presentation.
- Include proper heading structure - This creates a logical document outline that helps screen reader users navigate.
- Use `aria-hidden="true"` for decorative elements - This prevents screen readers from announcing purely visual elements.

```html
<table role="presentation" border="0" cellpadding="0" cellspacing="0">
```

### Text alternatives

- Always use descriptive alt text for images - This ensures people using screen readers understand the content and purpose of images.
- Set empty alt text for decorative images - This prevents screen readers from announcing images that don't add information.
- Consider what the email looks like with images off - Many users and email clients block images, so content should work without them.

### Color and contrast

- Maintain a minimum contrast ratio of 4.5:1 for normal text - This meets WCAG accessibility standards and improves readability for all users.
- Use actual text instead of text in images - Text in images isn't accessible to screen readers and doesn't scale well on different devices.
- Don't rely on color alone to convey information - This accommodates users with color vision deficiencies.

### Navigation and links

- Make links easily identifiable - Distinct styling helps users recognize clickable elements.
- Use descriptive link text (avoid "click here") - Descriptive links provide context and are more accessible to screen reader users.
- Ensure adequate spacing between touch targets - This prevents accidental taps on mobile devices and helps users with motor impairments.

## Testing tools and services

- Use professional email testing services like [Litmus](https://www.litmus.com/) and [Email on Acid](https://www.emailonacid.com/) - These platforms provide comprehensive testing across multiple email clients and devices.
- Test in real email clients when possible - While testing services are valuable, real-world testing can catch issues that automated testing might miss.
- Check rendering in both desktop and mobile clients - Mobile email opens often exceed desktop, making mobile testing essential.
- Test with images disabled - Many email clients block images by default, so ensure your email is readable and functional without them.
- Check spam filter scores - Use tools like [Mail-Tester](https://www.mail-tester.com/) to identify potential spam triggers in your email content.
- Validate HTML - Use email-specific validators to catch potential rendering issues before sending.
