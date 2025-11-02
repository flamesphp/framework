<?php

namespace Flames\Kernel\Tools;

/**
 * @internal
 */
class WinIco
{
    var $_images = array();
    var $_has_requirements = false;

    function __construct( $file = false, $sizes = array() ) {
        $required_functions = array(
            'getimagesize',
            'imagecreatefromstring',
            'imagecreatetruecolor',
            'imagecolortransparent',
            'imagecolorallocatealpha',
            'imagealphablending',
            'imagesavealpha',
            'imagesx',
            'imagesy',
            'imagecopyresampled',
        );

        foreach ( $required_functions as $function ) {
            if ( ! function_exists( $function ) ) {
                trigger_error( "The class was unable to find the $function function, which is part of the GD library. Ensure that the system has the GD library installed and that PHP has access to it through a PHP interface, such as PHP's GD module. Since this function was not found, the library will be unable to create ICO files." );
                return;
            }
        }

        $this->_has_requirements = true;


        if ( false != $file )
            $this->add_image( $file, $sizes );
    }

    function add_image( $file, $sizes = array() ) {
        if ( ! $this->_has_requirements )
            return false;

        if ( false === ( $im = $this->_load_image_file( $file ) ) )
            return false;


        if ( empty( $sizes ) )
            $sizes = array( imagesx( $im ), imagesy( $im ) );

        if ( ! is_array( $sizes[0] ) )
            $sizes = array( $sizes );

        foreach ( (array) $sizes as $size ) {
            list( $width, $height ) = $size;

            $new_im = imagecreatetruecolor( $width, $height );

            imagecolortransparent( $new_im, imagecolorallocatealpha( $new_im, 0, 0, 0, 127 ) );
            imagealphablending( $new_im, false );
            imagesavealpha( $new_im, true );

            $source_width = imagesx( $im );
            $source_height = imagesy( $im );

            if ( false === imagecopyresampled( $new_im, $im, 0, 0, 0, 0, $width, $height, $source_width, $source_height ) )
                continue;

            $this->_add_image_data( $new_im );
        }

        return true;
    }

    function save( $file ) {
        if ( ! $this->_has_requirements )
            return false;

        if ( false === ( $data = $this->_get_ico_data() ) )
            return false;

        if ( false === ( $fh = fopen( $file, 'w' ) ) )
            return false;

        if ( false === ( fwrite( $fh, $data ) ) ) {
            fclose( $fh );
            return false;
        }

        fclose( $fh );

        return true;
    }

    function _get_ico_data() {
        if ( ! is_array( $this->_images ) || empty( $this->_images ) )
            return false;


        $data = pack( 'vvv', 0, 1, count( $this->_images ) );
        $pixel_data = '';

        $icon_dir_entry_size = 16;

        $offset = 6 + ( $icon_dir_entry_size * count( $this->_images ) );

        foreach ( $this->_images as $image ) {
            $data .= pack( 'CCCCvvVV', $image['width'], $image['height'], $image['color_palette_colors'], 0, 1, $image['bits_per_pixel'], $image['size'], $offset );
            $pixel_data .= $image['data'];

            $offset += $image['size'];
        }

        $data .= $pixel_data;
        unset( $pixel_data );


        return $data;
    }

    function _add_image_data( $im ) {
        $width = imagesx( $im );
        $height = imagesy( $im );


        $pixel_data = array();

        $opacity_data = array();
        $current_opacity_val = 0;

        for ( $y = $height - 1; $y >= 0; $y-- ) {
            for ( $x = 0; $x < $width; $x++ ) {
                $color = imagecolorat( $im, $x, $y );

                $alpha = ( $color & 0x7F000000 ) >> 24;
                $alpha = ( 1 - ( $alpha / 127 ) ) * 255;

                $color &= 0xFFFFFF;
                $color |= 0xFF000000 & ( $alpha << 24 );

                $pixel_data[] = $color;


                $opacity = ( $alpha <= 127 ) ? 1 : 0;

                $current_opacity_val = ( $current_opacity_val << 1 ) | $opacity;

                if ( ( ( $x + 1 ) % 32 ) == 0 ) {
                    $opacity_data[] = $current_opacity_val;
                    $current_opacity_val = 0;
                }
            }

            if ( ( $x % 32 ) > 0 ) {
                while ( ( $x++ % 32 ) > 0 )
                    $current_opacity_val = $current_opacity_val << 1;

                $opacity_data[] = $current_opacity_val;
                $current_opacity_val = 0;
            }
        }

        $image_header_size = 40;
        $color_mask_size = $width * $height * 4;
        $opacity_mask_size = ( ceil( $width / 32 ) * 4 ) * $height;


        $data = pack( 'VVVvvVVVVVV', 40, $width, ( $height * 2 ), 1, 32, 0, 0, 0, 0, 0, 0 );

        foreach ( $pixel_data as $color )
            $data .= pack( 'V', $color );

        foreach ( $opacity_data as $opacity )
            $data .= pack( 'N', $opacity );


        $image = array(
            'width'                => $width,
            'height'               => $height,
            'color_palette_colors' => 0,
            'bits_per_pixel'       => 32,
            'size'                 => $image_header_size + $color_mask_size + $opacity_mask_size,
            'data'                 => $data,
        );

        $this->_images[] = $image;
    }

    function _load_image_file( $file ) {
        if ( false === ( $size = getimagesize( $file ) ) )
            return false;

        if ( false === ( $file_data = file_get_contents( $file ) ) )
            return false;

        if ( false === ( $im = imagecreatefromstring( $file_data ) ) )
            return false;

        unset( $file_data );


        return $im;
    }
}