<?php
// -----
// Part of the "Zen Tags" plugin for Zen Cart v1.5.8a (and later)
// Copyright (C) 2018-2024, Vinos de Frutas Tropicales (lat9)
// @license https://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
//
class zcObserverZenTagsSearch extends base
{
    protected int $tID;

    public function __construct()
    {
        if (defined('ZEN_TAGS_ENABLE') && ZEN_TAGS_ENABLE === 'true') {
            $this->tID = (int)($_GET['tID'] ?? '0');
            $this->tID = ($this->tID > 0) ? $this->tID : 0;
            if ($this->tID !== 0 || (defined('ZEN_TAGS_SEARCH_ALWAYS') && ZEN_TAGS_SEARCH_ALWAYS === 'true')) {
                $this->attach(
                    $this,
                    [
                        'NOTIFY_SEARCH_SELECT_STRING',
                        'NOTIFY_AJAX_BOOTSTRAP_SEARCH_CLAUSES',
                        'NOTIFY_SEARCH_RESULTS',
                    ]
                );
            }
        }
    }

    protected function updateNotifySearchSelectString(&$class, $eventID, $select_str_in, &$select_str_out)
    {
        if (!empty($_GET['keyword'])) {
            // -----
            // Since a search keyword was specified, attach to the remaining search-query
            // clauses' generation to search for matching tags.
            //
            $this->attach(
                $this,
                [
                    'NOTIFY_SEARCH_FROM_STRING',
                    'NOTIFY_SEARCH_WHERE_STRING',
                ]
            );
        }
    }

    protected function updateNotifySearchFromString(&$class, $eventID, $from_str_in, &$from_str_out)
    {
        $from_str_out .= $this->updateFromClause();
    }

    protected function updateNotifySearchWhereString(&$class, $eventID, $search_keywords, &$where_str)
    {
        $where_str .= $this->updateWhereClause($_GET['keyword']);
    }

    protected function updateNotifyAjaxBootstrapSearchClauses(&$class, $eventID, $search_keywords, &$select_clause, &$from_clause, &$where_clause)
    {
        $from_clause .= $this->updateFromClause();
        $where_clause .= $this->updateWhereClause($_POST['keywords'], strpos($from_clause, 'p2c') !== false);
    }

    protected function updateFromClause(): string
    {
        return
            " LEFT JOIN " . TABLE_TAGS_TO_PRODUCTS . " t2p ON t2p.tag_mapping_id = p.products_id
              LEFT JOIN " . TABLE_TAGS . " t ON t.tag_id = t2p.tag_id ";
    }

    protected function updateWhereClause(string $keyword_string, bool $use_p2c = true): string
    {
        // -----
        // While the main search page includes the products_to_categories and categories
        // tables in its search, the Bootstrap Ajax search doesn't.
        //
        $p2c_clause = '';
        if ($use_p2c === true) {
            $p2c_clause = ' AND p.products_id = p2c.products_id AND p2c.categories_id = c.categories_id ';
        }

        $where_clause = ' OR
                (
                        p.products_status = 1
                    AND p.products_id = pd.products_id
                    AND pd.language_id = ' . (int)$_SESSION['languages_id'] .
                    $p2c_clause;

        if ($this->tID !== 0) {
            $where_clause .= " AND t2p.tag_id = {$this->tID} AND t2p.tag_mapping_id = p.products_id";
        } else {
            $where_clause .= ' AND t.tag_id = t2p.tag_id ' . zen_build_keyword_where_clause(['t.tag_name'], $keyword_string);
        }
        return $where_clause . ')';
    }

    protected function updateNotifySearchResults(&$class, $eventID, $listing_sql, &$keywords, &$results)
    {
        global $db;

        $search_tag = $db->Execute(
            "SELECT tag_name
               FROM " . TABLE_TAGS . "
              WHERE tag_id = {$this->tID}
              LIMIT 1"
        );
        if (!$search_tag->EOF) {
            $keywords = $search_tag->fields['tag_name'];
        }
    }
}
