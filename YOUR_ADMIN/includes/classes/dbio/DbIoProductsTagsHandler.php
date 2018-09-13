<?php
// -----
// Part of the ZenTags plugin, created by Cindy Merkin (cindy@vinosdefrutastropicales.com)
// Copyright (c) 2018, Vinos de Frutas Tropicales.
//
if (!defined ('IS_ADMIN_FLAG')) {
    exit ('Illegal access');
}

// -----
// This DbIo class handles the import and export of information in the ZenTags plugin's 'tags' and 'tags_to_products' tables.
//
// Each table-record is exported as a single CSV record; the product's 'products_id' and 'products_name' are exported, along with
// a ^-separated list of current tags for the product.
//
// For the import, the CSV **must** contain the 'products_id', since that value is used during import to determine
// whether an associated 'tags_to_products' record exists (update) or not (import).  If a product's associated 'products_tags'
// field is empty, **all** tags for the product will be removed.
//
class DbIoProductsTagsHandler extends DbIoHandler 
{
    public static function getHandlerInformation ()
    {
        if (!defined('TABLE_TAGS_TO_PRODUCTS') || !$GLOBALS['sniffer']->table_exists(TABLE_TAGS_TO_PRODUCTS)) {
            return false;
        }
        
        DbIoHandler::loadHandlerMessageFile('ProductsTags'); 
        return array (
            'version' => '1.4.0',
            'handler_version' => '1.4.0',
            'include_header' => true,
            'export_only' => false,
            'description' => DBIO_PRODUCTSTAGS_DESCRIPTION,
        );
    }

// ----------------------------------------------------------------------------------
//             I N T E R N A L / P R O T E C T E D   F U N C T I O N S 
// ----------------------------------------------------------------------------------
    
    // -----
    // This function, called during the overall class construction, is used to set this handler's database
    // configuration for the DbIO operations.
    //
    protected function setHandlerConfiguration() 
    {
        $this->stats['report_name'] = 'ProductsTags';
        $this->config = self::getHandlerInformation();
        $this->config['handler_does_import'] = true;  //-Indicate that **all** the import-based database manipulations are performed by this handler
        $this->config['keys'] = array(
            TABLE_PRODUCTS => array (
                'alias' => 'p',
                'products_id' => array (
                    'type' => (self::DBIO_KEY_IS_MASTER | self::DBIO_KEY_IS_VARIABLE),
                ),
            ),
        );
        $this->config['tables'] = array(
            TABLE_PRODUCTS => array(
                'alias' => 'p',
                'join_clause' => 
                    "INNER JOIN " . TABLE_PRODUCTS_DESCRIPTION . " AS pd
                        ON p.products_id = pd.products_id
                       AND pd.language_id = " . (int)$this->languages[DEFAULT_LANGUAGE]
            ),
            TABLE_PRODUCTS_DESCRIPTION => array(
                'alias' => 'pd',
                'no_from_clause' => true,
                'language_field' => 'language_id',
            ),
        );
        $this->config['fixed_headers'] = array (
            'products_id' => TABLE_PRODUCTS,
            'products_name' => TABLE_PRODUCTS_DESCRIPTION,
            'products_tags' => self::DBIO_SPECIAL_IMPORT,
        );
        $this->config['export_order_by_clause'] = 'pd.products_name ASC';
    }
    
    // -----
    // The 'products_tags' field for the export is handled 'specially'.  The input
    // array contains the current products_id, so we'll grab all the names for all the
    // currently-defined tags and provide them as a ^-separated field for the export.
    //
    public function exportPrepareFields(array $fields)
    {
        $tags = $GLOBALS['db']->Execute(
            "SELECT GROUP_CONCAT(tag_name SEPARATOR '^') AS products_tags
               FROM " . TABLE_TAGS . " t
                    INNER JOIN " . TABLE_TAGS_TO_PRODUCTS . " t2p
                        ON t2p.tag_id = t.tag_id
                       AND t2p.tag_mapping_id = " . $fields['products_id'] . "
           GROUP BY t2p.tag_mapping_id"
        );
        $fields['products_tags'] = ($tags->EOF) ? '' : $tags->fields['products_tags'];
        return parent::exportPrepareFields($fields);
    }
    
