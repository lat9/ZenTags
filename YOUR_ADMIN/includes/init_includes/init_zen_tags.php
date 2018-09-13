<?php
// -----
// Part of the "Zen Tags" plugin by lat9 (Cindy Merkin)
// Copyright (c) 2014-2018 Vinos de Frutas Tropicales
//
if (!defined('IS_ADMIN_FLAG') || IS_ADMIN_FLAG !== true) {
    die ('Illegal Access');
}

// -----
// Quick return if no admin is currently logged in.
//
if (empty($_SESSION['admin_id'])) {
    return;
}

define ('ZEN_TAGS_CURRENT_VERSION', '1.0.0-beta1');
define ('ZEN_TAGS_LAST_UPDATE_DATE', '2018-09-13');

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
    $cgi = $db->Insert_ID(); 
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
if (!defined('ZEN_TAGS_VERSION')) {
    $db->Execute(
        "INSERT INTO " . TABLE_CONFIGURATION . " 
            (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function) 
         VALUES 
            ('Zen Cart &quot;Tags&quot; Installed Version', 'ZEN_TAGS_VERSION', '" . ZEN_TAGS_CURRENT_VERSION . "', 'Displays the Zen Tag plugin\'s current version.', $cgi, 1, NULL, 'trim(' )"
     );
    $db->Execute(
        "INSERT INTO " . TABLE_CONFIGURATION . " 
            (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function) 
         VALUES 
            ('Enable on Storefront?', 'ZEN_TAGS_ENABLE', 'false', 'Identifies whether (<em>true</em>) or not (<em>false</em>, the default, <em>Zen Tags</em> is enabled on your storefront.', $cgi, 5, NULL , 'zen_cfg_select_option(array(\'true\', \'false\'),')"
    );   
    $db->Execute(
        "INSERT INTO " . TABLE_CONFIGURATION . " 
            (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function ) 
         VALUES 
            ('Tag Cloud &mdash; Text Size (Smallest)', 'ZEN_TAGS_CLOUD_SMALLEST', '8', 'What text size displays the tag with the smallest usage (the units are given by the <em>Tag Cloud &mdash; Unit</em> setting.', $cgi, 10, NULL, NULL )"
    );
    $db->Execute(
        "INSERT INTO " . TABLE_CONFIGURATION . " 
            (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function ) 
         VALUES 
            ('Tag Cloud &mdash; Text Size (Largest)', 'ZEN_TAGS_CLOUD_LARGEST', '22', 'What text size displays the tag with the largest usage (the units are given by the <em>Tag Cloud &mdash; Unit</em> setting.', $cgi, 12, NULL, NULL )"); 
    $db->Execute
        ("INSERT INTO " . TABLE_CONFIGURATION . " 
            (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function ) 
         VALUES 
            ('Tag Cloud &mdash; Units', 'ZEN_TAGS_CLOUD_UNITS', 'pt', 'In what units (e.g. pt, px, em) are tags in the &quot;Tag Cloud&quot;  to be displayed?', $cgi, 14, NULL, NULL )");
    $db->Execute(
        "INSERT INTO " . TABLE_CONFIGURATION . " 
            (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function ) 
         VALUES 
            ('Tag Cloud &mdash; Maximum Tags', 'ZEN_TAGS_CLOUD_MAX', '45', 'What is the maximum number of <em>tags</em> to be displayed within the &quot;Tag Cloud&quot;?', $cgi, 16, NULL, NULL )"
     );
    $db->Execute(
        "INSERT INTO " . TABLE_CONFIGURATION . " 
            (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function ) 
         VALUES 
            ('Tag Cloud &mdash; Order By', 'ZEN_TAGS_ORDER_BY', 'name', 'Identify which value influences the order of the tag names display in the &quot;Tag Cloud&quot;.', $cgi, 18, NULL, 'zen_cfg_select_option(array(\'name\', \'count\'),')"
    );
    $db->Execute(
        "INSERT INTO " . TABLE_CONFIGURATION . " 
            (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function ) 
         VALUES 
            ('Tag Cloud &mdash; Sort Order', 'ZEN_TAGS_SORT_ORDER', 'ASC', 'Identifies the sort order to be used when displaying the tag names in the &quot;Tag Cloud&quot;.', $cgi, 20, NULL , 'zen_cfg_select_option(array(\'ASC\', \'DESC\'),')"
    );
    $db->Execute(
        "INSERT INTO " . TABLE_CONFIGURATION . " 
            (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function ) 
         VALUES 
            ('Remove Unused Tags?', 'ZEN_TAGS_REMOVE_IF_NOT_USED', 'true', 'If a tag name is no longer referenced by any mapping (e.g. not used by any product or category), should the tag be removed from the database?', $cgi, 30, NULL , 'zen_cfg_select_option(array(\'true\', \'false\'),')"
    );
  
    // -----
    // Create the tag-related tables, if they don't already exist.
    //
    $db->Execute(
        "CREATE TABLE IF NOT EXISTS " . TABLE_TAGS . " (
            tag_id int(11) NOT NULL auto_increment,
            languages_id int(11) NOT NULL default '0',
            tag_name varchar(255) NOT NULL default '',
            PRIMARY KEY (tag_id)
        )"
    );

    $db->Execute(
        "CREATE TABLE IF NOT EXISTS " . TABLE_TAGS_TO_PRODUCTS . " (
            tag_mapping_id int(11) NOT NULL default 0,
            tag_id int(11) NOT NULL default 0,  
            PRIMARY KEY (tag_mapping_id,tag_id)
         ) COMMENT = 'The tag_mapping_id contains a products_id'"
    );
    $db->Execute(
        "CREATE TABLE IF NOT EXISTS " . TABLE_TAGS_TO_CATEGORIES . " (
            tag_mapping_id int(11) NOT NULL default '0',
            tag_id int(11) NOT NULL default '0',  
            PRIMARY KEY (tag_mapping_id,tag_id)
         ) COMMENT = 'The tag_mapping_id contains a categories_id'"
    );

    //----
    // Register the "Zen Tags" configuration (assumes Zen Cart v1.5.0 or later).
    //
    if (!zen_page_key_exists('configZenTags')) {
        zen_register_admin_page('configZenTags', 'BOX_CONFIGURATION_ZEN_TAGS', 'FILENAME_CONFIGURATION', "gID=$cgi", 'configuration', 'Y', $cgi);
    } 

    define('ZEN_TAGS_VERSION', '0.0.0');
}

if (ZEN_TAGS_VERSION != ZEN_TAGS_CURRENT_VERSION) {
    $db->Execute(
        "UPDATE " . TABLE_CONFIGURATION . " 
            SET configuration_value = '" . ZEN_TAGS_CURRENT_VERSION . "' 
          WHERE configuration_key = 'ZEN_TAGS_VERSION' 
          LIMIT 1"
    );
}
