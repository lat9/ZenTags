<?php
// -----
// Part of the "Zen Tags" plugin for Zen Cart v1.5.5
// Copyright (C) 2018, Vinos de Frutas Tropicales (lat9)
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
//
class zcObserverZenTagsSearch extends base 
{
    public function __construct() 
    {
        if (defined('ZEN_TAGS_ENABLE') && ZEN_TAGS_ENABLE == 'true') {
            $this->tID = (isset($_GET['tID']) && ((int)$_GET['tID']) > 0) ? ((int)$_GET['tID']) : false;
            $this->attach(
                $this, 
                array(
                    'NOTIFY_SEARCH_ORDERBY_STRING',
                    'NOTIFY_SEARCH_FROM_STRING',
                    'NOTIFY_SEARCH_WHERE_STRING'
                )
            );
        }
    }

    public function update (&$class, $eventID) 
    {
        switch ($eventID) {
            case 'NOTIFY_SEARCH_FROM_STRING':
                global $from_str;
                $from_str = $this->insertAfter($from_str, ' c,', ' ' . TABLE_TAGS . ' t, ');
                $from_str .= PHP_EOL . "LEFT JOIN " . TABLE_TAGS_TO_PRODUCTS . ' t2p ON t2p.tag_mapping_id = p.products_id ';
                break;
                
            case 'NOTIFY_SEARCH_WHERE_STRING':
                global $keywords, $where_str;
                if ($this->tID !== false) {
                    $p2c_str = 'p2c.categories_id = c.categories_id';
                    $p2c_pos = strpos($where_str, $p2c_str);
                    $where_str = substr($where_str, 0, $p2c_pos + strlen($p2c_str)) . " AND t2p.tag_id = {$this->tID} )";
                } else {
                    if (!empty($keywords) && zen_parse_search_string(stripslashes($_GET['keyword']), $search_keywords)) {
                        $tags_where = 'OR ( t.tag_id = t2p.tag_id AND ';
                        foreach ($search_keywords as $current_keyword) {
                            switch ($current_keyword) {
                                case '(':
                                case ')':
                                case 'and':
                                case 'or':
                                    $tags_where .= " $current_keyword ";
                                    break;
                    
                                default:
                                    $tags_where .= "t.tag_name LIKE '%:keywords%'";
                                    $tags_where = $GLOBALS['db']->bindVars($tags_where, ':keywords', $current_keyword, 'noquotestring');
                                    break;
                            }
                        }
                        $where_str = str_replace('))', $tags_where . ') ))', $where_str);
                    }
                } 
                break;
                
            case 'NOTIFY_SEARCH_ORDERBY_STRING':
                global $keywords;
                if ($this->tID !== false) {
                    $keywords = "tag ($keywords)";
                }
                break; 

            default:
                break;
        }
    }
    
    protected function insertAfter($the_string, $insert_after, $insert_string)
    {
        $insert_pos = strpos($the_string, $insert_after);
        if ($insert_pos === false) {
            trigger_error("Missing 'anchor' string ($insert_after) from 'base' string ($the_string); the search does not include any tags.", E_USER_WARNING);
        } else {
            $the_string = substr_replace($the_string, $insert_string, $insert_pos + strlen($insert_after), 0);
        }
        return $the_string;
    }
}