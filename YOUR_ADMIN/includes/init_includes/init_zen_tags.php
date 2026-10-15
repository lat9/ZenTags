<?php
// -----
// Part of the "Zen Tags" plugin by lat9 (Cindy Merkin)
// Copyright (c) 2014-2026 Vinos de Frutas Tropicales
//
if (!defined('IS_ADMIN_FLAG') || IS_ADMIN_FLAG !== true) {
    die ('Illegal Access');
}

define('ZEN_TAGS_CURRENT_VERSION', '2.1.0-beta2');
define('ZEN_TAGS_LAST_UPDATE_DATE', '2026-10-15');

// -----
// Quick return if no admin is currently logged in or the plugin's
// already at the current version.
//
if (empty($_SESSION['admin_id']) || zen_config('ZEN_TAGS_VERSION') === ZEN_TAGS_CURRENT_VERSION) {
    return;
}

// -----
// Create the configuration group and associated items to allow the configuration of the "Tag Cloud" sidebox.
//
$config_group_title = 'Configure Zen Tags';
$configuration = $db->Execute(
    "SELECT configuration_group_id 
       FROM " . TABLE_CONFIGURATION_GROUP . " 
      WHERE configuration_group_title = '$config_group_title'
      LIMIT 1"
);
if (!$configuration->EOF) {
    $cgi = $configuration->fields['configuration_group_id'];
} else {
    $db->Execute(
        "INSERT INTO " . TABLE_CONFIGURATION_GROUP . "
            (configuration_group_title, configuration_group_description, sort_order, visible)
         VALUES
            ('$config_group_title', 'Zen Cart Tags Settings', 1, 1)"
    );
    $cgi = (int)$db->Insert_ID(); 
    $db->Execute(
        "UPDATE " . TABLE_CONFIGURATION_GROUP . "
            SET sort_order = $cgi
          WHERE configuration_group_id = $cgi
          LIMIT 1"
    );
}

// -----
// Set the various configuration items, if not previously installed
//
$zen_tags_version = zen_config('ZEN_TAGS_VERSION');
if ($zen_tags_version === null) {
    $db->Execute(
        "INSERT INTO " . TABLE_CONFIGURATION . "
            (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function, set_function)
         VALUES
            ('Zen Cart &quot;Tags&quot; Installed Version', 'ZEN_TAGS_VERSION', '" . ZEN_TAGS_CURRENT_VERSION . "', 'Displays the Zen Tag plugin\'s current version.', $cgi, 1, now(), NULL, 'zen_cfg_read_only(' ),

            ('Enable on Storefront?', 'ZEN_TAGS_ENABLE', 'false', 'Identifies whether (<em>true</em>) or not (<em>false</em>, the default, <em>Zen Tags</em> is enabled on your storefront.', $cgi, 5, now(), NULL, 'zen_cfg_select_option([\'true\', \'false\'],'),

            ('Tag Cloud &mdash; Text Size (Smallest)', 'ZEN_TAGS_CLOUD_SMALLEST', '8', 'What text size displays the tag with the smallest usage (the units are given by the <em>Tag Cloud &mdash; Unit</em> setting.', $cgi, 10, now(), NULL, NULL),

            ('Tag Cloud &mdash; Text Size (Largest)', 'ZEN_TAGS_CLOUD_LARGEST', '22', 'What text size displays the tag with the largest usage (the units are given by the <em>Tag Cloud &mdash; Unit</em> setting.', $cgi, 12, now(), NULL, NULL),

            ('Tag Cloud &mdash; Units', 'ZEN_TAGS_CLOUD_UNITS', 'pt', 'In what units (e.g. pt, px, em) are tags in the &quot;Tag Cloud&quot;  to be displayed?', $cgi, 14, now(), NULL, NULL),

            ('Tag Cloud &mdash; Maximum Tags', 'ZEN_TAGS_CLOUD_MAX', '45', 'What is the maximum number of <em>tags</em> to be displayed within the &quot;Tag Cloud&quot;?', $cgi, 16, now(), NULL, NULL),

            ('Tag Cloud &mdash; Order By', 'ZEN_TAGS_ORDER_BY', 'name', 'Identify which value influences the order of the tag names display in the &quot;Tag Cloud&quot;.', $cgi, 18, now(), NULL, 'zen_cfg_select_option([\'name\', \'count\'],'),

            ('Tag Cloud &mdash; Sort Order', 'ZEN_TAGS_SORT_ORDER', 'ASC', 'Identifies the sort order to be used when displaying the tag names in the &quot;Tag Cloud&quot;.', $cgi, 20, now(), NULL, 'zen_cfg_select_option([\'ASC\', \'DESC\'],'),

            ('Remove Unused Tags?', 'ZEN_TAGS_REMOVE_IF_NOT_USED', 'true', 'If a tag name is no longer referenced by any mapping (e.g. not used by any product or category), should the tag be removed from the database?', $cgi, 30, now(), NULL, 'zen_cfg_select_option([\'true\', \'false\'],')"
    );

    // -----
    // Create the tag-related tables, if they don't already exist.
    //
    $db->Execute(
        "CREATE TABLE IF NOT EXISTS " . TABLE_TAGS . " (
            tag_id int(11) NOT NULL auto_increment,
            languages_id int(11) NOT NULL default 0,
            tag_name varchar(255) NOT NULL default '',
            PRIMARY KEY (tag_id)
        )"
    );

    $db->Execute(
        "CREATE TABLE IF NOT EXISTS " . TABLE_TAGS_TO_PRODUCTS . " (
            tag_mapping_id int(11) NOT NULL,
            tag_id int(11) NOT NULL,
            PRIMARY KEY (tag_mapping_id,tag_id)
         ) COMMENT = 'The tag_mapping_id contains a products_id'"
    );
    $db->Execute(
        "CREATE TABLE IF NOT EXISTS " . TABLE_TAGS_TO_CATEGORIES . " (
            tag_mapping_id int(11) NOT NULL,
            tag_id int(11) NOT NULL,
            PRIMARY KEY (tag_mapping_id,tag_id)
         ) COMMENT = 'The tag_mapping_id contains a categories_id'"
    );

    //----
    // Register the "Zen Tags" configuration.
    //
    if (!zen_page_key_exists('configZenTags')) {
        zen_register_admin_page('configZenTags', 'BOX_CONFIGURATION_ZEN_TAGS', 'FILENAME_CONFIGURATION', "gID=$cgi", 'configuration', 'Y', $cgi);
    } 
    $zen_tags_version = '0.0.0';
}

