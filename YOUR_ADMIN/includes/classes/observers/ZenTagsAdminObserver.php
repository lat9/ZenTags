<?php
// -----
// Part of the "Zen Tags" plugin by Cindy Merkin (lat9)
// Copyright (c) 2014-2019 Vinos de Frutas Tropicales
//
if (!defined('IS_ADMIN_FLAG') || IS_ADMIN_FLAG !== true) {
    die('Illegal Access');
}

class ZenTagsAdminObserver extends base 
{
    public function __construct() {
        if (!defined('ZEN_TAGS_VERSION')) {
            return;
        }
        $this->attach (
            $this, 
            array( 
                /* Issued by /includes/functions/general.php */
                'NOTIFIER_ADMIN_ZEN_REMOVE_CATEGORY', 
                'NOTIFIER_ADMIN_ZEN_REMOVE_PRODUCT',
                                  
                /* Issued by /includes/modules/copy_to_confirm.php */
                'NOTIFY_MODULES_COPY_TO_CONFIRM_DUPLICATE',
                                  
                /* Issued by /includes/modules/update_product.php */
                'NOTIFY_MODULES_UPDATE_PRODUCT_END',
                
                /* Issued by /categories.php */
                'NOTIFY_ADMIN_CATEGORIES_EXTRA_INPUTS',
                
                /* Issued by /modules/{product_type}/collect_info.php */
                'NOTIFY_ADMIN_PRODUCT_COLLECT_INFO_EXTRA_INPUTS',
            ) 
        );
    }
  
    public function update(&$class, $eventID, $p1, &$p2, &$p3) {
        global $db;
        
        $zen_tags = new ZenTags();
        switch ($eventID) {
            // -----
            // If a product is removed, make sure that any references to its tags are removed as well.
            //
            case 'NOTIFIER_ADMIN_ZEN_REMOVE_PRODUCT':
                $zen_tags->removeProductTags($p2);
                break;

            // -----
            // If a category is removed, make sure that any references to its tags are removed as well.
            //
            case 'NOTIFIER_ADMIN_ZEN_REMOVE_CATEGORY':
                $zen_tags->removeCategoryTags($p2);
                break;

            // -----
            // If a product is duplicated, the duplicate product re-uses the current product's tags.
            //
            case 'NOTIFY_MODULES_COPY_TO_CONFIRM_DUPLICATE':
                $zen_tags->duplicateProductTagInputs($p1['products_id'], $p1['dup_products_id']);
                break;
                
            // -----
            // During a category-edit, include the form-field to enable tags to be defined
            // for the current category. The notification is also issued for
            // *new* categories, in which case the categories_id value is not yet set.
            //
            // On entry:
            //
            // - $p1 ... (r/o) A copy of the current category's $cInfo object.
            // - $p2 ... (r/w) A reference to the $extra_category_inputs array.
            //
            case 'NOTIFY_ADMIN_CATEGORIES_EXTRA_INPUTS':
                if (isset($p1->categories_id)) {
                    $extra_category_inputs = $zen_tags->generateCategoryTagInputs($p1->categories_id);
                } else {
                    $extra_category_inputs = $zen_tags->generateCategoryTagPlaceholder();
                }
                if (is_array($extra_category_inputs)) {
                    $p2[] = $extra_category_inputs;
                }
                break;
                
            // -----
            // During a product-edit, include the form-field to enable tags to be defined
            // for the current product.  The notification is also issued for *new*
            // products, in which case the products_id value is not yet set.
            //
            // On entry:
            //
            // - $p1 ... (r/o) A copy of the current product's $pInfo object.
            // - $p2 ... (r/w) A reference to the $extra_product_inputs array.
            //
            //
            case 'NOTIFY_ADMIN_PRODUCT_COLLECT_INFO_EXTRA_INPUTS':
                if (isset($p1->products_id)) {
                    $extra_product_inputs = $zen_tags->generateProductTagInputs($p1->products_id);
                } else {
                    $extra_product_inputs = $zen_tags->generateProductTagPlaceholder();
                }
                if (is_array($extra_product_inputs)) {
                    $p2[] = $extra_product_inputs;
                }
                break;
            
            // -----
            // Anything else ...
            //
            default:
                break;
        }
    }
}
