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
        
        if($_REQUEST["wwcs_key"]){
            $inputname = $_REQUEST["wwcs_key"];
        }else{
            $inputname = "wwcs_key_".$_REQUEST["wwcs_cid"];
        }
        
        $inputs = array();
        $inputs[$_REQUEST["wwcs_key"]."[]"] = array(
            "label" => $_REQUEST["wwcs_label"],
            "type" => "select",
            "options" => $options,
            "class" => array("form-row-wide"),
            "value" => ""
        );
        
        ob_end_clean();
        echo '<div class="wwcs_categories wwcs_c'.$_REQUEST["wwcs_cid"].'   wwcs_c'.$_REQUEST["wwcs_pid"].'" id="wwcs_category_'.$_REQUEST["wwcs_cid"].'" data-parent="'.$_REQUEST["wwcs_cid"].'">';
        foreach ($inputs as $key => $field) {
            woocommerce_form_field( $key, $field, ! empty( $_REQUEST[$key] ) ? wc_clean( $_REQUEST[ $key ] ) : $field['value'] );
        }
        echo '</div>';
        die();
    }


    add_shortcode('wwcs_get_categories_basic', 'wwcs_get_categories_basic');

    function wwcs_get_categories_basic($attr) {
        if($attr["parent"] === ""){
            $attr["parent"] = "0";
        }
        $attr["default"] = array("32","41");
        $ajaxurl = admin_url('admin-ajax.php');
        $defaultclass = "";
        foreach ($attr["default"] as $dc) {
            $defaultclass .= " wwcs_dc_".$dc;
        }
        echo '<div class="wwcs_container" data-defaults="'.json_encode($attr["default"]).'">';
        echo '</div>';

    ?>
        <script type="text/javascript">
            var defaults = <?=json_encode($attr["default"])?>;
            function wwcs_get_categories(parent,grandparent,label,key){
                grandparent = typeof grandparent !== 'undefined' ? grandparent : "0";
                label       = typeof label !== 'undefined' ? label : "Tür";
                key         = typeof key !== 'undefined' ? key : "ktpt_np_genre";
                jQuery.ajax({
                    type: "POST",
                    url:  "<?=$ajaxurl?>",
                    data: "action=wwcs_get_categories&wwcs_cid="+parent+"&wwcs_pid="+grandparent+"&wwcs_label="+label+"&wwcs_key="+key,  
                    success: function(msg){
                        var content = jQuery('<div/>').html(msg).contents();
//                        content.find("select").val("val2");
                        console.log(defaults);
                        content.find("select option").each(function() {
                            if(jQuery.inArray(this.value, defaults)){
                                content.find("select").val(this.value);
                                return false;
                            }
//                            alert(this.text + ' ' + this.value);
                        });
                        jQuery(".wwcs_container").append(content);
                        jQuery("#wwcs_category_"+parent).addClass(jQuery("#wwcs_category_"+grandparent).attr('class'));
                        jQuery("#wwcs_category_"+parent+" select").change(function(){
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
                if(jQuery("#wwcs_category_"+parent).length){
                    var siblings = [];
                    jQuery("#wwcs_category_"+parent).find("select option").each(function(){
                        console.log(this.value);
                        if(activechild !== this.value){
                            siblings.push(this.value);
                        }
                    });
                    return siblings;
                }else{
                    return false;
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
    
    add_action( 'wp_ajax_wwcs_get_categories_once', 'wwcs_get_categories_once' );
    add_action( 'wp_ajax_nopriv_wwcs_get_categories_once', 'wwcs_get_categories_once' );
    
    add_shortcode('wwcs_get_categories_once', 'wwcs_get_categories_once');
    
    function wwcs_get_categories_once($attr) {
        ob_start();
        if(array_key_exists("parent", $_REQUEST)){
            $attr["parent"] = $_REQUEST["parent"];
        }
        if($attr["parent"] === ""){
            $attr["parent"] = "0";
        }
        $taxonomy     = 'product_cat';
        $orderby      = 'name';  
        $show_count   = 0;      // 1 for yes, 0 for no
        $pad_counts   = 0;      // 1 for yes, 0 for no
        $hierarchical = 1;      // 1 for yes, 0 for no  
        $title        = '';  
        $empty        = 0;
        $args = array(
//            'child_of'     => 0,
            'parent'       => $attr["parent"],
            'taxonomy'     => $taxonomy,
            'orderby'      => $orderby,
            'show_count'   => $show_count,
            'pad_counts'   => $pad_counts,
            'hierarchical' => $hierarchical,
            'title_li'     => $title,
            'hide_empty'   => $empty
        );
        $thecategories = get_categories( $args );
        if(empty($thecategories)){
            echo '';
        }else{
            $options = array();
            foreach ($thecategories as $thecategory) {
//                $options[$thecategory->cat_ID] = $thecategory->cat_name;
//                $options[$thecategory->term_id] = $thecategory->cat_name;
                $options[$thecategory->term_id] = $thecategory->cat_name;
            }
            $uclass = "wwcs_".$attr["parent"];
            $inputs = array();
            $inputs["ktpt_np_genre[]"] = array(
                "label" => $attr["label"],
                "type" => "select",
                "options" => $options,
                "class" => array("form-row-wide",$uclass),
                "value" => $attr["default"]
            );
            $ajaxurl = admin_url('admin-ajax.php');
            ob_end_clean();
            foreach ($inputs as $key => $field) {
                woocommerce_form_field( $key,$field,$attr["default"]);
            }
            ?>
            <script type="text/javascript">
//                var allcats = <?=json_encode($thecategories)?>;
                jQuery(".<?=$uclass?>").change(function(){
                    var selectedoption = jQuery(".<?=$uclass?>  option:selected").val();
                    console.log("Seleceted ID:"+selectedoption);
                    jQuery.ajax({
                        type: "POST",
                        url:  "<?=$ajaxurl?>",
                        data: "action=wwcs_get_categories_once&parent="+selectedoption,  
                        success: function(response){
                            var data = response.substr(response.length-1, 1) === '0'? response.substr(0, response.length-1) : response;
                            console.log(data);
                            jQuery(".<?=$uclass?>").after(data);
                        }
                    });
                });   
            </script>
            <?php
        }
    }
        
        
    add_shortcode('wwcs_get_categories_edit', 'wwcs_get_categories_edit');
    
    function wwcs_get_categories_edit($attr) {
        ob_start();
        $taxonomy     = 'product_cat';
        $orderby      = 'name';  
        $show_count   = 0;      // 1 for yes, 0 for no
        $pad_counts   = 0;      // 1 for yes, 0 for no
        $hierarchical = 1;      // 1 for yes, 0 for no  
        $title        = '';  
        $empty        = 0;
        $args = array(
//            'child_of'     => 0,
            'parent'       => $attr["parent"],
            'taxonomy'     => $taxonomy,
            'orderby'      => $orderby,
            'show_count'   => $show_count,
            'pad_counts'   => $pad_counts,
            'hierarchical' => $hierarchical,
            'title_li'     => $title,
            'hide_empty'   => $empty
        );
        $thecategories = get_categories( $args );
        if(empty($thecategories)){
            echo '';
        }else{
            $options = array();
            foreach ($thecategories as $thecategory) {
//                $options[$thecategory->cat_ID] = $thecategory->cat_name;
//                $options[$thecategory->term_id] = $thecategory->cat_name;
                $options[$thecategory->term_id] = $thecategory->cat_name;
            }
            $uclass = "wwcs_".$attr["parent"];
            $inputs = array();
            $inputs["ktpt_np_genre[]"] = array(
                "label" => $attr["label"],
                "type" => "select",
                "options" => $options,
                "class" => array("form-row-wide",$uclass),
                "value" => $attr["default"]
            );
            $ajaxurl = admin_url('admin-ajax.php');
            ob_end_clean();
            foreach ($inputs as $key => $field) {
                woocommerce_form_field( $key,$field,$attr["default"]);
            }
            ?>
            <script type="text/javascript">
//                var allcats = <?=json_encode($thecategories)?>;
                jQuery(".<?=$uclass?>").change(function(){
                    var selectedoption = jQuery(".<?=$uclass?>  option:selected").val();
                    console.log("Seleceted ID:"+selectedoption);
//                    var options = jQuery.map(jQuery('.<?=$uclass?> option'), function(e) { return e.value; });
                    jQuery('.<?=$uclass?> option').map(function() { 
//                         console.log(jQuery(this).val());
                         jQuery('.wwcs_'+jQuery(this).val()).remove();
                    });
                    jQuery.ajax({
                        type: "POST",
                        url:  "<?=$ajaxurl?>",
                        data: "action=wwcs_get_categories_once&parent="+selectedoption,  
                        success: function(response){
                            var data = response.substr(response.length-1, 1) === '0'? response.substr(0, response.length-1) : response;
                            
                            console.log(data);
                            jQuery(".<?=$uclass?>").after(data);
                        }
                    });
                });   
            </script>
            <?php
        }
    }
}