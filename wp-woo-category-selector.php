<?php
/**
 * Plugin Name: Woocommerce Category Selector
 * Plugin URI: #
 * Description: Woocommerce category selector for custom forms.
 * Version: 1.0.0
 * Author: hayzem
 * Author URI: http://hayzem.net
 * License: GPL2
 */


if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}


/**
 * Detect plugin. For use on Front End only.
 */
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

// check for plugin using plugin name
if ( !is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
    
}else{

    add_action( 'wp_ajax_wwcs_get_categories', 'wwcs_get_categories' );
    add_action( 'wp_ajax_nopriv_wwcs_get_categories', 'wwcs_get_categories' );


    function wwcs_get_categories(){
        ob_start();
        $taxonomy     = 'product_cat';
        $orderby      = 'name';  
        $show_count   = 0;      // 1 for yes, 0 for no
        $pad_counts   = 0;      // 1 for yes, 0 for no
        $hierarchical = 1;      // 1 for yes, 0 for no  
        $title        = '';  
        $empty        = 0;

        $args = array(
            'child_of'     => 0,
            'parent'       => $_REQUEST["wwcs_cid"],
            'taxonomy'     => $taxonomy,
            'orderby'      => $orderby,
            'show_count'   => $show_count,
            'pad_counts'   => $pad_counts,
            'hierarchical' => $hierarchical,
            'title_li'     => $title,
            'hide_empty'   => $empty
        );
        
        $thecategories = get_categories( $args );
        $options = array();
        foreach ($thecategories as $thecategory) {
            $options[$thecategory->cat_ID] = $thecategory->cat_name;
        }
        if($_REQUEST["wwcs_input_name"]){
            $inputname = $_REQUEST["wwcs_input_name"];
        }else{
            $inputname = "wwcs_input_".$_REQUEST["wwcs_cid"];
        }
        $inputs = array();
        $inputs[$inputname] = array(
            "type" => "select",
            "options" => $options,
            "class" => array("form-row-wide"),
            "value" => ""
        );
        
        ob_end_clean();
        echo '<div class="wwcs_categories wwcs_c'.$_REQUEST["wwcs_cid"].'   wwcs_c'.$_REQUEST["wwcs_pid"].'" id="wwcs_category_'.$_REQUEST["wwcs_cid"].'" data-parent="'.$_REQUEST["wwcs_cid"].'">';
        foreach ($inputs as $key => $field) {
            woocommerce_form_field( $key, $field, ! empty( $_POST[$key] ) ? wc_clean( $_POST[ $key ] ) : $field['value'] );
        }
        echo '</div>';
        die();
    }


    add_shortcode('wwcs_get_categories_basic', 'wwcs_get_categories_basic');

    function wwcs_get_categories_basic($attr) {
        if($attr["parent"] === ""){
            $attr["parent"] = "0";
        }
        $ajaxurl = admin_url('admin-ajax.php');
        echo '<div class="wwcs_container">';
        echo '</div>';

    ?>
        <script type="text/javascript">
            function wwcs_get_categories(parent,grandparent){
                grandparent = typeof grandparent !== 'undefined' ? grandparent : "0";
                jQuery.ajax({
                    type: "POST",
                    url:  "<?=$ajaxurl?>",
                    data: "action=wwcs_get_categories&wwcs_cid="+parent+"&wwcs_pid="+grandparent,  
                    success: function(msg){
                        jQuery(".wwcs_container").append(msg);
                        jQuery("#wwcs_category_"+parent).addClass(jQuery("#wwcs_category_"+grandparent).attr('class'));
                        jQuery("#wwcs_input_"+parent).change(function(){
                            wwcs_get_categories(this.value,parent);
                        });
                        if(parent !== grandparent){
                            var badsiblings = wwcs_get_siblings(grandparent,parent);
                            console.log(badsiblings);
                            wwcs_remove_generation(badsiblings);
                        }
                    }
                });
            }
            
            function wwcs_get_siblings(parent,activechild){
                if(jQuery("#wwcs_input_"+parent).length){
                    var siblings = [];
                    jQuery("#wwcs_input_"+parent+" option").each(function(){
                        console.log(this.value);
                        if(activechild !== this.value){
                            siblings.push(this.value);
                        }
                    });
                    return siblings;
                }else{
                    return FALSE;
                }
            }
            
            function wwcs_remove_generation(parents){
                if (parents instanceof Array) {
                    jQuery.each(parents,function(k,v){
                        console.log("removing element class:"+v);
                        jQuery(".wwcs_c"+v).remove();
                    });
                };
            }            
            wwcs_get_categories(<?=$attr["parent"]?>);
        </script>
    <?php
    }
}