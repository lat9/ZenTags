<?php
// -----
// Part of the "Zen Tags" plugin by Cindy Merkin (lat9)
// Copyright (c) 2014-2018 Vinos de Frutas Tropicales
//
if (!defined('IS_ADMIN_FLAG') || IS_ADMIN_FLAG !== true) {
    die('Illegal Access');
}

class ZenTagsAdminObserver extends base 
{
    protected $zenTags;

    public function __construct() {
        $this->attach (
            $this, 
            array( 
                /* Issued by /includes/functions/general.php */
                'NOTIFIER_ADMIN_ZEN_REMOVE_CATEGORY', 'NOTIFIER_ADMIN_ZEN_REMOVE_PRODUCT',
                                  
                /* Issued by /includes/modules/copy_to_confirm.php */
                'NOTIFY_MODULES_COPY_TO_CONFIRM_DUPLICATE',
                                  
                /* Issued by /includes/modules/update_product.php */
                'NOTIFY_MODULES_UPDATE_PRODUCT_END',
            ) 
        );
    }
  
    public function update(&$class, $eventID, $p1a, &$p2, &$p3) {
        global $db;
        switch ($eventID) {
            // -----
            // If a product is removed, make sure that any references to its tags are removed as well.
            //
            case 'NOTIFIER_ADMIN_ZEN_REMOVE_PRODUCT':
                $zen_tags = new ZenTags();
                $zen_tags->removeProductTags($p2);
                break;

            // -----
            // If a category is removed, make sure that any references to its tags are removed as well.
            //
            case 'NOTIFIER_ADMIN_ZEN_REMOVE_CATEGORY':
                $zen_tags = new ZenTags();
                $zen_tags->removeCategoryTags($p2);
                break;

            // -----
            // If a product is being updated, update its tag names as well.
            //
            case 'NOTIFY_MODULES_UPDATE_PRODUCT_END':
                $zen_tags = new ZenTags();
                $zen_tags->updateProductTagInputs((int)$p1a['products_id']);
                break;

            // -----
            // If a product is duplicated, the duplicate product re-uses the current product's tags.
            //
            case 'NOTIFY_MODULES_COPY_TO_CONFIRM_DUPLICATE':
                $zen_tags = new ZenTags();
                $zen_tags->duplicateProductTagInputs($p1a['dup_products_id'], $p1a['products_id']);
                break;
            
            // -----
            // Anything else ...
            //
            default:
                break;
        }
    }
}
