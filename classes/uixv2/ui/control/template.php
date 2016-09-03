<?php
/**
 * UIX Metaboxes
 *
 * @package   uixv2
 * @author    David Cramer
 * @license   GPL-2.0+
 * @link
 * @copyright 2016 David Cramer
 */
namespace uixv2\ui\control;

/**
 * UIX Control class.
 *
 * @since 2.0.0
 */
class template extends \uixv2\ui\controls{


    /**
     * Returns the main input field for rendering
     *
     * @since 2.0.0
     * @see \uixv2\ui\uix
     * @param string $slug Control slug to be rendered
     * @return string 
     */
    public function input( $slug ){
        
        $control = $this->get( $slug );

        if( !empty( $control['template'] ) && file_exists( $control['template'] ) ){ ?>
            <?php include $control['template']; ?>
            </div>
        <?php }
    }    
     

}