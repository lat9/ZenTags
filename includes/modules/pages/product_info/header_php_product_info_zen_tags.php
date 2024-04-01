<?php
// -----
// Part of the "Zen Tags" plugin by Cindy Merkin (lat9)
// Copyright (c) 2024 Vinos de Frutas Tropicales
//
if (!defined('ZEN_TAGS_ENABLE') || ZEN_TAGS_ENABLE !== 'true') {
    return;
}

// -----
// See if the current product has any tags defined.
//
$product_tags = new ZenTags();
$product_tags_list = $product_tags->getProductTagLinks((int)$_GET['products_id']);