if ($zen_tags_version !== ZEN_TAGS_CURRENT_VERSION) {
    switch (true) {
        // -----
        // v1.0.0: Adds configuration setting to identify whether/not to **always** include matching tags in a product-search.
        //
        case version_compare($zen_tags_version, '1.0.0', '<'):
            $db->Execute(
                "INSERT IGNORE INTO " . TABLE_CONFIGURATION . "
                    (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function, set_function)
                 VALUES
                    ('Always Include Tags in Search?', 'ZEN_TAGS_SEARCH_ALWAYS', 'false', 'Identifies whether (<em>true</em>) or not (<em>false</em>, whether a storefront product-search <em>always</em> includes products with tags that match the search <code>keywords</code>.  This setting applies <em>only</em> if tags are enabled for use on your storefront.', $cgi, 6, now(), NULL, 'zen_cfg_select_option([\'true\', \'false\'],')"
            );
        case version_compare($zen_tags_version, '2.0.0', '<'):   //-Fall through from above to continue with updates
                $db->Execute(
                    "UPDATE " . TABLE_CONFIGURATION . "
                        SET set_function = 'zen_cfg_read_only(',
                            last_modified = now()
                      WHERE configuration_key = 'ZEN_TAGS_VERSION'
                      LIMIT 1"
                );
                                                               //-Fall through from above to continue with updates
        default:
            break;
    }

    // -----
    // Previous versions might have allowed either empty tag-names or untrimmed tag names to
    // be inserted into the 'tags' table.  Clean them up ...
    //
    $tags = $db->Execute(
        "SELECT *
           FROM " . TABLE_TAGS
    );
    foreach ($tags as $next_tag) {
        $trimmed_tag_name = zen_db_input(trim($next_tag['tag_name']));
        $tag_id = (int)$next_tag['tag_id'];
        $tag_language_id = (int)$next_tag['languages_id'];
        if ($trimmed_tag_name === '') {
            $db->Execute(
                "DELETE FROM " . TABLE_TAGS . "
                  WHERE tag_id = $tag_id
                    AND languages_id = $tag_language_id
                  LIMIT 1"
            );
            $db->Execute(
                "DELETE FROM " . TABLE_TAGS_TO_CATEGORIES . "
                  WHERE tag_id = $tag_id"
            );
            $db->Execute(
                "DELETE FROM " . TABLE_TAGS_TO_PRODUCTS . "
                  WHERE tag_id = $tag_id"
            );
            continue;
        }
        $db->Execute(
            "UPDATE " . TABLE_TAGS . "
                SET tag_name = '$trimmed_tag_name'
              WHERE tag_id = $tag_id
                AND languages_id = $tag_language_id
              LIMIT 1"
        );
    }

    $db->Execute(
        "UPDATE " . TABLE_CONFIGURATION . "
            SET configuration_value = '" . ZEN_TAGS_CURRENT_VERSION . "',
                last_modified = now()
          WHERE configuration_key = 'ZEN_TAGS_VERSION' 
          LIMIT 1"
    );

    if ($zen_tags_version === '0.0.0') {
        $messageStack->add_session(sprintf(ZEN_TAG_INSTALLED, ZEN_TAGS_CURRENT_VERSION), 'success');
    } else {
        $messageStack->add_session(sprintf(ZEN_TAG_UPDATED, ZEN_TAGS_CURRENT_VERSION), 'success');
    }
}
