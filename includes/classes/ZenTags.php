<?php
// -----
// Part of the "Zen Tags" plugin by lat9
// Copyright (c) 2014-2025, Vinos de Frutas Tropicales
//
if (!defined ('IS_ADMIN_FLAG')) {
    exit ('Illegal Access');
}

// -----
// Zen Cart Tags
//
class ZenTags
{
    // -----
    // Used to identify the different associations between the tags and their "base" elements.
    //
    const TAG_MAP_PRODUCT  = 1;
    const TAG_MAP_CATEGORY = 2;
    const TAG_MAP_NEWS     = 3;

    protected bool $removeUnusedTags;
    protected int $smallestFont;
    protected int $largestFont;
    protected string $fontUnits;
    protected int $maxTags;
    protected string $tagsOrderBy;
    protected string $tagsSortOrder;
    protected array $tags;
    protected $font_increment;

    public function __construct()
    {
        $this->removeUnusedTags = (ZEN_TAGS_REMOVE_IF_NOT_USED === 'true');
        $this->smallestFont = (int)ZEN_TAGS_CLOUD_SMALLEST;
        $this->largestFont = (int)ZEN_TAGS_CLOUD_LARGEST;
        $this->fontUnits = ZEN_TAGS_CLOUD_UNITS;
        $this->maxTags = (int)ZEN_TAGS_CLOUD_MAX;
        $this->tagsOrderBy = ZEN_TAGS_ORDER_BY;
        $this->tagsSortOrder = ZEN_TAGS_SORT_ORDER;
    }

    public function generateCategoryTagInputs($categories_id)
    {
        $tag_inputs = $this->generateTagInputsProtected((int)$categories_id, TABLE_TAGS_TO_CATEGORIES);
        if (is_array($tag_inputs)) {
            $tag_inputs['label']['text'] .= ('<br><strong>' . ZEN_TAG_LABEL_NOTE . '</strong> ' . ZEN_TAG_LABEL_CATEGORY_INSTRUCTIONS . '<br>');
        }
        return $tag_inputs;
    }

    public function updateCategoryTagInputs($categories_id)
    {
        $this->updateTagInputsProtected((int)$categories_id, TABLE_TAGS_TO_CATEGORIES);
        return;
    }

    public function generateProductTagInputs($products_id)
    {
        return $this->generateTagInputsProtected((int)$products_id, TABLE_TAGS_TO_PRODUCTS);
    }

    public function updateProductTagInputs($products_id)
    {
        $this->updateTagInputsProtected((int)$products_id, TABLE_TAGS_TO_PRODUCTS);
        return;
    }

    public function duplicateProductTagInputs($source_products_id, $dup_products_id)
    {
        $this->updateTagInputsProtected((int)$dup_products_id, TABLE_TAGS_TO_PRODUCTS, $this->getProductTagList($source_products_id));
        return;
    }

    public function generateTagInputs($tag_mapping_id, $tag_mapping_type)
    {
        $tag_mapping_table = $this->sanitizeTagMappingType($tag_mapping_type);
        return $this->generateTagInputsProtected((int)$tag_mapping_id, $tag_mapping_table);
    }

    public function updateTagInputs($tag_mapping_id, $tag_mapping_type)
    {
        $tag_mapping_table = $this->sanitizeTagMappingType($tag_mapping_type);
        $this->updateTagInputsProtected((int)$tag_mapping_id, $tag_mapping_table);
        return;
    }

    public function generateTagsList($tag_mapping_id, $tag_mapping_type)
    {
        $tag_mapping_table = $this->sanitizeTagMappingType($tag_mapping_type);
        return $this->generateTagsListProtected((int)$tag_mapping_id, $tag_mapping_table);
    }

    public function generateCategoryTagPlaceholder()
    {
        return $this->generateTagPlaceholder(ZEN_TAG_CREATE_CATEGORY_FIRST);
    }
    
    public function generateProductTagPlaceholder()
    {
        return $this->generateTagPlaceholder(ZEN_TAG_CREATE_PRODUCT_FIRST);
    }

