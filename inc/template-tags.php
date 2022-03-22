<?php
/**
 * Template tags to render table of contents.
 *
 * @package hm-toc
 * @since   0.1
 */

namespace HM\TOC\Template_Tags;

use HM\TOC;
use WP_Post;

/**
 * Render TOC menu from post content headings.
 *
 * @param WP_Post $post The post for which to render the TOC.
 * @param integer $level The maximum level to dive to.
 * @return string
 */
function render_toc( WP_Post $post, int $level = 1 ) : string {
	$toc = TOC\get_header_hierarchy( $post );

	return render_items( $toc, $level, 1 );
}

/**
 * Render single level of items into unordered list.
 *
 * This function is called recursively for every item's children to render
 * the TOC hierarchy.
 *
 * @param array $items Array of items
 * @param integer $max_level The maximum level to dive to.
 * @param integer $level Current level being rendered.
 * @return string
 */
function render_items( array $items, int $max_level, int $level ) : string {
	ob_start();

	$start_el_attrs = [
		'class' => ( $level > 1 ) ? 'hm-toc-submenu' : 'hm-toc',
	];
	$start_el_attrs = apply_filters( 'hm_toc.render.start_el_attrs', $start_el_attrs, $level );

	printf( '<ul %s>', html_attrubutes( $start_el_attrs ) ); // phpcs:ignore HM.Security.EscapeOutput.OutputNotEscaped

	foreach ( $items as $item ) {
		$item_attrs = [
			'class' => 'hm-toc-item',
		];
		$link_attrs = [
			'href' => $item->href,
			'class' => 'hm-toc-link',
		];

		printf(
			'<li %1$s><a %2$s>%3$s</a>%4$s</li>',
			html_attrubutes( apply_filters( 'hm_toc.render.item_attrs', $item_attrs ) ),
			html_attrubutes( apply_filters( 'hm_toc.render.link_attrs', $link_attrs ) ),
			esc_html( $item->title ),
			! empty( $item->items ) && $level < $max_level ? render_items( $item->items, $max_level, $level++ ) : '' // phpcs:ignore HM.Security.EscapeOutput.OutputNotEscaped
		);
	}

	print( '</ul>' ); // phpcs:ignore HM.Security.EscapeOutput.OutputNotEscaped

	return ob_get_clean();
}

/**
 * Helper function to convert associative array to HTML attributes.
 *
 * @param array $attributes Associative array of strings to be turned into HTML attributes string.
 * @return string
 */
function html_attrubutes( array $attributes ) : string {
	$attrs = array_map(
		function ( $value, $key ) {
			return sprintf( '%s="%s"', $key, esc_attr( $value ) );
		},
		$attributes,
		array_keys( $attributes )
	);

	return implode( ' ', $attrs );
}
