<?php
// -----
// Part of the "Zen Tags" plugin by lat9 (Cindy Merkin)
// Copyright (c) 2014-2018 Vinos de Frutas Tropicales
//
if (!defined('IS_ADMIN_FLAG') || IS_ADMIN_FLAG !== true) {
    die ('Illegal Access');
}

define('BOX_CONFIGURATION_ZEN_TAGS', 'Product/Category Tags');
define('ZEN_TAG_LABEL_TAGS', 'Tags:');
define('ZEN_TAG_LABEL_NOTE', 'Note:');
define('ZEN_TAG_LABEL_CATEGORY_INSTRUCTIONS', 'Adding or removing a category tag will cause that tag to be added or removed from all sub-categories and included products!');
define('ZEN_TAG_HEADING_TAGS', 'Tags');
define('ZEN_TAG_TEXT_SEPARATE_TAGS', 'Separate tags with commas.  Tags are case-insensitive, so <em>THIS</em> is identical to <em>this</em>.');
define('ZEN_TAG_TEXT_SHOW_MOST_USED', 'Show the most-used tags');
define('ZEN_TAG_TEXT_HIDE_MOST_USED', 'Hide the most-used tags');
define('ZEN_TAG_TEXT_ADD_TAG', 'Click to add this tag');
define('ZEN_TAG_TEXT_CLICK_TO_REMOVE_TITLE', 'Click here to remove this tag');
define('ZEN_TAG_TEXT_CLICK_TO_REMOVE', 'Click a link below to remove the associated tag: ');