    // -----
    // Generate the common tag-input block.  Contains three separate areas:
    // 1) The tag input box, with add button.  Adds new tags (comma-separated).
    // 2) The tag remove box, you can individually remove tags one by one.
    // 3) The tag cloud box, you can add tags to the current category/product by clicking a cloud link.
    //
    protected function generateTagInputsProtected($tag_mapping_id, $tag_mapping_table)
    {
        $tag_inputs = false;
        if (IS_ADMIN_FLAG === true) {
            $zen_tags = $this->generateTagsListProtected($tag_mapping_id, $tag_mapping_table);
            $tag_mapping_type = ($tag_mapping_table == TABLE_TAGS_TO_PRODUCTS) ? self::TAG_MAP_PRODUCT : self::TAG_MAP_CATEGORY;
            $the_input = '<div id="zen-tags-add">' . zen_draw_input_field('zen_tags', '', 'id="zen-tag-input"') . '&nbsp;&nbsp;<button type="button">Add</button>&nbsp;&nbsp;' . ZEN_TAG_TEXT_SEPARATE_TAGS . '</div>';
            $the_input .= zen_draw_hidden_field('tag_mapping_id', $tag_mapping_id, 'id="tag_mapping_id"');
            $the_input .= zen_draw_hidden_field('tag_mapping_type', $tag_mapping_type, 'id="tag_mapping_type"');
            $tag_cloud = '<br><div id="zen-tag-cloud-outer"><div id="zen-tag-cloud"><a href="#" class="choose">' . ZEN_TAG_TEXT_SHOW_MOST_USED . '</a></div></div>';
            
            $tag_inputs = [
                'label' => [
                    'text' => ZEN_TAG_LABEL_TAGS,
                    'field_name' => 'zen_tags',
                ],
                'input' => $the_input . '<br><div id="zen-tags-remove-outer"><span id="zen-tags-remove_text">' . ZEN_TAG_TEXT_CLICK_TO_REMOVE . '</span><div id="zen-tags-remove">' . $zen_tags . '</div></div>' . $tag_cloud . '<script src="includes/javascript/ajax_tag_list.js"></script>',
            ];
        }
        return $tag_inputs;
    }

    protected function generateTagPlaceholder($message)
    {
        $tag_inputs = false;
        if (IS_ADMIN_FLAG === true) {
            $tag_inputs = [
                'label' => [
                    'text' => ZEN_TAG_LABEL_TAGS,
                    'field_name' => 'zen_tags',
                ],
                'input' => $message . zen_draw_hidden_field('zen_tags', ''),
            ];
        }
        return $tag_inputs;
    }

    protected function generateTagsListProtected($tag_mapping_id, $tag_mapping_table): string
    {
        $zen_tags_list = '';
        $current_tags = $GLOBALS['db']->Execute(
            "SELECT t.tag_id, t.tag_name
               FROM " . TABLE_TAGS . " t, $tag_mapping_table tm
              WHERE tm.tag_mapping_id = $tag_mapping_id
                AND tm.tag_id = t.tag_id
           ORDER BY t.tag_name ASC"
        );
        foreach ($current_tags as $next_tag) {
            $zen_tags_list .= (' <span class="tag-list"><a id="tag_id[' . $next_tag['tag_id'] . ']" title="' . ZEN_TAG_TEXT_CLICK_TO_REMOVE_TITLE . '">' . $next_tag['tag_name']) . '</a></span>';
        }
        return $zen_tags_list;
    }

    protected function sanitizeTagMappingType($tag_mapping_type)
    {
        if ($tag_mapping_type == self::TAG_MAP_PRODUCT) {
            $tag_mapping_table = TABLE_TAGS_TO_PRODUCTS;
        } elseif ($tag_mapping_type == self::TAG_MAP_CATEGORY) {
            $tag_mapping_table = TABLE_TAGS_TO_CATEGORIES;
        } elseif ($tag_mapping_type == self::TAG_MAP_NEWS && defined('TABLE_TAGS_TO_NEWS')) {
            $tag_mapping_table = TABLE_TAGS_TO_NEWS;
        } else {
            trigger_error("FATAL Error: Unknown tag-mapping-type ($tag_mapping_type).", E_USER_WARNING);
            zen_exit();
        }
        return $tag_mapping_table;
    }

