<?php
/*
Plugin Name: RedBro Opencart to Woocommerce
Plugin URI:  https://github.com/alexeevdemyankov/redbrootw
Description: Opencart to Woocommerce goods parcer
Version: 1.0.0
Author: Alexeev-Demyankov Ilya
Author URI:  https://boosty.to/alexeevdemyankov/
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/


/**
 * install and unistall
 */

/**install**/
register_activation_hook(__FILE__, 'redbrootw_install');
function redbrootw_install()
{

    add_option('redbrootw', json_encode([]));
}

/**unininstall**/
register_uninstall_hook(__FILE__, 'redbrootw_uninstall');
function redbrootw_uninstall()
{

    delete_option('redbrootw');
}


/**Install**/
if (is_admin()) {
    include(plugin_dir_path(__FILE__) . 'inc/admin.php');
}


class redbrootw
{



    public static function getProduct($lastId)
    {
        $lastId=($lastId>0)?$lastId:0;

        $items = self::getQuery("SELECT * FROM `oc_product` WHERE  product_id > ".$lastId."  LIMIT 1");
        if (sizeof($items) > 0) {
            $item = current($items);
            $item['PROPERTIES'] = self::getProps($item['product_id']);
            $item['OPTIONS'] = self::getOptions($item['product_id']);
            $item['IMGS'] = self::getImgs($item['product_id']);
            $item['DESCRIPTIONS'] = self::getDesc($item['product_id']);
            $item['RELATED'] = self::getRelated($item['product_id']);
            $item['CATEGORY'] = self::getCategory($item['product_id']);
            if (sizeof($item['RELATED']) > 0) {
                foreach ($item['RELATED'] as $rid => $rated) {
                    $item['RELATED'][$rid] = self::getQuery("SELECT * FROM `oc_product` WHERE product_id=" . $rated['related_id'])[0];
                    $item['RELATED'][$rid]['OPTIONS'] = self::getOptions($rated['related_id']);
                    $item['RELATED'][$rid]['DESCRIPTIONS'] = self::getDesc($rated['related_id']);
                    $item['RELATED'][$rid]['IMGS'] = self::getImgs($rated['related_id']);
                }
            }
            return $item;
        } else {
            return null;
        }
    }

    public static function setProduct($item)
    {
        $postId = self::createWpProduct($item);
        if (!$postId > 0) return null;
        $item['PREP_ATTR'] = self::attrPrep($item);
        self::createWpAttr($item['PREP_ATTR']);
        $item['PREP_CATS'] = self::createWpCat($item['CATEGORY']);
        self::updateWpProduct($item, $postId);
        return 1;

    }

    /**setWPFunctions**/

    private static function createWpCat($cats)
    {
        $ids = [];
        foreach ($cats as $groupId => $catTree) {
            $parent = 0;
            foreach ($catTree as $catId => $cat) {
                $term_data = term_exists($cat['DESCRIPTION']['name'], 'product_cat');
                if (!$term_data) {
                    $term_data = wp_insert_term(
                        $cat['DESCRIPTION']['name'],
                        'product_cat',
                        array(
                            'description' => $cat['DESCRIPTION']['description'],
                            'slug' => self::slugTranslit($cat['DESCRIPTION']['name']),
                            'parent' => $parent
                        )
                    );
                    $parent = $term_data['term_id'];
                } else {
                    $parent = $term_data['term_id'];
                }
                $cats[$groupId][$catId]['TERM'] = $term_data;
                $ids[] = $term_data['term_id'];
            }
        }
        return array_unique($ids);
    }


    private static function createWpProduct($item)
    {

        if ($item['RELATED'] > 0) {
            $item['sku'] = 'var_' . $item['sku'];
        }

        $post_id = self::get_product_by_sku($item['sku']);

        if ($post_id > 0) {
            $parent_post = get_post_parent($post_id);
            /**skip parent**/
            if ($parent_post->id) return null;
        } else {
            $post = array(
                'post_content' => htmlspecialchars_decode($item['DESCRIPTIONS']['description']),
                'post_status' => "publish",
                'post_title' => $item['DESCRIPTIONS']['name'],
                'post_parent' => '',
                'post_type' => "product",
            );
            $post_id = wp_insert_post($post);
            update_post_meta($post_id, '_sku', $item['sku']);
        }

        update_post_meta($post_id, '_regular_price',  $item['price']);
        update_post_meta($post_id, '_price',  $item['price']);
        return $post_id;
    }

