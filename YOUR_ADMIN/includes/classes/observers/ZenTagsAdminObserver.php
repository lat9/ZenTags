<?php
// -----
// Part of the "Zen Tags" plugin by Cindy Merkin (lat9)
// Copyright (c) 2014-2024 Vinos de Frutas Tropicales
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
            [
                /* Issued by /includes/functions/functions_categories.php */
                'NOTIFIER_ADMIN_ZEN_REMOVE_CATEGORY',

                /* Issued by /includes/functions/functions_products.php */
                'NOTIFIER_ADMIN_ZEN_REMOVE_PRODUCT',

                /* Issued by /admin/includes/modules/copy_to_confirm.php */
                'NOTIFY_MODULES_COPY_TO_CONFIRM_DUPLICATE',

                /* Issued by /categories.php */
                'NOTIFY_ADMIN_CATEGORIES_EXTRA_INPUTS',

                /* Issued by /modules/{product_type/}collect_info.php */
                'NOTIFY_ADMIN_PRODUCT_COLLECT_INFO_EXTRA_INPUTS',
            ]
        );
    }

    // -----
    // If a category is removed, make sure that any references to its tags are removed as well.
    //
    protected function updateNotifierAdminZenRemoveCategory(&$class, $eventID, $empty_array, &$categories_id)
    {
        $zen_tags = new ZenTags();
        $zen_tags->removeCategoryTags($categories_id);
    }

    // -----
    // If a product is removed, make sure that any references to its tags are removed as well.
    //
    protected function updateNotifierAdminZenRemoveProduct(&$class, $eventID, $empty_array, &$product_id)
    {
        $zen_tags = new ZenTags();
        $zen_tags->removeProductTags($products_id);
    }

    // -----
    // If a product is duplicated, the duplicate product re-uses the current product's tags.
    //
    protected function updateNotifyModulesCopyToConfirmDuplicate(&$class, $eventID, $info_array)
    {
        $zen_tags = new ZenTags();
        $zen_tags->duplicateProductTagInputs($info_array['products_id'], $info_array['dup_products_id']);
    }

    // -----
    // During a category-edit, include the form-field to enable tags to be defined
    // for the current category. The notification is also issued for
    // *new* categories, in which case the categories_id value is not yet set.
    //
    protected function updateNotifyAdminCategoriesExtraInputs(&$class, $eventID, $cInfo, &$extra_category_inputs)
    {
        $zen_tags = new ZenTags();
        if (isset($cInfo->categories_id)) {
            $category_inputs = $zen_tags->generateCategoryTagInputs($cInfo->categories_id);
        } else {
            $category_inputs = $zen_tags->generateCategoryTagPlaceholder();
        }
        if (is_array($category_inputs)) {
            $extra_category_inputs[] = $category_inputs;
        }
    }

    // -----
    // During a product-edit, include the form-field to enable tags to be defined
    // for the current product.  The notification is also issued for *new*
    // products, in which case the products_id value is not yet set.
    //
    protected function updateNotifyAdminProductCollectInfoExtraInputs(&$class, $eventID, $pInfo, &$extra_product_inputs)
    {
        $zen_tags = new ZenTags();
        if (isset($pInfo->products_id)) {
            $product_inputs = $zen_tags->generateProductTagInputs($pInfo->products_id);
        } else {
            $product_inputs = $zen_tags->generateProductTagPlaceholder();
        }
        if (is_array($product_inputs)) {
            $extra_product_inputs[] = $product_inputs;
        }
    }
}
