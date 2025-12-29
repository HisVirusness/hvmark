# hVmark

**hVmark** is a tiny, opinionated markup language made by and for, well, me. While it's used across HisVirusness.com, it's primarily used in posts in lieu of hard-coded HTML.

This is the **vanilla reference model**; a working example showing how the language behaves in practice. My own site-specific settings were nixed out of the code to keep it CSS agnostic. Despite that, output HTML still contains [deprecated code](https://hisvirusness.com/man-page#deprecation-policy) and assumes a trusted author pipeline.

This module exists to _demonstrate, not aberrate_. If you want to make syntax changes or add features,  
**FFTF**: **F**eel **F**ree **T**o **F**ork.

## Syntax

### Inline Markers

#### Text Formatting

*   **Bold**: `*text*`
*   _Emphasis_ (Italics): `%text%`
*   <s>Strike</s>: `-text-`
*   <ins>Underline</ins>: `_text_`

#### Misc.

*   **Soft line break**: `[]` → `<br>`
*   **Horizontal rule**: --- → `<hr>`  
    **Three or more** -, *, or _ characters on their own line will output a horizontal rule.  
    Output styling depends on configuration.
*   **Subheading**: `*// Subheading Text*`
*   **Subheading output**: `<h# id="subheading-text">// Subheading Text</h#>`  
    Actual heading level is based on config.
*   **Optional TOC**: If two or more subheadings are found, a table of contents will be generated.  
    The TOC is automatically placed under the main heading (_&lt;h1>_) of the page, at the top of the page if a main heading isn't present, or at the location of `<!--HV_TOC-->`.

### Links, Images, YouTube

All use the same “anchor + fangs” pattern: `@@anchor^fangs^`

*   **Links**: `@@https://example.com/^Link text^`  
    (leave fangs empty to use the URL as text, e.g. `@@https://example.com/^^`)
*   **Images**: `@@img:/path/file.jpg^Caption^` (85% width by default; caption optional)  
    Full: `@@img:/path/file.jpg\[50px|25% left|right|center|blank\]^Caption^`
*   **YouTube**: `@@ytb:VIDEOID^Caption^` (caption optional)

### Lists

*   **Dotted**: one item per line starting with `[+]`
*   **Plain**: one item per line starting with `[-]`
*   Soft breaks are allowed inside items via `[]`

### Notes & Quirks

*   **_Order matters_ when nesting**: `*` and `%` are forgiving, but consistent nesting is recommended.
*   Inline raw HTML on a line disables hVmark parsing on that line.
*   A backslash (\\) will escape an inline marker and output the literal symbol.  
    (e.g., `\%` will output `%`.)
*   Despite the above, the symbols used for inline markers do not appear in the alt text of images, escaped or not.
*   Emojis are supported in regular text, but not in subheadings.
*   Subheadings with similar names will have duplicate IDs; the TOC generator does not add incrementing suffixes.
*   Blockquotes are not currently part of v1.

## Further Reading

*  [hVmark in Production](https://hisvirusness.com/man-page#hvmark)
*  [Writing About hVmark… in hVmark](https://hisvirusness.com/can-and-should-are-not-the-same)