    /**prep Props**/
    private static function attrPrep($item)
    {
        $propsArray = [];
        foreach ($item['PROPERTIES'] as $property) {
            $propsArray[$property['name']]['NAME'] = trim($property['name']);
            $propsArray[$property['name']]['SLUG'] = self::slugTranslit($property['name']);
            $propsArray[$property['name']]['TAX'] = 'pa_' . self::slugTranslit($property['name']);
            $propsArray[$property['name']]['VARIABLE'] = FALSE;
            $propsArray[$property['name']]['VALUES'][$property['text']]['NAME'] = $property['text'];
            $propsArray[$property['name']]['VALUES'][$property['text']]['SLUG'] = self::slugTranslit($property['text']);
        }
        foreach ($item['RELATED'] as $relItem) {
            foreach ($relItem['OPTIONS'] as $option) {
                $propsArray[$option['name']]['VARIABLE'] = TRUE;
                $propsArray[$option['name']]['SLUG'] = self::slugTranslit($option['name']);
                $propsArray[$option['name']]['TAX'] = 'pa_' . self::slugTranslit($option['name']);
                $propsArray[$option['name']]['VALUES'][$option['namevalue']]['NAME'] = trim($option['namevalue']);
                $propsArray[$option['name']]['VALUES'][$option['namevalue']]['SLUG'] = self::slugTranslit($option['namevalue']);
            }
        }
        return $propsArray;
    }


    public static function slugTranslit($text)
    {
        $text = self::translit($text);
        if (strlen($text) > 20) $text = substr($text, 0, 20);
        return trim($text);

    }

    /**create attr**/
    public static function createWpAttr($attrs)
    {
        foreach ($attrs as $attr) {
            $taxonomy_name = wc_attribute_taxonomy_name($attr['NAME']);
            if (!taxonomy_exists($taxonomy_name)) {
                wc_create_attribute(array(
                    'name' => $attr['NAME'],
                    'slug' => $attr['SLUG'],
                    'type' => 'select',
                    'order_by' => 'menu_order',
                    'has_archives' => false,
                ));
                register_taxonomy(
                    $taxonomy_name,
                    apply_filters('woocommerce_taxonomy_objects_' . $taxonomy_name, array('product')),
                    apply_filters('woocommerce_taxonomy_args_' . $taxonomy_name, array(
                        'labels' => array(
                            'name' => $attr['NAME'],
                        ),
                        'hierarchical' => true,
                        'show_ui' => false,
                        'query_var' => true,
                        'rewrite' => false,
                    ))
                );
                delete_transient('wc_attribute_taxonomies');
            }
            foreach ($attr['VALUES'] as $attrValue) {
                $exist = term_exists($attrValue['SLUG'], $taxonomy_name);
                if (!$exist) {
                    wp_insert_term(
                        $attrValue['NAME'],
                        $taxonomy_name,
                        array(
                            'description' => '',
                            'slug' => $attrValue['SLUG']
                        )
                    );
                }

            }
        }
    }