    public function generateTagsArray($tag_mapping_id, $tag_mapping_type)
    {
        $tag_mapping_table = $this->sanitizeTagMappingType($tag_mapping_type);

        $zen_tags_array = [];
        $current_tags = $GLOBALS['db']->Execute(
            "SELECT t.tag_id, t.tag_name
               FROM " . TABLE_TAGS . " t, $tag_mapping_table tm
              WHERE tm.tag_mapping_id = $tag_mapping_id
                AND tm.tag_id = t.tag_id
           ORDER BY t.tag_name ASC"
        );
        foreach ($current_tags as $next_tag) {
            $zen_tags_array[] = $next_tag;
        }
        return $zen_tags_array;
    }

    // -----
    // Called when an item's tags are to be updated.  The 'zen_tags' POST variable is set up in this 
    // class' generateTagInputsProtected function (above).
    //
    protected function updateTagInputsProtected($tag_mapping_id, $tag_mapping_table, $data_override = false): void
    {
        if (IS_ADMIN_FLAG === true) {
            if ($data_override === false) {
                $zen_tags = $_POST['tag_list'] ?? false;
            } else {
                $zen_tags = $data_override;
            }
            if ($zen_tags !== false) {
                $sub_cats_and_products = false;
                if ($tag_mapping_table === TABLE_TAGS_TO_CATEGORIES) {
                    $sub_cats_and_products = $this->getSubCatsAndProducts((int)$tag_mapping_id);
                }
                $new_tags = explode(',', str_replace('  ', ' ', $zen_tags));
                foreach ($new_tags as $current_tag) {
                    $tag_id = $this->tagId($current_tag);
                    $GLOBALS['db']->Execute(
                        "INSERT IGNORE INTO $tag_mapping_table
                            (tag_mapping_id, tag_id)
                         VALUES
                            ($tag_mapping_id, $tag_id)"
                    );
                    if (is_array($sub_cats_and_products)) {
                        if (is_array($sub_cats_and_products['sub_cats'])) {
                            foreach ($sub_cats_and_products['sub_cats'] as $sub_cat_id) {
                                $GLOBALS['db']->Execute(
                                    "INSERT IGNORE INTO " . TABLE_TAGS_TO_CATEGORIES . "
                                        (tag_mapping_id, tag_id)
                                     VALUES
                                        ($sub_cat_id, $tag_id)"
                                );
                            }
                        }
                        if (is_array($sub_cats_and_products['products'])) {
                            foreach ($sub_cats_and_products['products'] as $product_id) {
                                $GLOBALS['db']->Execute(
                                    "INSERT IGNORE INTO " . TABLE_TAGS_TO_PRODUCTS . "
                                        (tag_mapping_id, tag_id)
                                     VALUES
                                        ($product_id, $tag_id)"
                                );
                            }
                        }
                    }
                }
            }
        }
    }

    protected function getSubCatsAndProducts($categories_id): array
    {
        global $categories_products_id_list;  //-For zen_get_categories_products_list

        $categories_products_id_list = [];
        $products_list = zen_get_categories_products_list($categories_id, true, false);
        $products_in_categories = (is_array($products_list)) ? $products_list : [];
        unset($products_list);

        $sub_cats = [];
        $sub_cats = zen_get_categories($sub_cats, $categories_id);
        $sub_categories = [];
        foreach ($sub_cats as $next_cat) {
            $sub_categories[] = $next_cat['id'];
            $categories_products_id_list = [];
            $products_list = zen_get_categories_products_list($next_cat['id'], true, false);
            if (is_array($products_list)) {
                $products_in_categories = array_merge($products_in_categories, $products_list);
            }
        }
        unset($sub_cats);

        return [
            'sub_cats' => $sub_categories,
            'products' => $products_in_categories,
        ];
    }

    // -----
    // Returns the tag list for the specified product.
    //
    public function getProductTagList($products_id, $separator = ',')
    {
        return $this->getTagListProtected((int)$products_id, TABLE_TAGS_TO_PRODUCTS, (string)$separator);
    }

    // -----
    // Returns the tag list for the specified category.
    //
    public function getCategoryTagList($categories_id, $separator = ',')
    {
        return $this->getTagListProtected((int)$categories_id, TABLE_TAGS_TO_CATEGORIES, (string)$separator);
    }

