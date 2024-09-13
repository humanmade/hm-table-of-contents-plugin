# HM Table of Contents

WordPress plugin with the following features:

* Generate table of contents that lists all headings (Ordered hierachically) in post content.
* Automatically add anchors links after all headings in post content.
* Block to add these to pages and template.
* Adds 'active' class to the current heading in the table of contents.

## FAQ

### I don't want to automatically add anchor links to headings.

You can remove the filter that adds these with the following snippet. Doing it like this means that the plugin will still ensures headings all have IDs.

```php
// Don't append an anchor link to each heading.
	add_filter( 'hm_toc.contents.anchor_html', '__return_empty_string' );
```
