<?php
/**
 * @package admin
 * @copyright Copyright 2003-2019 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2019 Jan 04 Modified in v1.5.6a $
 */
if (!defined('IS_ADMIN_FLAG')) {
  die('Illegal Access');
}
$parameters = array(
  'products_name' => '',
  'products_description' => '',
  'products_url' => '',
  'products_id' => '',
  'products_quantity' => '0',
  'products_model' => '',
  'products_image' => '',
  'products_price' => '0.0000',
  'products_virtual' => DEFAULT_PRODUCT_PRODUCTS_VIRTUAL,
  'products_weight' => '0',
  'products_date_added' => '',
  'products_last_modified' => '',
  'products_date_available' => '',
  'products_status' => '1',
  'products_tax_class_id' => DEFAULT_PRODUCT_TAX_CLASS_ID,
  'manufacturers_id' => '',
  'products_quantity_order_min' => '1',
  'products_quantity_order_units' => '1',
  'products_priced_by_attribute' => '0',
  'product_is_free' => '0',
  'product_is_call' => '0',
  'products_quantity_mixed' => '1',
  'product_is_always_free_shipping' => DEFAULT_PRODUCT_PRODUCTS_IS_ALWAYS_FREE_SHIPPING,
  'products_qty_box_status' => PRODUCTS_QTY_BOX_STATUS,
  'products_quantity_order_max' => '0',
  'products_sort_order' => '0',
  'products_discount_type' => '0',
  'products_discount_type_from' => '0',
  'products_price_sorter' => '0',
  'master_categories_id' => ''
);

$pInfo = new objectInfo($parameters);