    // -----
    // Common function that retrieves the specified tag list.
    //
    protected function getTagListProtected($tag_mapping_id, $tag_mapping_table, $separator)
    {
        $tags = [];
        $current_tags = $GLOBALS['db']->Execute(
            "SELECT t.tag_id, t.tag_name
               FROM " . TABLE_TAGS . " t, $tag_mapping_table tm
              WHERE tm.tag_mapping_id = $tag_mapping_id
                AND tm.tag_id = t.tag_id
           ORDER BY t.tag_name ASC"
        );
        foreach ($current_tags as $next_tag) {
            $tags[] = $next_tag['tag_name'];
        }
        return implode($separator, $tags);
    }

    public function removeProductTagsKeepUnused($products_id)
    {
        $this->removeProductTagsProtected($products_id, false);
    }
    public function removeProductTags($products_id)
    {
        $this->removeProductTagsProtected($products_id, true);
    }
    protected function removeProductTagsProtected($products_id, $remove_unused = true)
    {
       $products_id = (int)$products_id;
        $GLOBALS['db']->Execute(
            "DELETE FROM " . TABLE_TAGS_TO_PRODUCTS . "
              WHERE tag_mapping_id = $products_id"
        );
        if ($remove_unused === true) {
            $this->removeUnusedTagsProtected();
        }
    }

    public function removeCategoryTags($categories_id)
    {
        $categories_id = (int)$categories_id;
        $GLOBALS['db']->Execute(
            "DELETE FROM " . TABLE_TAGS_TO_CATEGORIES . "
              WHERE tag_mapping_id = $categories_id"
        );
        $this->removeUnusedTagsProtected();
    }

    public function removeTagByType($tag_mapping_id, $tag_id, $tag_mapping_type)
    {
        $tag_mapping_table = $this->sanitizeTagMappingType($tag_mapping_type);
        $tag_mapping_id = (int)$tag_mapping_id;
        $tag_id = (int)$tag_id;
        $GLOBALS['db']->Execute(
            "DELETE FROM $tag_mapping_table
              WHERE tag_mapping_id = $tag_mapping_id
                AND tag_id = $tag_id"
        );
        if ($tag_mapping_type == self::TAG_MAP_CATEGORY) {
            $sub_cats_and_products = $this->getSubCatsAndProducts((int)$tag_mapping_id);
            if (is_array($sub_cats_and_products)) {
                if (is_array($sub_cats_and_products['sub_cats']) && count($sub_cats_and_products['sub_cats']) != 0) {
                    $sub_cat_list = implode(',', $sub_cats_and_products['sub_cats']);
                    $GLOBALS['db']->Execute(
                        "DELETE FROM " . TABLE_TAGS_TO_CATEGORIES . "
                          WHERE tag_mapping_id IN ($sub_cat_list)
                            AND tag_id = $tag_id"
                    );
                    unset($sub_cat_list);
                }
                if (is_array($sub_cats_and_products['products']) && count($sub_cats_and_products['products']) !== 0) {
                    $product_list = implode(',', $sub_cats_and_products['products']);
                    $GLOBALS['db']->Execute(
                        "DELETE FROM " . TABLE_TAGS_TO_PRODUCTS . "
                          WHERE tag_mapping_id IN ($product_list)
                            AND tag_id = $tag_id"
                    );
                }
            }
        }
        $this->removeUnusedTagsProtected();
    }

    // -----
    // Conditionally remove any tags that currently have a usage count of 0.
    //
    public function removeUnusedTagNames()
    {
        $this->removeUnusedTagsProtected(true);
    }
    protected function removeUnusedTagsProtected($override = false, $force = false) 
    {
        if (($override !== false || IS_ADMIN_FLAG === true) && ($force !== false || $this->removeUnusedTags)) {
            $GLOBALS['db']->Execute(
                "DELETE FROM " . TABLE_TAGS . "
                  WHERE tag_id NOT IN (SELECT " . TABLE_TAGS_TO_PRODUCTS . ".tag_id FROM " . TABLE_TAGS_TO_PRODUCTS . ")
                    AND tag_id NOT IN (SELECT " . TABLE_TAGS_TO_CATEGORIES . ".tag_id FROM " . TABLE_TAGS_TO_CATEGORIES . ")"
             );
        }
    }