    public static function updateWpProduct($item, $post_id)
    {

        /**set Attr**/
        foreach ($item['PREP_ATTR'] as $attr) {
            foreach ($attr['VALUES'] as $attrValue) {
                self::setWpProductAttribute($post_id, $attr['TAX'], $attrValue['NAME'], $attr['VARIABLE']);
            }
        }
        /**set variations**/
        if (sizeof($item['RELATED']) > 0) {
            wp_set_object_terms($post_id, 'variable', 'product_type');
            foreach ($item['RELATED'] as $rid => $related) {
                $item['RELATED'][$rid]['VARIANT_ID'] = self::setWpProductParent($item, $related, $post_id);
            }
        }

        /**set category**/
        foreach ($item['PREP_CATS'] as $catId) {
            if (!has_term($catId, 'product_cat', $post_id)) {
                wp_set_object_terms($post_id, (int)$catId, 'product_cat', true);
            }
        }


        /**update images**/
        $config = json_decode(get_option('redbrootw'), 1);
        if(!get_post_thumbnail_id( $post_id )) {
            self::setWpImage( $config['rbo__url'] . 'image/' . $item['image'],$post_id);
        }

        /**update images related**/
        foreach ( $item['RELATED'] as $related){
            if(!get_post_thumbnail_id($related['VARIANT_ID'])) {
                self::setWpImage( $config['rbo__url'] . 'image/' . $related['image'], $related['VARIANT_ID']);
            }
        }

        /**status**/
        $inStock=explode(',',$config['rbo__instock']);
        $outofstock=explode(',',$config['rbo__outofstock']);
        $onbackorder=explode(',',$config['rbo_onbackorder']);

        $status = (in_array($item['stock_status_id'], $inStock)) ? 'instock' : $item['stock_status_id'];
        $status = (in_array($item['stock_status_id'], $outofstock)) ? 'outofstock' : $status;
        $status = (in_array($item['stock_status_id'], $onbackorder)) ? 'onbackorder' :$status;
        update_post_meta( $post_id, '_stock_status', $status );
        foreach ( $item['RELATED'] as $related){
            $status = (in_array($related['stock_status_id'], $inStock)) ? 'instock' : $related['stock_status_id'];
            $status = (in_array($related['stock_status_id'], $outofstock)) ? 'outofstock' : $status;
            $status = (in_array($related['stock_status_id'], $onbackorder)) ? 'onbackorder' :$status;
            update_post_meta( $related['VARIANT_ID'], '_stock_status', $status );
        }

        /**galery**/
        $urlsImg=[];
        foreach ($item['IMGS'] as $img){
            $urlsImg[]=$config['rbo__url'] . 'image/' . $img['image'];
        }

        foreach ( $item['RELATED'] as $related){
            foreach ($related['IMGS'] as $img) {
                $urlsImg[]=$config['rbo__url'] . 'image/' . $img['image'];
            }
        }
        self::setWpGalery($urlsImg,$post_id);




    }


    private static function setWpGalery($urlsImg,$post_id){
        if(get_post_galleries($post_id))return null;
        if(sizeof($urlsImg)==0)return null;
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        foreach($urlsImg as $image_url) {
           $upload_dir = wp_upload_dir();
           $image_data = file_get_contents($image_url);
           $filename = basename($image_url);
           if (wp_mkdir_p($upload_dir['path'])) $file = $upload_dir['path'] . '/' . $filename;
           else                                    $file = $upload_dir['basedir'] . '/' . $filename;
           file_put_contents($file, $image_data);
           $wp_filetype = wp_check_filetype($filename, null);
           $attachment = array(
               'post_mime_type' => $wp_filetype['type'],
               'post_title' => sanitize_file_name($filename),
               'post_content' => '',
               'post_status' => 'inherit'
           );
           $attach_ids[] = wp_insert_attachment($attachment, $file, $post_id);


       }

        if(sizeof($attach_ids)>0) {
            update_post_meta($post_id, '_product_image_gallery', implode(',', $attach_ids));
        }

    }