    // -----
    // Import initialization, create an empty array that will hold a cache of the tag-names
    // provided by the input CSV file, so that we don't have to keep hitting the database to
    // see if they exist.
    //
    public function importInitialize($language = 'all', $operation = 'check')
    {
        $this->tagCache = array();
        $this->zenTags = new ZenTags();
        
        return parent::importInitialize($language, $operation);
    }
    
    // -----
    // Some of the exported fields are for information only and aren't importable.
    //
    protected function importHeaderFieldCheck($field_name)
    {
        switch ($field_name) {
            case 'products_id':
                $import_status = self::DBIO_IMPORT_OK;
                break;
            case 'products_tags':
                $import_status = self::DBIO_SPECIAL_IMPORT;
                break;
            default:
                $import_status = self::DBIO_NO_IMPORT;
                break;
        }
        return $import_status;
    }
    
    // -----
    // Since this handler claims responsibility for the import process, make sure that each imported record's "key"
    // (the products_id) is set and a valid integer.
    //
    // The importCsvRecord processing in the base DbIoHandler class has already checked to see if the
    // products_id exists, setting $this->import_is_insert to (boolean)true if that product isn't found.  This
    // handler's processing requires a pre-existing products_id, so disallow the import if an invalid
    // products_id value was supplied.
    //
    protected function importCheckKeyValue($data)
    {
        $this->importProductsId = $this->importGetFieldValue('products_id', $data);
        $import_allowed = !$this->import_is_insert;
        if (!$import_allowed) {
            $this->debugMessage("Unknown products_id (" . $this->importProductsId . ") found.  The record at line #" . $this->stats['record_count'] . " was not imported.", self::DBIO_WARNING);
        }
        $this->record_status = $import_allowed;
        
        return $import_allowed; 
    }
  
    // -----
    // This function receives control on each importable field of each imported record.  We'll use
    // the opportunity to do some pre-checking of the to-be-imported product tag information.
    //
    // Note that the current 'products_id' has been saved in 'importProductsId'.
    //
    protected function importProcessField($table_name, $field_name, $language_id, $field_value)
    {
        $this->debugMessage("ProductsTags::importProcessField($table_name, $field_name, $language_id, $field_value)");
        if ($field_name == 'products_tags') {
            $this->productsTags = array();
            $tags = explode('^', $field_value);
            foreach ($tags as $current_tag) {
                if (!$this->zenTags->validateTagName($current_tag)) {
                    $this->debugMessage("[*] Invalid tag name ($current_tag) supplied on line #" . $this->stats['record_count'] . "; the record was not imported.", self::DBIO_ERROR);
                    $this->record_status = false;
                    break;
                }
                if (isset($this->tagCache[$current_tag])) {
                    $tag_id = $this->tagCache[$current_tag];
                } else {
                    $tag_id = $this->zenTags->tagIdCheck($current_tag);
                }
                $this->productsTags[$current_tag] = $tag_id;
            }
        }
    }
    
    // -----
    // Since this handler has set 'handler_does_import', this method is called by the base class to
    // 'finish' processing the current CSV import record, if no errors were found in the imported
    // record.
    //
    protected function importFinishProcessing()
    {
        $this->debugMessage("importFinishProcessing for products_id (" . $this->importProductsId . ": " . print_r($this->productTags, true));
        if ($this->operation != 'check') {
            if (count($this->productsTags) == 0) {
                $this->zenTags->removeProductTagsKeepUnused($this->importProductsId);
            } else {
                foreach ($this->productsTags as $tag_name => $tag_id) {
                    if ($tag_id == 0) {
                        $tag_id = $this->zenTags->tagIdCreate($tag_name);
                    }
                    $this->tagCache[$tag_name] = $tag_id;
                    $this->zenTags->addTagMapping($this->importProductsId, $tag_id, ZenTags::TAG_MAP_PRODUCT);
                }
            }
        }
    }
    
    public function importPostProcess()
    {
        $this->zenTags->removeUnusedTagNames();
    }

}  //-END class DbIoProductsTagsHandler
