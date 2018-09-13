<?php
// -----
// Part of the "Zen Tags" plugin by Cindy Merkin (lat9)
// Copyright (c) 2014-2018 Vinos de Frutas Tropicales
//
if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

$autoLoadConfig[200][] = array( 
    'autoType' => 'class', 
    'loadFile' => 'ZenTags.php' 
);