    private static function setWpImage($image_url, $post_id)
    {
        if(has_post_thumbnail($post_id))return null;


        $upload_dir = wp_upload_dir();
        $image_data = file_get_contents($image_url);
        $filename = basename($image_url);
        if (wp_mkdir_p($upload_dir['path'])) $file = $upload_dir['path'] . '/' . $filename;
        else                                    $file = $upload_dir['basedir'] . '/' . $filename;
        file_put_contents($file, $image_data);

        $wp_filetype = wp_check_filetype($filename, null);
        $attachment = array(
            'post_mime_type' => $wp_filetype['type'],
            'post_title' => sanitize_file_name($filename),
            'post_content' => '',
            'post_status' => 'inherit'
        );
        $attach_id = wp_insert_attachment($attachment, $file, $post_id);
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $file);
        $res1 = wp_update_attachment_metadata($attach_id, $attach_data);
        $res2 = set_post_thumbnail($post_id, $attach_id);
    }


    private static function setWpProductParent($item, $related, $parent_id)
    {

        $variation_id = self::get_product_by_sku($related['sku']);


        if ($variation_id > 0) return $variation_id;
        $variation = array(
            'post_title' => $related['DESCRIPTIONS']['name'],
            'post_content' => '',
            'post_status' => 'publish',
            'post_parent' => $parent_id,
            'post_type' => 'product_variation'
        );
        $price = ($related['price'] > 0) ? $related['price'] : $item['price'];

        $variation_id = wp_insert_post($variation);
        update_post_meta($variation_id, '_regular_price', $price);
        update_post_meta($variation_id, '_price', $price);
        update_post_meta($variation_id, '_sku', $related['sku']);

        foreach ($related['OPTIONS'] as $rateOption) {
            $variationAttr = self::getWpAttrTax($rateOption['name'], $rateOption['namevalue']);
            update_post_meta($variation_id, 'attribute_' . $variationAttr['taxonomy'], $variationAttr['slug']);
        }
        WC_Product_Variable::sync($parent_id);
        return $variation_id;
    }


    private static function getWpAttrTax($nameParam, $nameVal)
    {
        $taxonomy = self::translit($nameParam);
        if (strlen($taxonomy) > 20) $taxonomy = substr($taxonomy, 0, 20);
        $taxonomyParam = 'pa_' . $taxonomy;
        $res = get_term_by('name', $nameVal, $taxonomyParam, ARRAY_A);
        $res['tax_slug'] = $taxonomy;

        return $res;
    }

    private static function setWpProductAttribute($post_id, $taxonomy, $value, $variation = false)
    {
        $taxonomyValue = get_term_by('name', $value, $taxonomy);
        $product = wc_get_product($post_id);
        $attributes = $product->get_attributes();
        if (array_key_exists($taxonomy, $attributes)) {
            foreach ($attributes as $key => $attribute) {
                if ($key == $taxonomy) {
                    $options = $attribute->get_options();
                    $options[] = $taxonomyValue->term_id;
                    $options = array_unique($options);
                    $attribute->set_variation($variation);
                    $attribute->set_options($options);
                    $attributes[$key] = $attribute;
                    break;
                }
            }
            $product->set_attributes($attributes);
            $product->save();
        } else {
            $attribute = new WC_Product_Attribute();
            $attribute->set_id(sizeof($attributes) + 1);
            $attribute->set_name($taxonomy);
            $attribute->set_options([$taxonomyValue->term_id]);
            $attribute->set_position(sizeof($attributes) + 1);
            $attribute->set_visible(true);
            $attribute->set_variation($variation);
            $attributes[] = $attribute;
            $product->set_attributes($attributes);
            $product->save();
        }
        if (!has_term($taxonomyValue->term_id, $taxonomy, $post_id)) {
            wp_set_object_terms($post_id, $taxonomyValue->term_id, $taxonomy, true);
        }
    }





    private static function get_product_by_sku($sku)
    {
        global $wpdb;
        $product_id = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_sku' AND meta_value='%s' LIMIT 1", $sku));
        if ($product_id) return $product_id;
        return null;
    }


    /**opencart**/

    private static function getCategory($pid)
    {
        $cats = self::getQuery("SELECT * FROM `oc_product_to_category` WHERE product_id=" . $pid);
        $return = [];
        foreach ($cats as $cat) {
            $return[$cat['category_id']] = self::buildCatPatch($cat['category_id']);
        }
        return $return;
    }

    private static function buildCatPatch($cid)
    {
        $catPatch = [];
        $cat = self::getQuery("SELECT * FROM `oc_category` WHERE category_id=" . $cid)[0];
        $catPatch[] = $cat;
        $parent = $cat['parent_id'];
        while ($parent) {
            $parentData = self::getQuery('SELECT * FROM `oc_category`  where category_id=' . $parent)[0];
            $catPatch[] = $parentData;
            $parent = $parentData['parent_id'];
        }
        foreach ($catPatch as $ccii => $catPat) {
            $catPatch[$ccii]['DESCRIPTION'] = self::getQuery('SELECT * FROM `oc_category_description`  where category_id=' . $catPat['category_id'])[0];
        }

        $catPatch = array_reverse($catPatch);
        return $catPatch;

    }


    private static function getRelated($pid)
    {
        return self::getQuery("SELECT * FROM `oc_product_related` WHERE product_id=" . $pid);
    }

    private static function getDesc($pid)
    {
        return self::getQuery("SELECT * FROM `oc_product_description` WHERE product_id=" . $pid)[0];
    }


    private static function getImgs($pid)
    {
        return self::getQuery("SELECT * FROM `oc_product_image` WHERE product_id=" . $pid);
    }

    private static function getOptions($pid)
    {
        $options = self::getQuery("SELECT * FROM `oc_product_option_value` WHERE product_id=" . $pid);
        foreach ($options as $oid => $option) {
            $optName = self::getQuery("SELECT * FROM `oc_option_description` WHERE option_id=" . $option['option_id']);
            $options[$oid]['name'] = $optName[0]['name'];

            $optValame = self::getQuery("SELECT * FROM `oc_ocfilter_option_value_description` WHERE option_id=" . $option['option_id'] . " and value_id=" . $option['option_value_id']);
            $options[$oid]['namevalue'] = $optValame[0]['name'];

            $optImg = self::getQuery("SELECT * FROM `oc_option_value` WHERE option_id=" . $option['option_id'] . " and option_value_id=" . $option['option_value_id']);
            $options[$oid]['img'] = $optImg[0]['image'];
        }
        return $options;
    }


    private static function getProps($pid)
    {
        $props = self::getQuery("SELECT * FROM `oc_product_attribute` WHERE product_id=" . $pid);
        foreach ($props as $pid => $prop) {
            $propName = self::getQuery("SELECT * FROM `oc_attribute_description` WHERE attribute_id=" . $prop['attribute_id'] . " and language_id=" . $prop['language_id']);
            $props[$pid]['name'] = $propName[0]['name'];
        }
        return $props;
    }


    private static function getQuery($query)
    {
        $config = json_decode(get_option('redbrootw'), 1);
        $return = [];
        $link = mysqli_connect($config['rbo__sqlhost'] . ':' . $config['rbo__sqlport'], $config['rbo__sqllogin'], $config['rbo__sqlpassword'], $config['rbo__sqldb']);
        if (!$link) {
            echo "Error connect";
            return null;
        }
        $result = mysqli_query($link, $query);
        while ($row = $result->fetch_assoc()) {
            $return[] = $row;
        }
        return $return;
    }


    public static function getPrepQuery($query, $key)
    {
        $res = [];
        $items = self::getQuery($query);
        foreach ($items as $item) {
            $res[$item[$key]] = $item;
        }
        return $res;
    }

    public static function pre($array)
    {
        echo "<pre>";
        print_r($array);
        echo "</pre>";
    }


    private static function translit($value)
    {
        $converter = array(
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
            'е' => 'e', 'ё' => 'e', 'ж' => 'zh', 'з' => 'z', 'и' => 'i',
            'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
            'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
            'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch',
            'ш' => 'sh', 'щ' => 'sch', 'ь' => '', 'ы' => 'y', 'ъ' => '',
            'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
        );

        $value = mb_strtolower($value);
        $value = strtr($value, $converter);
        $value = mb_ereg_replace('[^-0-9a-z]', '-', $value);
        $value = mb_ereg_replace('[-]+', '-', $value);
        $value = trim($value, '-');

        return $value;
    }


}




