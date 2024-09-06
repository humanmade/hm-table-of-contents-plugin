<?php

namespace HM\TOC;

$post = isset( $attributes['post_id'] ) ? get_post( $attributes['post_id'] ) : get_post( get_the_ID() );

if ( ! $post ) {
	return;
}

$hierarchy = get_header_hierarchy( $post );
$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => 'hm-table-of-contents' ) );

printf(
	'<div %s>',
	$wrapper_attributes, // @codingStandardsIgnoreLine WordPress.Security.EscapeOutput.OutputNotEscaped
);

printf( '<h2>%s</h2>', esc_html__( 'Table of contents', 'hm-table-of-contents' ) );
the_heading_list( $hierarchy );

echo '</div>';