if (isset($_GET['pID']) && empty($_POST)) {
  $product = $db->Execute("SELECT pd.products_name, pd.products_description, pd.products_url,
                                  p.products_id, p.products_quantity, p.products_model,
                                  p.products_image, p.products_price, p.products_virtual, p.products_weight,
                                  p.products_date_added, p.products_last_modified,
                                  date_format(p.products_date_available, '%Y-%m-%d') as
                                  products_date_available, p.products_status, p.products_tax_class_id,
                                  p.manufacturers_id,
                                  p.products_quantity_order_min, p.products_quantity_order_units, p.products_priced_by_attribute,
                                  p.product_is_free, p.product_is_call, p.products_quantity_mixed,
                                  p.product_is_always_free_shipping, p.products_qty_box_status, p.products_quantity_order_max,
                                  p.products_sort_order,
                                  p.products_discount_type, p.products_discount_type_from,
                                  p.products_price_sorter, p.master_categories_id
                           FROM " . TABLE_PRODUCTS . " p,
                                " . TABLE_PRODUCTS_DESCRIPTION . " pd
                           WHERE p.products_id = " . (int)$_GET['pID'] . "
                           AND p.products_id = pd.products_id
                           AND pd.language_id = " . (int)$_SESSION['languages_id']);

  $pInfo->updateObjectInfo($product->fields);
} elseif (zen_not_null($_POST)) {
  $pInfo->updateObjectInfo($_POST);
  $products_name = isset($_POST['products_name']) ? $_POST['products_name'] : '';
  $products_description = isset($_POST['products_description']) ? $_POST['products_description'] : '';
  $products_url = isset($_POST['products_url']) ? $_POST['products_url'] : '';
}

$category_lookup = $db->Execute("SELECT *
                                 FROM " . TABLE_CATEGORIES . " c,
                                      " . TABLE_CATEGORIES_DESCRIPTION . " cd
                                 WHERE c.categories_id = " . (int)$current_category_id . "
                                 AND c.categories_id = cd.categories_id
                                 AND cd.language_id = " . (int)$_SESSION['languages_id']);
if (!$category_lookup->EOF) {
  $cInfo = new objectInfo($category_lookup->fields);
} else {
  $cInfo = new objectInfo(array());
}

$manufacturers_array = array(array(
    'id' => '',
    'text' => TEXT_NONE));
$manufacturers = $db->Execute("SELECT manufacturers_id, manufacturers_name
                               FROM " . TABLE_MANUFACTURERS . "
                               ORDER BY manufacturers_name");
foreach ($manufacturers as $manufacturer) {
  $manufacturers_array[] = array(
    'id' => $manufacturer['manufacturers_id'],
    'text' => $manufacturer['manufacturers_name']
  );
}

$tax_class_array = array(array(
    'id' => '0',
    'text' => TEXT_NONE));
$tax_class = $db->Execute("SELECT tax_class_id, tax_class_title
                           FROM " . TABLE_TAX_CLASS . "
                           ORDER BY tax_class_title");
foreach ($tax_class as $item) {
  $tax_class_array[] = array(
    'id' => $item['tax_class_id'],
    'text' => $item['tax_class_title']);
}

$languages = zen_get_languages();

// set to out of stock if categories_status is off and new product or existing products_status is off
if (zen_get_categories_status($current_category_id) == 0 && $pInfo->products_status != 1) {
  $pInfo->products_status = 0;
}
?>
<script>
  var tax_rates = new Array();
<?php
for ($i = 0, $n = sizeof($tax_class_array); $i < $n; $i++) {
  if ($tax_class_array[$i]['id'] > 0) {
    echo 'tax_rates["' . $tax_class_array[$i]['id'] . '"] = ' . zen_get_tax_rate_value($tax_class_array[$i]['id']) . ';' . "\n";
  }
}
?>

  function doRound(x, places) {
      return Math.round(x * Math.pow(10, places)) / Math.pow(10, places);
  }

  function getTaxRate() {
      var selected_value = document.forms["new_product"].products_tax_class_id.selectedIndex;
      var parameterVal = document.forms["new_product"].products_tax_class_id[selected_value].value;

      if ((parameterVal > 0) && (tax_rates[parameterVal] > 0)) {
          return tax_rates[parameterVal];
      } else {
          return 0;
      }
  }

  function updateGross() {
      var taxRate = getTaxRate();
      var grossValue = document.forms["new_product"].products_price.value;

      if (taxRate > 0) {
          grossValue = grossValue * ((taxRate / 100) + 1);
      }

      document.forms["new_product"].products_price_gross.value = doRound(grossValue, 4);
  }

  function updateNet() {
      var taxRate = getTaxRate();
      var netValue = document.forms["new_product"].products_price_gross.value;

      if (taxRate > 0) {
          netValue = netValue / ((taxRate / 100) + 1);
      }

      document.forms["new_product"].products_price.value = doRound(netValue, 4);
  }
</script>
<div class="container-fluid">
    <?php
    echo zen_draw_form('new_product', FILENAME_PRODUCT, 'cPath=' . $current_category_id . (isset($_GET['pID']) ? '&pID=' . $_GET['pID'] : '') . '&action=new_product_preview' . (isset($_GET['page']) ? '&page=' . $_GET['page'] : '') . ( (isset($_GET['search']) && !empty($_GET['search'])) ? '&search=' . $_GET['search'] : '') . ( (isset($_POST['search']) && !empty($_POST['search']) && empty($_GET['search'])) ? '&search=' . $_POST['search'] : ''), 'post', 'enctype="multipart/form-data" class="form-horizontal"');
    if (isset($product_type)) {
      echo zen_draw_hidden_field('product_type', $product_type);
    }
    ?>
  <h3 class="col-sm-11"><?php echo sprintf(TEXT_NEW_PRODUCT, zen_output_generated_category_path($current_category_id)); ?></h3>
  <div class="col-sm-1"><?php echo zen_info_image($cInfo->categories_image, $cInfo->categories_name, HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT); ?></div>
  <div>
    <span class="floatButton text-right">
      <button type="submit" class="btn btn-primary"><?php echo IMAGE_PREVIEW; ?></button>&nbsp;&nbsp;<a href="<?php echo zen_href_link(FILENAME_CATEGORY_PRODUCT_LISTING, 'cPath=' . $current_category_id . (isset($_GET['pID']) ? '&pID=' . $_GET['pID'] : '') . (isset($_GET['page']) ? '&page=' . $_GET['page'] : '') . ( (isset($_GET['search']) && !empty($_GET['search'])) ? '&search=' . $_GET['search'] : '') . ( (isset($_POST['search']) && !empty($_POST['search']) && empty($_GET['search'])) ? '&search=' . $_POST['search'] : '')); ?>" class="btn btn-default" role="button"><?php echo IMAGE_CANCEL; ?></a>
    </span>
  </div>
  <div class="form-group">
      <?php
// show when product is linked
      if (isset($_GET['pID']) && zen_get_product_is_linked($_GET['pID']) == 'true' && $_GET['pID'] > 0) {
        ?>
        <?php echo zen_draw_label(TEXT_MASTER_CATEGORIES_ID, 'master_category', 'class="col-sm-3 control-label"'); ?>
      <div class="col-sm-9 col-md-6">
        <div class="input-group">
          <span class="input-group-addon">
              <?php
              echo zen_image(DIR_WS_IMAGES . 'icon_yellow_on.gif', IMAGE_ICON_LINKED) . '&nbsp;&nbsp;';
              ?>
          </span>
          <?php
          echo zen_draw_pull_down_menu('master_category', zen_get_master_categories_pulldown($_GET['pID']), $pInfo->master_categories_id, 'class="form-control"');
          ?>
        </div>
      </div>
    <?php } else { ?>
      <div class="col-sm-3 text-right">
        <strong>
            <?php echo TEXT_MASTER_CATEGORIES_ID; ?>
        </strong>
      </div>
      <div class="col-sm-9 col-md-6"><?php echo TEXT_INFO_ID . (isset($_GET['pID']) && $_GET['pID'] > 0 ? $pInfo->master_categories_id . ' ' . zen_get_category_name($pInfo->master_categories_id, $_SESSION['languages_id']) : $current_category_id . ' ' . zen_get_category_name($current_category_id, $_SESSION['languages_id'])); ?></div>
    <?php } ?>
  </div>
  <div class="form-group">
    <div class="col-sm-offset-3 col-sm-9 col-md-6">
        <?php echo TEXT_INFO_MASTER_CATEGORIES_ID; ?>
    </div>
  </div>
  <?php
// hidden fields not changeable on products page
  echo zen_draw_hidden_field('master_categories_id', $pInfo->master_categories_id);
  echo zen_draw_hidden_field('products_discount_type', $pInfo->products_discount_type);
  echo zen_draw_hidden_field('products_discount_type_from', $pInfo->products_discount_type_from);
  echo zen_draw_hidden_field('products_price_sorter', $pInfo->products_price_sorter);
  ?>
  <div class="col-sm-12 text-center"><?php echo (zen_get_categories_status($current_category_id) == '0' ? TEXT_CATEGORIES_STATUS_INFO_OFF : '') . (isset($out_status) && $out_status == true ? ' ' . TEXT_PRODUCTS_STATUS_INFO_OFF : ''); ?></div>
  <div class="form-group">
      <?php echo zen_draw_label(TEXT_PRODUCTS_STATUS, 'products_status', 'class="col-sm-3 control-label"'); ?>
    <div class="col-sm-9 col-md-6">
      <label class="radio-inline"><?php echo zen_draw_radio_field('products_status', '1', ($pInfo->products_status == 1)) . TEXT_PRODUCT_AVAILABLE; ?></label>
      <label class="radio-inline"><?php echo zen_draw_radio_field('products_status', '0', ($pInfo->products_status == 0)) . TEXT_PRODUCT_NOT_AVAILABLE; ?></label>
    </div>
  </div>
  <div class="form-group">
      <?php echo zen_draw_label(TEXT_PRODUCTS_DATE_AVAILABLE, 'products_date_available', 'class="col-sm-3 control-label"'); ?>
    <div class="col-sm-9 col-md-6">
      <div class="date input-group" id="datepicker">
        <span class="input-group-addon datepicker_icon">
          <i class="fa fa-calendar fa-lg"></i>
        </span>
        <?php echo zen_draw_input_field('products_date_available', $pInfo->products_date_available, 'class="form-control"'); ?>
      </div>
      <span class="help-block errorText">(YYYY-MM-DD)</span>
    </div>
  </div>
  <div class="form-group">
      <?php echo zen_draw_label(TEXT_PRODUCTS_MANUFACTURER, 'manufacturers_id', 'class="col-sm-3 control-label"'); ?>
    <div class="col-sm-9 col-md-6">
        <?php echo zen_draw_pull_down_menu('manufacturers_id', $manufacturers_array, $pInfo->manufacturers_id, 'class="form-control"'); ?>
    </div>
  </div>
  <div class="form-group">
      <?php echo zen_draw_label(TEXT_PRODUCTS_NAME, 'products_name', 'class="col-sm-3 control-label"'); ?>
    <div class="col-sm-9 col-md-6">
        <?php
        for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
          ?>
        <div class="input-group">
          <span class="input-group-addon">
              <?php echo zen_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']); ?>
          </span>
          <?php echo zen_draw_input_field('products_name[' . $languages[$i]['id'] . ']', htmlspecialchars(isset($products_name[$languages[$i]['id']]) ? stripslashes($products_name[$languages[$i]['id']]) : zen_get_products_name($pInfo->products_id, $languages[$i]['id']), ENT_COMPAT, CHARSET, TRUE), zen_set_field_length(TABLE_PRODUCTS_DESCRIPTION, 'products_name') . ' class="form-control"'); ?>
        </div>
        <br>
        <?php
      }
      ?>
    </div>
  </div>
<?php
//-bof-zen_tags-lat9  *** 1 of 1 ***
    // -----
    // Give an observer the chance to supply some additional product-related inputs.  Each
    // entry in the $extra_product_inputs returned contains:
    //
    // array(
    //    'label' => array(
    //        'text' => 'The label text',   (required)
    //        'field_name' => 'The name of the field associated with the label', (required)
    //        'addl_class' => {Any additional class to be applied to the label} (optional)
    //        'parms' => {Any additional parameters for the label, e.g. 'style="font-weight: 700;"} (optional)
    //    ),
    //    'input' => 'The HTML to be inserted' (required)
    // )
    //
    // Note: The product's type can be found in the 'product_type' element of the passed $pInfo object.
    //
    $extra_product_inputs = array();
    $zco_notifier->notify('NOTIFY_ADMIN_PRODUCT_COLLECT_INFO_EXTRA_INPUTS', $pInfo, $extra_product_inputs);
    if (!empty($extra_product_inputs)) {
        foreach ($extra_product_inputs as $extra_input) {
            $addl_class = (isset($extra_input['label']['addl_class'])) ? (' ' . $extra_input['label']['addl_class']) : '';
            $parms = (isset($extra_input['label']['parms'])) ? (' ' . $extra_input['label']['parms']) : '';
?>
            <div class="form-group">
                <?php echo zen_draw_label($extra_input['label']['text'], $extra_input['label']['field_name'], 'class="col-sm-3 control-label' . $addl_class . '"' . $parms); ?>
                <div class="col-sm-9 col-md-6"><?php echo $extra_input['input']; ?></div>
            </div>
<?php
        }
    }
//-eof-zen_tags-lat9  *** 1 of 1 ***
?>
  <div class="form-group">
      <?php echo zen_draw_label(TEXT_PRODUCT_IS_FREE, 'product_is_free', 'class="col-sm-3 control-label"'); ?>
    <div class="col-sm-9 col-md-6">
      <label class="radio-inline"><?php echo zen_draw_radio_field('product_is_free', '1', ($pInfo->product_is_free == 1)) . TEXT_YES; ?></label>
      <label class="radio-inline"><?php echo zen_draw_radio_field('product_is_free', '0', ($pInfo->product_is_free == 0)) . TEXT_NO; ?></label>
      <?php echo ($pInfo->product_is_free == 1 ? '<span class="help-block errorText">' . TEXT_PRODUCTS_IS_FREE_EDIT . '</span>' : ''); ?>
    </div>
  </div>
  <div class="form-group">
      <?php echo zen_draw_label(TEXT_PRODUCT_IS_CALL, 'product_is_call', 'class="col-sm-3 control-label"'); ?>
    <div class="col-sm-9 col-md-6">
      <label class="radio-inline"><?php echo zen_draw_radio_field('product_is_call', '1', ($pInfo->product_is_call == 1)) . TEXT_YES; ?></label>
      <label class="radio-inline"><?php echo zen_draw_radio_field('product_is_call', '0', ($pInfo->product_is_call == 0)) . TEXT_NO; ?></label>
      <?php echo ($pInfo->product_is_call == 1 ? '<span class="help-block errorText">' . TEXT_PRODUCTS_IS_CALL_EDIT . '</span>' : ''); ?>
    </div>
  </div>
  <div class="form-group">
      <?php echo zen_draw_label(TEXT_PRODUCTS_PRICED_BY_ATTRIBUTES, 'products_priced_by_attribute', 'class="col-sm-3 control-label"'); ?>
    <div class="col-sm-9 col-md-6">
      <label class="radio-inline"><?php echo zen_draw_radio_field('products_priced_by_attribute', '1', ($pInfo->products_priced_by_attribute == 1)) . TEXT_PRODUCT_IS_PRICED_BY_ATTRIBUTE; ?></label>
      <label class="radio-inline"><?php echo zen_draw_radio_field('products_priced_by_attribute', '0', ($pInfo->products_priced_by_attribute == 0)) . TEXT_PRODUCT_NOT_PRICED_BY_ATTRIBUTE; ?></label>
      <?php echo ($pInfo->products_priced_by_attribute == 1 ? '<span class="help-block errorText">' . TEXT_PRODUCTS_PRICED_BY_ATTRIBUTES_EDIT . '</span>' : ''); ?>
    </div>
  </div>
  <div class="well" style="color: #31708f;background-color: #d9edf7;border-color: #bce8f1;;padding: 10px 10px 0 0;">
    <div class="form-group">
        <?php echo zen_draw_label(TEXT_PRODUCTS_TAX_CLASS, 'products_tax_class_id', 'class="col-sm-3 control-label"'); ?>
      <div class="col-sm-9 col-md-6">
          <?php echo zen_draw_pull_down_menu('products_tax_class_id', $tax_class_array, $pInfo->products_tax_class_id, 'onchange="updateGross()" class="form-control"'); ?>
      </div>
    </div>
<?php
//-bof-posm_price_weight-lat9  *** 1 of 2 ***
    if (function_exists('posmpw_price_weight_managed') && posmpw_price_weight_managed($pInfo->products_id)) {
?>
    <div class="form-group">
        <?php echo zen_draw_label(POSMPW_TEXT_PRODUCTS_PRICE_CLICK, 'products_price', 'class="col-sm-3 control-label"'); ?>
        <div class="col-sm-9 col-md-6">
            <a href="<?php echo zen_href_link(FILENAME_PRODUCTS_OPTIONS_STOCK, 'pID=' . $pInfo->products_id); ?>" class="btn btn-default" role="button"><?php echo POSMPW_ALT_MANAGE_PRICES; ?></a>
            <?php echo zen_draw_hidden_field('products_price', '0', 'id="products_price"') . zen_draw_hidden_field('products_price_gross', '0'); ?>
        </div>
    </div>
<?php
    } else {
?>
    <div class="form-group">
        <?php echo zen_draw_label(TEXT_PRODUCTS_PRICE_NET, 'products_price', 'class="col-sm-3 control-label"'); ?>
      <div class="col-sm-9 col-md-6">
          <?php echo zen_draw_input_field('products_price', $pInfo->products_price, 'onkeyup="updateGross()" class="form-control"'); ?>
      </div>
    </div>
    <div class="form-group">
        <?php echo zen_draw_label(TEXT_PRODUCTS_PRICE_GROSS, 'products_price_gross', 'class="col-sm-3 control-label"'); ?>
      <div class="col-sm-9 col-md-6">
          <?php echo zen_draw_input_field('products_price_gross', $pInfo->products_price, 'onkeyup="updateNet()" class="form-control"'); ?>
      </div>
    </div>
<?php
    }
//-eof-posm_price_weight-lat9  *** 1 of 2 ***
?>
  </div>
  <script>
    updateGross();
  </script>
  <div class="form-group">
      <?php echo zen_draw_label(TEXT_PRODUCTS_VIRTUAL, 'products_virtual', 'class="col-sm-3 control-label"'); ?>
    <div class="col-sm-9 col-md-6">
      <label class="radio-inline"><?php echo zen_draw_radio_field('products_virtual', '1', ($pInfo->products_virtual == 1)) . TEXT_PRODUCT_IS_VIRTUAL; ?></label>
      <label class="radio-inline"><?php echo zen_draw_radio_field('products_virtual', '0', ($pInfo->products_virtual == 0)) . TEXT_PRODUCT_NOT_VIRTUAL; ?></label>
      <?php echo ($pInfo->products_virtual == 1 ? '<span class="help-block errorText">' . TEXT_VIRTUAL_EDIT . '</span>' : ''); ?>
    </div>
  </div>
  <div class="form-group">
      <?php echo zen_draw_label(TEXT_PRODUCTS_IS_ALWAYS_FREE_SHIPPING, 'product_is_always_free_shipping', 'class="col-sm-3 control-label"'); ?>
    <div class="col-sm-9 col-md-6">
      <label class="radio-inline"><?php echo zen_draw_radio_field('product_is_always_free_shipping', '1', ($pInfo->product_is_always_free_shipping == 1)) . TEXT_PRODUCT_IS_ALWAYS_FREE_SHIPPING; ?></label>
      <label class="radio-inline"><?php echo zen_draw_radio_field('product_is_always_free_shipping', '0', ($pInfo->product_is_always_free_shipping == 0)) . TEXT_PRODUCT_NOT_ALWAYS_FREE_SHIPPING; ?></label>
      <label class="radio-inline"><?php echo zen_draw_radio_field('product_is_always_free_shipping', '2', ($pInfo->product_is_always_free_shipping == 2)) . TEXT_PRODUCT_SPECIAL_ALWAYS_FREE_SHIPPING; ?></label>
      <?php echo ($pInfo->product_is_always_free_shipping == 1 ? '<span class="help-block errorText">' . TEXT_FREE_SHIPPING_EDIT . '</span>' : ''); ?>
    </div>
  </div>
  <div class="form-group">
      <?php echo zen_draw_label(TEXT_PRODUCTS_QTY_BOX_STATUS, 'products_qty_box_status', 'class="col-sm-3 control-label"'); ?>
    <div class="col-sm-9 col-md-6">
      <label class="radio-inline"><?php echo zen_draw_radio_field('products_qty_box_status', '1', ($pInfo->products_qty_box_status == 1 ? true : false)) . TEXT_PRODUCTS_QTY_BOX_STATUS_ON; ?></label>
      <label class="radio-inline"><?php echo zen_draw_radio_field('products_qty_box_status', '0', ($pInfo->products_qty_box_status == 0 ? true : false)) . TEXT_PRODUCTS_QTY_BOX_STATUS_OFF; ?></label>
      <?php echo ($pInfo->products_qty_box_status == 0 ? '<span class="help-block errorText">' . TEXT_PRODUCTS_QTY_BOX_STATUS_EDIT . '</span>' : ''); ?>
    </div>
  </div>
  <div class="form-group">
      <?php echo zen_draw_label(TEXT_PRODUCTS_QUANTITY_MIN_RETAIL, 'products_quantity_order_min', 'class="col-sm-3 control-label"'); ?>
    <div class="col-sm-9 col-md-6">
        <?php echo zen_draw_input_field('products_quantity_order_min', ($pInfo->products_quantity_order_min == 0 ? 1 : $pInfo->products_quantity_order_min), 'class="form-control"'); ?>
    </div>
  </div>
  <div class="form-group">
      <?php echo zen_draw_label(TEXT_PRODUCTS_QUANTITY_MAX_RETAIL, 'products_quantity_order_max', 'class="col-sm-3 control-label"'); ?>
    <div class="col-sm-9 col-md-6">
      <?php echo zen_draw_input_field('products_quantity_order_max', $pInfo->products_quantity_order_max, 'class="form-control"'); ?>&nbsp;&nbsp;<?php echo TEXT_PRODUCTS_QUANTITY_MAX_RETAIL_EDIT; ?>
    </div>
  </div>
  <div class="form-group">
      <?php echo zen_draw_label(TEXT_PRODUCTS_QUANTITY_UNITS_RETAIL, 'products_quantity_order_units', 'class="col-sm-3 control-label"'); ?>
    <div class="col-sm-9 col-md-6">
        <?php echo zen_draw_input_field('products_quantity_order_units', ($pInfo->products_quantity_order_units == 0 ? 1 : $pInfo->products_quantity_order_units), 'class="form-control"'); ?>
    </div>
  </div>
  <div class="form-group">
      <?php echo zen_draw_label(TEXT_PRODUCTS_MIXED, 'products_quantity_mixed', 'class="col-sm-3 control-label"'); ?>
    <div class="col-sm-9 col-md-6">
      <label class="radio-inline"><?php echo zen_draw_radio_field('products_quantity_mixed', '1', ($pInfo->products_quantity_mixed == 1)) . TEXT_YES; ?></label>
      <label class="radio-inline"><?php echo zen_draw_radio_field('products_quantity_mixed', '0', ($pInfo->products_quantity_mixed == 0)) . TEXT_NO; ?></label>
    </div>
  </div>
  <div class="form-group">
      <?php echo zen_draw_label(TEXT_PRODUCTS_DESCRIPTION, 'products_description', 'class="col-sm-3 control-label"'); ?>
    <div class="col-sm-9 col-md-6">
        <?php
        for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
          ?>
        <div class="input-group">
          <span class="input-group-addon">
              <?php echo zen_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']); ?>
          </span>
          <?php echo zen_draw_textarea_field('products_description[' . $languages[$i]['id'] . ']', 'soft', '100%', '30', htmlspecialchars((isset($products_description[$languages[$i]['id']])) ? stripslashes($products_description[$languages[$i]['id']]) : zen_get_products_description($pInfo->products_id, $languages[$i]['id']), ENT_COMPAT, CHARSET, TRUE), 'class="editorHook form-control"'); ?>
        </div>
        <br>
        <?php
      }
      ?>
    </div>
  </div>
  <div class="form-group">
      <?php echo zen_draw_label(TEXT_PRODUCTS_QUANTITY, 'products_quantity', 'class="col-sm-3 control-label"'); ?>
    <div class="col-sm-9 col-md-6">
        <?php echo zen_draw_input_field('products_quantity', $pInfo->products_quantity, 'class="form-control"'); ?>
    </div>
  </div>
  <div class="form-group">
      <?php echo zen_draw_label(TEXT_PRODUCTS_MODEL, 'products_model', 'class="col-sm-3 control-label"'); ?>
    <div class="col-sm-9 col-md-6">
        <?php echo zen_draw_input_field('products_model', htmlspecialchars(stripslashes($pInfo->products_model), ENT_COMPAT, CHARSET, TRUE), zen_set_field_length(TABLE_PRODUCTS, 'products_model') . ' class="form-control"'); ?>
    </div>
  </div>
  <?php
  $dir_info = zen_build_subdirectories_array(DIR_FS_CATALOG_IMAGES);
  $default_directory = substr($pInfo->products_image, 0, strpos($pInfo->products_image, '/') + 1);
  ?>

  <div class="form-group">
      <?php echo zen_draw_separator('pixel_black.gif', '100%', '3'); ?>
  </div>
  <div class="form-group">
      <?php echo zen_draw_label(TEXT_PRODUCTS_IMAGE, 'products_image', 'class="col-sm-3 control-label"'); ?>
    <div class="col-sm-9 col-md-9 col-lg-6">
      <div class="col-md-6">
        <div class="row">
            <?php echo zen_draw_file_field('products_image', '', 'class="form-control"'); ?>
        </div>
        <div class="row">&nbsp;</div>
        <div class="row">
            <?php echo zen_draw_label(TEXT_IMAGE_CURRENT, 'products_previous_image', 'class="control-label"') . '&nbsp;' . ($pInfo->products_image != '' ? $pInfo->products_image : NONE); ?>
            <?php echo zen_draw_hidden_field('products_previous_image', $pInfo->products_image); ?>
        </div>
        <div class="row">&nbsp;</div>
      </div>
      <div class="col-md-6">
        <div class="row">
          <?php echo zen_draw_label(TEXT_PRODUCTS_IMAGE_DIR, 'img_dir', 'class="control-label"'); ?>&nbsp;<?php echo zen_draw_pull_down_menu('img_dir', $dir_info, $default_directory, 'class="form-control"'); ?>
        </div>
        <div class="row">&nbsp;</div>
        <div class="row">
            <?php echo zen_draw_label(TEXT_IMAGES_DELETE, 'image_delete', 'class="control-label"'); ?>
          <label class="radio-inline"><?php echo zen_draw_radio_field('image_delete', '0', true) . TABLE_HEADING_NO; ?></label>
          <label class="radio-inline"><?php echo zen_draw_radio_field('image_delete', '1', false) . TABLE_HEADING_YES; ?></label>
        </div>
        <div class="row">&nbsp;</div>
        <div class="row">
            <?php echo zen_draw_label(TEXT_IMAGES_OVERWRITE, 'overwrite', 'class="control-label"'); ?>
          <label class="radio-inline"><?php echo zen_draw_radio_field('overwrite', '0', false) . TABLE_HEADING_NO; ?></label>
          <label class="radio-inline"><?php echo zen_draw_radio_field('overwrite', '1', true) . TABLE_HEADING_YES; ?></label>
        </div>
        <div class="row">&nbsp;</div>
        <div class="row">
            <?php echo zen_draw_label(TEXT_PRODUCTS_IMAGE_MANUAL, 'products_image_manual', 'class="control-label"') . zen_draw_input_field('products_image_manual', '', 'class="form-control"'); ?>
        </div>
      </div>
    </div>
  </div>
  <div class="form-group">
      <?php echo zen_draw_separator('pixel_black.gif', '100%', '3'); ?>
  </div>
  <div class="form-group">
    <div class="col-sm-3 control-label">
      <?php echo zen_draw_label(TEXT_PRODUCTS_URL, 'products_url'); ?><span class="help-block"><?php echo TEXT_PRODUCTS_URL_WITHOUT_HTTP; ?></span>
    </div>
    <div class="col-sm-9 col-md-6">
        <?php
        for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
          ?>
        <div class="input-group">
          <span class="input-group-addon">
              <?php echo zen_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']); ?>
          </span>
          <?php echo zen_draw_input_field('products_url[' . $languages[$i]['id'] . ']', htmlspecialchars(isset($products_url[$languages[$i]['id']]) ? $products_url[$languages[$i]['id']] : zen_get_products_url($pInfo->products_id, $languages[$i]['id']), ENT_COMPAT, CHARSET, TRUE), zen_set_field_length(TABLE_PRODUCTS_DESCRIPTION, 'products_url') . ' class="form-control"'); ?>
        </div>
        <br>
        <?php
      }
      ?>
    </div>
  </div>
<?php
//-bof-posm_price_weight-lat9  *** 2 of 2 ***
    if (function_exists('posmpw_price_weight_managed') && posmpw_price_weight_managed($pInfo->products_id)) {
?>
   <div class="form-group">
      <?php echo zen_draw_label(POSMPW_TEXT_PRODUCTS_WEIGHT_CLICK, 'products_weight', 'class="col-sm-3 control-label"'); ?>
      <div class="col-sm-9 col-md-6">
          <a href="<?php echo zen_href_link(FILENAME_PRODUCTS_OPTIONS_STOCK, 'pID=' . $pInfo->products_id); ?>" class="btn btn-default" role="button"><?php echo POSMPW_ALT_MANAGE_WEIGHTS; ?></a>
          <?php echo zen_draw_hidden_field('products_weight', '0', 'id="products_weight"'); ?>
      </div>
  </div>
<?php
    } else {
?>
  <div class="form-group">
      <?php echo zen_draw_label(TEXT_PRODUCTS_WEIGHT, 'products_weight', 'class="col-sm-3 control-label"'); ?>
    <div class="col-sm-9 col-md-6">
        <?php echo zen_draw_input_field('products_weight', $pInfo->products_weight, 'class="form-control"'); ?>
    </div>
  </div>
<?php
    }
//-eof-posm_price_weight-lat9  *** 2 of 2 ***
?>
  <div class="form-group">
      <?php echo zen_draw_label(TEXT_PRODUCTS_SORT_ORDER, 'products_sort_order', 'class="col-sm-3 control-label"'); ?>
    <div class="col-sm-9 col-md-6">
      <?php echo zen_draw_input_field('products_sort_order', $pInfo->products_sort_order, 'class="form-control"'); ?>
    </div>
    <?php
    echo zen_draw_hidden_field('products_date_added', (zen_not_null($pInfo->products_date_added) ? $pInfo->products_date_added : date('Y-m-d')));
    echo ((isset($_GET['search']) && !empty($_GET['search'])) ? zen_draw_hidden_field('search', $_GET['search']) : '');
    echo ((isset($_POST['search']) && !empty($_POST['search']) && empty($_GET['search'])) ? zen_draw_hidden_field('search', $_POST['search']) : '');
    ?>
  </div>
  <?php echo '</form>'; ?>
</div>
