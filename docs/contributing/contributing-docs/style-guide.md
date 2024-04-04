---
post_title: Technical Documentation Style Guide
menu_title: Style Guide
---

This style guide is intended to provide guidelines for creating effective and user-friendly tutorials and how-to guides for WooCommerce technical documentation that will live in repo and be editable and iterative by open source contributors and WooCommerce teams.

## Writing style

### Language style

- It's important to use clear and concise language that is easy to understand. Use active voice and avoid using jargon or technical terms that may be unfamiliar to the user. The tone should be friendly and approachable, and should encourage the user to take action.

- Articles are written in the 3rd-person voice.
  Example: "Add an embed block to your page."

- Use American English for spelling and punctuation styles, or consider using a different word that doesn't have a different spelling in other English variants.

- Use sentence case (not title case) for docs titles and subheadings.
  Example: "Introduction to the launch experience" rather than "Introduction to the Launch Experience."

- When referring to files or directories, the text formatting eliminates the need to include articles such as "the" and clarifying nouns such as "file" or "directory".
  Example: "files stored in ~~the~~ `/wp-content/uploads/` ~~directory~~" or "edit ~~the~~ `/config/config.yml` ~~file~~ with"

### Writing tips

- Our target audience has a range of roles and abilities. When creating a tutorial or how-to guide, it's important to consider the intended audience. Are they beginners or advanced users? What is their technical background? Understanding the audience can help guide the level of detail and the choice of language used in the guide.

- Use language understable even by readers with little technical knowledge and readers whose first language might not be English.

- Consider that this might be the first WooCommerce documentation page the reader has seen. They may have arrived here via a Google search or another website. Give the reader enough context about the topic and link words and phrases to other relevant Docs articles as often as possible.

- Consider notes and sections that provide insights, tips, or cautionary information to expand on topics with context that would be relevant to the reader.

- When providing specific direction, best practices, or requirements, we recommend including a description of the potential consequences or impacts of not following the provided guidance. This can help seed additional search keywords into the document and provide better context when support links to the documentation.

- Always write a conceptual, high-level introduction to the topic first, above any H2 subheading.

### Tutorials

Tutorials are comprehensive and designed to teach a new skill or concept.

