<?php
// -----
// Part of the "Zen Tags" plugin for Zen Cart v1.5.6 (and later)
// Copyright (C) 2018-2019, Vinos de Frutas Tropicales (lat9)
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
//
class zcObserverZenTagsSearch extends base 
{
    public function __construct() 
    {
        if (defined('ZEN_TAGS_ENABLE') && ZEN_TAGS_ENABLE == 'true') {
            $this->tID = (isset($_GET['tID']) && ((int)$_GET['tID']) > 0) ? ((int)$_GET['tID']) : false;
            $this->order_by = '';
            if ($this->tID !== false || (defined('ZEN_TAGS_SEARCH_ALWAYS') && ZEN_TAGS_SEARCH_ALWAYS == 'true')) {
                $this->attach(
                    $this, 
                    array(
                        'NOTIFY_SEARCH_ORDERBY_STRING',
                        'NOTIFY_SEARCH_FROM_STRING',
                        'NOTIFY_SEARCH_WHERE_STRING',
                        'NOTIFY_SEARCH_SELECT_STRING'
                    )
                );
            }
        }
    }

    public function update(&$class, $eventID) 
    {
        switch ($eventID) {
            case 'NOTIFY_SEARCH_SELECT_STRING':
                if (!empty($GLOBALS['keywords']) && !empty($_GET['keyword']) && zen_parse_search_string(stripslashes($_GET['keyword']), $search_keywords)) {
                    $in_tag_select = '';
                    foreach ($search_keywords as $current_keyword) {
                        switch ($current_keyword) {
                            case '(':
                            case ')':
                            case 'and':
                            case 'or':      //- Fall-through from above ...
                                $in_tag_select .= " $current_keyword ";
                                break;

                            default:
                                $in_tag_select .= "t.tag_name LIKE '%:keywords%'";
                                $in_tag_select = $GLOBALS['db']->bindVars($in_tag_select, ':keywords', $current_keyword, 'noquotestring');
                                break;
                        }
                    }
                    $GLOBALS['select_str'] .= ", IF (t2p.tag_mapping_id = p.products_id and t2p.tag_id = t.tag_id AND ($in_tag_select), 1, 0) AS in_tag ";
                    $this->order_by = ' in_tag DESC,';
                }
                break;
                
            case 'NOTIFY_SEARCH_FROM_STRING':
                $GLOBALS['from_str'] = $this->insertAfter($GLOBALS['from_str'], ' c,', ' ' . TABLE_TAGS . ' t, ');
                $GLOBALS['from_str'] .= PHP_EOL . "LEFT JOIN " . TABLE_TAGS_TO_PRODUCTS . ' t2p ON t2p.tag_mapping_id = p.products_id ';
                break;
                
            case 'NOTIFY_SEARCH_WHERE_STRING':
                global $keywords, $where_str;
                if ($this->tID !== false) {
                    $p2c_str = 'p2c.categories_id = c.categories_id';
                    $p2c_pos = strpos($where_str, $p2c_str);
                    $GLOBALS['where_str'] = substr($GLOBALS['where_str'], 0, $p2c_pos + strlen($p2c_str)) . " AND t2p.tag_id = {$this->tID} )";
                } else {
                    if (!empty($GLOBALS['keywords']) && isset($_GET['keyword']) && zen_parse_search_string(stripslashes($_GET['keyword']), $search_keywords)) {
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
                        $GLOBALS['where_str'] = str_replace('))', $tags_where . ') ))', $GLOBALS['where_str']);
                    }
                } 
                break;
                
            case 'NOTIFY_SEARCH_ORDERBY_STRING':
                if ($this->tID !== false) {
                    $GLOBALS['keywords'] = "tag ({$GLOBALS['keywords']})";
                }
                $GLOBALS['listing_sql'] = str_ireplace('order by', 'order by' . $this->order_by, $GLOBALS['listing_sql']);
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