    public function addTagMapping($tag_mapping_id, $tag_id, $tag_mapping_type)
    {
        $tag_mapping_table = $this->sanitizeTagMappingType($tag_mapping_type);
        $tag_mapping_id = (int)$tag_mapping_id;
        $tag_id = (int)$tag_id;
        $GLOBALS['db']->Execute(
            "INSERT IGNORE INTO $tag_mapping_table
                (tag_id, tag_mapping_id)
             VALUES
                ($tag_id, $tag_mapping_id)"
        );
    }

    // -----
    // A 'valid' tag-name contains only alphanumeric characters.
    //
    public function validateTagName($tag_name): bool
    {
        return ctype_alnum((string)$tag_name);
    }

    // -----
    // Return the tag_id associated with the specified "Tag Name", optionally creating the entry.
    //
    public function tagIdCheck($tag_name)
    {
        return $this->tagId($tag_name, false);
    }
    public function tagIdCreate($tag_name)
    {
        return $this->tagId($tag_name, true);
    }
    protected function tagId($tag_name, $create = true)
    {
        $tag_name = zen_db_input($tag_name);
        $tag_info = $GLOBALS['db']->Execute(
            "SELECT tag_id
               FROM " . TABLE_TAGS . "
              WHERE tag_name = '$tag_name'
              LIMIT 1"
        );
        if (!$tag_info->EOF) {
            $tag_id = $tag_info->fields['tag_id'];
        } elseif ($create !== true || IS_ADMIN_FLAG !== true) {
            $tag_id = 0;
        } else {
            $tag_name = trim($tag_name);
            $GLOBALS['db']->Execute(
                "INSERT INTO " . TABLE_TAGS . "
                    (languages_id, tag_name)
                 VALUES
                    (1, '$tag_name')"
                );
            $tag_id = $GLOBALS['db']->Insert_ID();
        }
        return $tag_id;
    }

    // -----
    // Returns the usage count for the specified "Tag Name".
    //
    public function tagCountFromName($tag_name)
    {
        return $this->tagCountFromId($this->tagId($tag_name));
    }

    // -----
    // Returns the usage count for the specified "Tag ID".
    //
    public function tagCountFromId($tag_id)
    {
        $tag_id = (int)$tag_id;
        $usage_info = $GLOBALS['db']->Execute(
            "SELECT count(*) AS count
               FROM " . TABLE_TAGS_TO_PRODUCTS . "
              WHERE tag_id = $tag_id"
        );
        $tag_count = $usage_info->fields['count'];
        unset($usage_info);

        $usage_info = $GLOBALS['db']->Execute(
            "SELECT tag_mapping_id
               FROM " . TABLE_TAGS_TO_CATEGORIES . "
              WHERE tag_id = $tag_id"
        );
        $tag_count += $usage_info->fields['count'];
        return $tag_count;
    }

    // -----
    // Returns the name of the specified tag (or '' if not found).
    //
    public function getTagName($tag_id)
    {
        $tag_id = (int)$tag_id;
        $tag_name_info = $GLOBALS['db']->Execute(
            "SELECT tag_name
               FROM " . TABLE_TAGS . "
              WHERE tag_id = $tag_id
                AND languages_id = 1
              LIMIT 1"
        );
        return ($tag_name_info->EOF) ? '' : $tag_name_info->fields['tag_name'];
    }

    // -----
    // Returns the "Zen Cart Tag Cloud" HTML block.
    //
    public function makeTagCloud()
    {
        $tag_array = $this->makeTagCloudArray();
        $tag_cloud = '';
        if (count($tag_array) !== 0) {
            $tag_cloud = '<div class="zenTagCloud">';
            foreach ($tag_array as $current_tag) {
                $tag_cloud .= $this->makeTagLink($current_tag['tag_id'], $current_tag['tag_name'], 'zenTag', 'style="font-size: ' . $current_tag['font_size'] . ';"') . ' ';
            }
            $tag_cloud .= '</div>';
        }
        return $tag_cloud;
    }

    public function getProductTagLinks(int $products_id): string
    {
        $tag_array = $this->makeTagCloudArray($products_id);
        $tag_links = [];
        foreach ($tag_array as $next_tag) {
            $tag_links[] = $this->makeTagLink($next_tag['tag_id'], $next_tag['tag_name'], 'zenProductTag');
        }
        return implode(' ', $tag_links);
    }