> You are the teacher, and you are responsible for what the student will do. Under your instruction, the student will execute a series of actions to achieve some end.
> 
> [Divio Framework on Tutorial Writing](https://documentation.divio.com/tutorials/)

### How-to guides

How-to guides are focused and specific, providing instructions on how to accomplish a particular task or solve a particular problem.

> How-to guides are wholly distinct from tutorials and must not be confused with them:
> 
> - A tutorial is what you decide a beginner needs to know.
> - A how-to guide is an answer to a question that only a user with some experience could even formulate.
> 
> [Divio Framework on How-to-Guide Writing](https://documentation.divio.com/how-to-guides/)

## Custom Linting Rules

At WooCommerce, we're dedicated to maintaining a consistent and high-quality standard for our technical documentation. Our documents primarily adhere to the linting rules provided by `markdownlint`. To assist our contributors, we've detailed our custom configurations and exceptions below.

Note: While we've outlined specific rules above, all other default linting rules from `markdownlint` apply unless otherwise stated. We've only highlighted custom configurations or exceptions here. For a complete list of `markdownlint` rules, you can refer to [this link](https://github.com/DavidAnson/markdownlint/blob/3561fc3f38b05b3c55f44e371c2cd9bda194598a/doc/Rules.md).

1. **Headings Style**: 
    - Use the ATX-style (`#`) for headers.

    ```markdown
      # This is an H1
      ## This is an H2
    ```

   [Reference: MD003](https://github.com/DavidAnson/markdownlint/blob/3561fc3f38b05b3c55f44e371c2cd9bda194598a/doc/Rules.md#md003---heading-style)

2. **List Indentation**: 
    - Indent list items with 4 spaces.

    ```markdown
      - Item 1
          - Subitem 1.1
    ```

   [Reference: MD007]([https://github.com/DavidAnson/markdownlint/blob/main/doc/Rules.md](https://github.com/DavidAnson/markdownlint/blob/3561fc3f38b05b3c55f44e371c2cd9bda194598a/doc/Rules.md)#md007---unordered-list-indentation)

3. **Line Length**: 
    - No specific restriction on the line length, but keep paragraphs and sentences readable.
    
    [Reference: MD013](https://github.com/DavidAnson/markdownlint/blob/main/doc/Rules.md#md013---line-length)

4. **Multiple Headings with the Same Content**: 
    - Multiple headings with the same content are permissible as long as they are not siblings.
    
    [Reference: MD024](https://github.com/DavidAnson/markdownlint/blob/3561fc3f38b05b3c55f44e371c2cd9bda194598a/doc/Rules.md#md024---no-multiple-headings-with-the-same-content)

5. **Inline HTML**: 
    - Only the `video` element is allowed when using inline HTML.

    ```markdown
    <video src="path_to_video.mp4" controls></video>
    ```

   [Reference: MD033](https://github.com/DavidAnson/markdownlint/blob/3561fc3f38b05b3c55f44e371c2cd9bda194598a/doc/Rules.md#md033---inline-html)

6. **Tabs and Whitespace**: 
    - We're flexible with the use of hard tabs and trailing whitespace. However, for consistency, we recommend using spaces over tabs and avoiding trailing whitespaces.
    
    [Reference: no-hard-tabs & whitespace](https://github.com/DavidAnson/markdownlint/blob/3561fc3f38b05b3c55f44e371c2cd9bda194598a/doc/Rules.md)

## Formatting

### Visual style

- Use the H2 style for main headings to be programmatically listed in the articles table of contents.
- File names and directory paths should be stylized as code per the [HTML spec](https://html.spec.whatwg.org/multipage/text-level-semantics.html#the-code-element).
  Example: `/wp-content/uploads/`
- References to a single directory should have a trailing slash (eg. "/" appended) to the name.
  Example: "uploads/"
- References to repositories should appear without forward slashes and not be formatted in any way. The first appearance of a repository in article content should link to the URL of the repository source whenever possible.
  Example: "[woocommerce-blocks](https://github.com/woocommerce/woocommerce-blocks)" followed by "woocommerce-blocks"
- Inline references to functions and command line operations should be formatted as inline code.
  Example: "Use `dig` to retrieve DNS information."
- Functions should be styled with "Inline code" formatting and retain upper and lower case formatting as established from their source.
  Example: `WP_Query` (not WP_query)

### Visual aids

Visual aids such as screenshots, diagrams, code snippets and videos can be very helpful in a how-to guide. They provide a visual reference that can help the user understand the instructions more easily. When including visual aids, be sure to label them clearly and provide a caption or description that explains what is being shown.

### Acronyms

Phrases that are more familiarly known in their acronym form can be used. The first time an acronym appears on any page, the full phrase must be included, followed by its acronym in parentheticals.

Example: We've enhanced the querying functionality in WooCommerce with the introduction of High Performance Order Storage (HPOS).

After that, the acronym can be used for the remainder of the page.

When deciding if a term is common, consider the impact on translation and future internationalization (i18n) efforts.

## Patterning

### Article content

When creating a how-to guide, it's important to use a consistent and easy-to-follow format. Here is a suggested template for a software how-to guide:

**Introduction**: Provide an overview of the task or feature that the guide covers.

**Prerequisites**: List any prerequisites that are required to complete the task or use the feature.

**Step-by-step instructions**: Provide detailed, step-by-step instructions for completing the task or using the feature. Use numbered steps and include screenshots or other visual aids where appropriate.

**Troubleshooting**: Include a troubleshooting section that addresses common issues or errors that users may encounter.

**Conclusion**: Summarize the key points covered in the guide and provide any additional resources or references that may be helpful.

## Terminology

### Reference to components and features

- "**WordPress Admin dashboard**" should be presented in its complete form the first time it appears in an article, followed by its abbreviated form in parentheses ("WP Admin"). Thereafter the abbreviated form can be used for any reference to the WordPress Admin dashboard within the same article.
- When referring to the URL of the WordPress Admin dashboard, the shortened form `wp-admin` can be used.

## Testing

Before publishing a tutorial or guide, it's important to test it thoroughly to ensure that the instructions are accurate and easy to follow.

## Structure

### Atomizing the docs

Articles that cover too many topics in one place can make it difficult for users to find the information they are looking for. "Atomizing" the Docs refers to breaking down extensive articles into a group of smaller related articles. This group of articles often has a main "landing page" with a high-level overview of the group of articles, and the descriptive text provides links to the related articles that a user will find relevant. These groups of articles can be considered an information "molecule" formed by the smaller, atomized articles.

Breaking out smaller chunks of content into their own articles makes it easier to link to specific topics rather than relying on links to more extensive articles with anchor tags. This more specific linking approach is helpful to our Support team but is also useful for cross-linking articles throughout the Docs site.
