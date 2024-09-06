# HM Table of Contents

WordPress plugin with the following features:

* Generate table of contents that lists all headings (Ordered hierachically) in post content. 
* Automatically add anchors links after all headings in post content.
* Block to add these to pages and template.

## FAQ

### I don't want to automatically add anchor links to headings. 

You can remove the filter that adds these with the following snippet: 

```php
remove_filter( 'the_content', 'HM\\TOC\\add_ids_to_content', -10 );
```
