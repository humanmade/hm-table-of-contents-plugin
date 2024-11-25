<?php

namespace HM\TOC;

use WP_HTML_Tag_Processor;

$post_id = isset( $attributes['postId'] ) ? $attributes['postId'] : ( $block->context['postId'] ?? null );
$post = get_post( $post_id );

if ( ! $post ) {
	return;
}

$max_level = $attributes['maxLevel'] ?? 3;

// Work out the current level by finding the heighest level heading within the content.
// Typically this is H2. But technically it can be anything.
foreach ( array_slice( [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ], 0, $max_level ) as $i => $heading_tag ) {
	$tags = new WP_HTML_Tag_Processor( $post->post_content );
	if ( $tags->next_tag( $heading_tag ) ) {
		$current_level = $i + 1;
		break;
	}
}

if ( ! $current_level ) {
	return;
}

$hierarchy = get_header_hierarchy( $post );

if ( empty( $hierarchy ) ) {
	return;
}

$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => 'hm-table-of-contents' ) );

printf(
	'<div %s>',
	$wrapper_attributes, // @codingStandardsIgnoreLine WordPress.Security.EscapeOutput.OutputNotEscaped
);

printf( '<h2>%s</h2>', esc_html__( 'Table of contents', 'hm-toc' ) );
the_heading_list( $hierarchy, $max_level, $current_level );
echo '</div>';