    // -----
    // Creates a link to search for a given tag.
    //
    protected function makeTagLink(string $tag_id, string $tag_name, string $tag_class, string $parameters = ''): string
    {
        return
            '<span class="' . $tag_class . '">' .
                '<a ' . $parameters . ' href="' . zen_href_link(FILENAME_ADVANCED_SEARCH_RESULT, 'tID=' . $tag_id . '&keyword=' . $tag_name) . '">' .
                    $tag_name .
                '</a>' .
            '</span>';
    }

    // -----
    // Returns the "Zen Cart Tag Cloud" information in an associative array.
    //
    public function makeTagCloudArray(int $products_id = 0): array
    {
        $tags = [];
        $minimum_usage = 0;
        $maximum_usage = 0;
        
        $where_clause = ($products_id === 0) ? '' : " WHERE t2p.tag_mapping_id = $products_id";

        $tag_info = $GLOBALS['db']->Execute(
            "SELECT t.tag_id, t.tag_name, COUNT(t2p.tag_id) as p_count, COUNT(t2c.tag_id) as c_count
               FROM " . TABLE_TAGS . " t
                    LEFT JOIN " . TABLE_TAGS_TO_PRODUCTS . " t2p
                        ON t2p.tag_id = t.tag_id
                    LEFT JOIN " . TABLE_TAGS_TO_CATEGORIES . " t2c
                        ON t2c.tag_id = t.tag_id
               $where_clause
           GROUP BY t.tag_id, t.tag_name"
        );

        foreach ($tag_info as $next_tag) {
            $next_tag['count'] = $next_tag['p_count'] + $next_tag['c_count'];

            if ($minimum_usage == 0 || $next_tag['count'] < $minimum_usage) {
                $minimum_usage = $next_tag['count'];
            }
            if ($maximum_usage == 0 || $next_tag['count'] > $maximum_usage) {
                $maximum_usage = $next_tag['count'];
            }
            $tags[] = $next_tag;
        }
        unset($tag_info);

        usort($tags, [$this, 'sortTagCloud']);
        $this->tags = $tags;

        $tag_cloud_array = [];
        if (count($tags) !== 0) {
            if ($maximum_usage == 0) {
                $maximum_usage = 1;
            }
            $font_increment = ($this->largestFont - $this->smallestFont) / $maximum_usage;
            $this->font_increment = $font_increment;
            foreach ($tags as $current_tag) {
                $font_size = ($this->smallestFont + ($current_tag['count'] * $font_increment)) . $this->fontUnits;
                $tag_cloud_array[] = [
                    'tag_id' => $current_tag['tag_id'],
                    'tag_name' => $current_tag['tag_name'],
                    'count' => $current_tag['count'],
                    'font_size' => $font_size,
                ];
                if (count($tag_cloud_array) >= $this->maxTags) {
                    break;
                }
            }
        }
        return $tag_cloud_array;
    }

    protected function sortTagCloud($a, $b)
    {
        $sort_field = ($this->tagsOrderBy === 'name') ? 'tag_name' : 'count';
        if ($a[$sort_field] === $b[$sort_field]) {
            return 0;
        }
        return ($a[$sort_field] < $b[$sort_field]) ? (($this->tagsSortOrder === 'ASC') ? -1 : 1) : (($this->tagsSortOrder === 'ASC') ? 1 : -1);
    }

    public function getProductIdList($tag_id)
    {
        $id_array = [];
        $tag_id = (int)$tag_id;
        $product_info = $GLOBALS['db']->Execute(
            "SELECT tag_mapping_id, tag_mapping_type
               FROM " . TABLE_TAGS_TO_PRODUCTS . "
              WHERE tag_id = $tag_id"
        );
        foreach ($product_info as $next_product) {
            if ($next_product['tag_mapping_type'] == self::TAG_MAP_PRODUCT) {
                $id_array[] = $next_product['tag_mapping_id'];
            
            } elseif ($next_product['tag_mapping_type'] == self::TAG_MAP_CATEGORY) {
                $categories_products_id_list = [];
                $categories_products_id_list = zen_get_categories_products_list($next_product['tag_mapping_id']);
                foreach ($categories_products_id_list as $key => $value) {
                    $id_array[] = $key;
                }
            }
        }
        if (count($id_array) === 0) {
            $id_array[] = 0;
        } else {
            $id_array = array_unique($id_array);
        }
        return implode(',', $id_array);
    }
}
