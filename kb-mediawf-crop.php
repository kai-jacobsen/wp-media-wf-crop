<?php
/**
 * Plugin Name: MediaFrame: Image Crop (proof of concept)
 * Description: a custom 2-step media workflow with (forced) image cropping. Creates an demo meta box for posts.
 * Plugin URI:
 * Version:     0.0.1
 * Author:      Kai Jacobsen @kjcbsn
 * Author URI:  https://github.com/kai-jacobsen
 * License:     GPLv2+
 *
 * Php Version 5.3
 *
 */

function kb_add_meta_box()
{
    add_meta_box(
        'kb-crop-image',
        'Demo cropping',
        'kb_meta_box_content',
        'post',
        'normal',
        'low'
    );


}

function kb_meta_box_content()
{
    echo "<div class='kb-cropped-image'></div>";
    echo "<button class='js-kb-crop-image secondary' type='button'>Click Me!</button>";
}


add_action( 'add_meta_boxes_post', 'kb_add_meta_box' );

/**
 * Enqueue script for media modal and democode
 *
 * @return null
 */
function enqueue_scripts()
{
    /*
     * uncomment and use different action hook if you want to use this on the frontend
     */
//    if (!is_admin()) {
//        wp_enqueue_media();
//        wp_enqueue_script( 'imgareaselect', "/wp-includes/js/imgareaselect/jquery.imgareaselect.js" );
//        wp_enqueue_script( 'image-edit', "/wp-admin/js/image-edit.js" );
//    }

    wp_enqueue_script(
        'kb-crop-frame',
        plugins_url( 'js/dev/mediawf-crop.js', __FILE__ ),
        array(),
        '0.1',
        TRUE
    );
}

add_action( 'admin_enqueue_scripts', 'enqueue_scripts' );


/**
 * Ajax callback
 * creates a new attachment from the cropped image
 * basically taken from the custom-header class
 * send prepared attachment back to the client
 */
function kb_custom_header_crop()
{
    check_ajax_referer( 'image_editor-' . $_POST['id'], 'nonce' );
    if (!current_user_can( 'edit_theme_options' )) {
        wp_send_json_error();
    }

    $crop_details = $_POST['cropDetails'];
    $crop_options = $_POST['cropOptions'];
    $attachment_id = absint( $_POST['id'] );

    $cropped = wp_crop_image(
        $attachment_id,
        (int) $crop_details['x1'],
        (int) $crop_details['y1'],
        (int) $crop_details['width'],
        (int) $crop_details['height'],
        $crop_options['maxWidth'],
        $crop_options['maxHeight']
    );

    if (!$cropped || is_wp_error( $cropped )) {
        wp_send_json_error( array( 'message' => __( 'Image could not be processed. Please go back and try again.' ) ) );
    }

    /** This filter is documented in wp-admin/custom-header.php */
    $cropped = apply_filters( 'wp_create_file_in_uploads', $cropped, $attachment_id ); // For replication

    $object = create_attachment_object( $cropped, $attachment_id );

    unset( $object['ID'] );

    $new_attachment_id = insert_attachment( $object, $cropped );

    $pre = wp_prepare_attachment_for_js( $new_attachment_id );

    wp_send_json_success( $pre );
}

add_action( 'wp_ajax_kb-custom-header-crop', 'kb_custom_header_crop' );


/**
 *
 * Insert an attachment and its metadata.
 *
 * @param array $object Attachment object.
 * @param string $cropped Cropped image URL.
 *
 * @return int Attachment ID.
 */
function insert_attachment( $object, $cropped )
{
    $attachment_id = wp_insert_attachment( $object, $cropped );
    $metadata = wp_generate_attachment_metadata( $attachment_id, $cropped );
    /**
     * Filter the header image attachment metadata.
     * @since 3.9.0
     * @see wp_generate_attachment_metadata()
     * @param array $metadata Attachment metadata.
     */
    $metadata = apply_filters( 'wp_header_image_attachment_metadata', $metadata );
    wp_update_attachment_metadata( $attachment_id, $metadata );
    return $attachment_id;
}

/**
 * Create an attachment 'object'.
 *
 * @param string $cropped Cropped image URL.
 * @param int $parent_attachment_id Attachment ID of parent image.
 *
 * @return array Attachment object.
 */
function create_attachment_object( $cropped, $parent_attachment_id )
{
    $parent = get_post( $parent_attachment_id );
    $parent_url = $parent->guid;
    $url = str_replace( basename( $parent_url ), basename( $cropped ), $parent_url );

    $size = @getimagesize( $cropped );
    $image_type = ( $size ) ? $size['mime'] : 'image/jpeg';

    $object = array(
        'ID' => $parent_attachment_id,
        'post_title' => basename( $cropped ),
        'post_content' => $url,
        'post_mime_type' => $image_type,
        'guid' => $url
    );

    return $object;
}