<?php
/*
Plugin Name: video_meta
Plugin URI: https://github.com/repzy/Wp_video_meta
Description: Adds duration and image preview meta tags of video in your post.
Version: 1.0
Author: Dmytro Kuzmenko
Author URI: https://github.com/repzy
*/
function save_video_meta( $post_id, $post ) {

  preg_match('~
        # Match non-linked youtube URL in the wild. (Rev:20130823)    
        https?://         # Required scheme. Either http or https.
        (?:[0-9A-Z-]+\.)? # Optional subdomain.
        (?:               # Group host alternatives.
          youtu\.be/      # Either youtu.be,
        | youtube         # or youtube.com or
          (?:-nocookie)?  # youtube-nocookie.com
          \.com           # followed by
          \S*             # Allow anything up to VIDEO_ID,
          [^\w\s-]        # but char before ID is non-ID char.
        )                 # End host alternatives.
        ([\w-]{11})       # $1: VIDEO_ID is exactly 11 chars.
        (?=[^\w-]|$)      # Assert next char is non-ID or EOS.
        (?!               # Assert URL is not pre-linked.
          [?=&+%\w.-]*    # Allow URL (query) remainder.
          (?:             # Group pre-linked alternatives.
            [\'"][^<>]*>  # Either inside a start tag,
          | </a>          # or inside <a> element text contents.
          )               # End recognized pre-linked alts.
        )                 # End negative lookahead assertion.
        [?=&+%\w.-]*      # Consume any URL (query) remainder.
        ~ix', $post->post_content, $videoUrl);
  
  if (null != $videoUrl) {
    $apiKey = 'AIzaSyBbMY9p5iG_hcaB-_eWkBUfkEw-F2s69YA';
    $allInfo = json_decode(file_get_contents('https://www.googleapis.com/youtube/v3/videos?id='.$videoUrl[1].'&key='.$apiKey.'&part=contentDetails'));

    $ISO8601Duration = $allInfo->items[0]->contentDetails->duration;

    preg_match('/^PT(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?$/', $ISO8601Duration, $res);

    $secondsDuration = $res[1] * 3600 + $res[2] * 60 + $res[3]; 

    update_post_meta( $post_id, 'og:image', 'img.youtube.com/vi/'.$videoUrl[1].'/mqdefault.jpg' );
    update_post_meta( $post_id, 'video:duration', $secondsDuration );
  }
}

add_action( 'save_post', 'save_video_meta', 10, 2 );

function insert_meta_in_head() {

  global $post;

  $image = get_post_meta( $post->ID, 'og:image', true );
  $duration = get_post_meta( $post->ID, 'video:duration', true );

  if (null != $duration && null != $image) {
    echo '<meta property="og:image" content="'.$image.'"/>';
    echo '<meta property="video:duration" content="'.$duration.'"/>';
  }       
}

add_action( 'wp_head', 'insert_meta_in_head', 5 );

/* If you have this strings in your <head> tag, please comment this function */
function doctype_opengraph($output) {
    return $output . '
    prefix="
      og: http://ogp.me/ns# 
      fb: http://ogp.me/ns/fb# 
      video: http://ogp.me/ns/video#
    "';
}

add_filter('language_attributes', 'doctype_opengraph');

?>