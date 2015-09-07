<?php

$nzshpcrt_gateways[$num]['name'] = 'TotalCoin';
$nzshpcrt_gateways[$num]['internalname'] = 'totalcoin';
$nzshpcrt_gateways[$num]['function'] = 'function_totalcoin';
$nzshpcrt_gateways[$num]['form'] = 'form_totalcoin';
$nzshpcrt_gateways[$num]['submit_function'] = 'submit_totalcoin';
$nzshpcrt_gateways[$num]['payment_type'] = 'tc';
$nzshpcrt_gateways[$num]['display_name'] = 'TotalCoin';
$nzshpcrt_gateways[$num]['class_name'] = 'wpsc_merchant_totalcoin';

include_once "tc-lib/API.php";

function function_totalcoin($seperator, $sessionid)
{
    global $wpdb, $wpsc_cart;
    $purchase_log = $wpdb->get_row(
    "SELECT * FROM `".WPSC_TABLE_PURCHASE_LOGS.
    "` WHERE `sessionid`= ".$sessionid." LIMIT 1"
    , ARRAY_A);

    $usersql = "SELECT `".WPSC_TABLE_SUBMITED_FORM_DATA."`.value,
    `".WPSC_TABLE_CHECKOUT_FORMS."`.`name`,
    `".WPSC_TABLE_CHECKOUT_FORMS."`.`unique_name` FROM
    `".WPSC_TABLE_CHECKOUT_FORMS."` LEFT JOIN
    `".WPSC_TABLE_SUBMITED_FORM_DATA."` ON
    `".WPSC_TABLE_CHECKOUT_FORMS."`.id =
    `".WPSC_TABLE_SUBMITED_FORM_DATA."`.`form_id` WHERE
    `".WPSC_TABLE_SUBMITED_FORM_DATA."`.`log_id`=".$purchase_log['id'];

    $userinfo = $wpdb->get_results($usersql, ARRAY_A);

    $data = array();
    $data['sucess'] = get_option('totalcoin_url_sucess');
    $data['pending'] = get_option('totalcoin_url_pending');
    $data['before_step'] = get_option('totalcoin_url_before_step');
    $data['Email'] = get_option('totalcoin_email');
    $data['ApiKey'] = get_option('totalcoin_apikey');
    $data['Currency'] = get_option('totalcoin_currency');
    $data['Country'] = get_option('totalcoin_country');
    $data['MerchantId'] = get_option('totalcoin_merchantid');
    $data['Reference'] = $purchase_log['id'];
    $data['Site'] = 'Wordpress';
    $data['PaymentMethods'] = get_option('totalcoin_methods');
    $data['Quantity'] = 1;
    $data['Amount'] = number_format($wpsc_cart->total_price, 2, '.', '');

    $description = '';
    foreach($wpsc_cart->cart_items as $item) {
      $description .= $item->product_name . ' - Precio por Unidad: ' . number_format($item->unit_price, 2, '.', '');
      $description .= ' - Cantidad: ' . $item->quantity . ' | ';
    }
    $description = rtrim($description, ' | ');
    $data['Description'] = $description;

    $tc = new TotalCoinAPI($data['Email'], $data['ApiKey']);
    $results = $tc->perform_checkout($data);
    if ($results['IsOk']) {
        $url = $results['Response']['URL'];
        $type_checkout = get_option('totalcoin_typecheckout');

        switch($type_checkout):
          case "Redirect":
            header("location: " . $url);
          break;
          case "Iframe":
            $content = '<iframe src="' . $url . '" name="TC-Checkout" width="953" height="600" frameborder="0" style="overflow:hidden"></iframe>';
          break;
          default:
            $content = '<TOTALCOIN SIMPLE BUTTON>';
          break;
        endswitch;
    } else {
        $content = 'Se ha producido un Error Interno';
    }

    $title = 'TotalCoin Checkout';
    get_header();
    $html = '<div style="position: relative; margin: 20px 0;" >';
    $html .= '<div style="margin: 0 auto; width: 80%; ">';
    $html .= '<h3>' . $title . '</h3>';
    $html .= $content;
    $html .= '</div>';
    $html .= '</div>';
    echo $html;
    get_footer();

    exit;
}

function form_totalcoin()
{
    if (get_option('totalcoin_url_sucess') != '') {
      $url_sucess = get_option('totalcoin_url_sucess');
    } else {
      $url_sucess = get_site_url();
    }

    if (get_option('totalcoin_url_pending') != '') {
      $url_pending = get_option('totalcoin_url_pending');
    } else {
      $url_pending = get_site_url();
    }

    if (get_option('totalcoin_url_before_step') != '') {
      $url_before_step = get_option('totalcoin_url_before_step');
    } else {
      $url_before_step = get_site_url();
    }

    $output ='<br /><tr><td>';

    $output.='E-Mail</td>';
    $output.='<td><input name="totalcoin_email" type="text" value="'. get_option('totalcoin_email') .'"/></td></tr>';

    $output.='<tr><td>API Key</td>';
    $output.='<td><input name="totalcoin_apikey" type="text" value="'. get_option('totalcoin_apikey') .'"/></td></tr>';

    $output.='<tr><td>Tipo de Checkout</td>';
    $output.='<td>'. tc_type_checkout() .'</td></tr>';

    $output.='<tr><td>Url Paso anterior</td>';
    $output.='<td><input name="totalcoin_url_before_step" type="text" value="'. $url_before_step .'"/></td></tr>';

    $output.='<tr><td>Url Pago Exitoso</td>';
    $output.='<td><input name="totalcoin_url_sucess" type="text" value="'. $url_sucess .'"/></td></tr>';

    $output.='<tr><td>Url Pago Pendiente</td>';
    $output.='<td><input name="totalcoin_url_pending" type="text" value="'. $url_pending .'"/></td></tr>';

    $output.='<tr><td>Metodos de Pago</td>';
    $output.='<td>'. tc_methods() .'</td></tr>';

    $output.='<tr><td>País</td>';
    $output.='<td>'. tc_country() .'</td></tr>';

    $output.='<tr><td>Moneda</td>';
    $output.='<td>'. tc_currency() .'</td></tr>';

    $output.='<tr><td>Merchant Id</td>';
    $output.='<td><input name="totalcoin_merchantid" type="text" value="'. get_option('totalcoin_merchantid') .'"/></td></tr>';

    return $output;
}

function submit_totalcoin()
{
    if (isset($_POST['totalcoin_email'])) {
      update_option('totalcoin_email', trim($_POST['totalcoin_email']));
    }

    if (isset($_POST['totalcoin_apikey'])) {
      update_option('totalcoin_apikey', trim($_POST['totalcoin_apikey']));
    }

    if (isset($_POST['totalcoin_typecheckout'])) {
      update_option('totalcoin_typecheckout', trim($_POST['totalcoin_typecheckout']));
    }

    if (isset($_POST['totalcoin_url_before_step'])) {
      update_option('totalcoin_url_before_step', trim($_POST['totalcoin_url_before_step']));
    }

    if (isset($_POST['totalcoin_url_sucess'])) {
      update_option('totalcoin_url_sucess', trim($_POST['totalcoin_url_sucess']));
    }

    if (isset($_POST['totalcoin_url_pending'])) {
      update_option('totalcoin_url_pending', trim($_POST['totalcoin_url_pending']));
    }

    if (isset($_POST['totalcoin_country'])) {
      update_option('totalcoin_country', trim($_POST['totalcoin_country']));
    }

    if (isset($_POST['totalcoin_currency'])) {
      update_option('totalcoin_currency', trim($_POST['totalcoin_currency']));
    }

    if (isset($_POST['totalcoin_merchantid'])) {
      update_option('totalcoin_merchantid', trim($_POST['totalcoin_merchantid']));
    }

    //String: CREDITCARD|CASH|TOTALCOIN
    if ($_POST['totalcoin_methods'] != null) {
      $methods = '';
      foreach ($_POST['totalcoin_methods'] as $name) {
        $methods .= $name . '|';
      }
      $methods = rtrim($methods, '|');

      update_option('totalcoin_methods', $methods);
    } else {
      update_option('totalcoin_methods', '');
    }

    return true;
}

//PRIVATE FUNCTIONS
function tc_methods(){
    $activemethods = preg_split("/[\s|]+/",get_option('totalcoin_methods'));
    /*
    Tarjeta de Crédito (CREDITCARD)
    Efectivo (CASH): cupones de pago como RapiPago, PagoFacil, etc.
    TOTALCOIN: saldo en cuenta TotalCoin
    */
    $methods = array(
      array('id' => 'CREDITCARD', 'name' => 'Tarjeta de Crédito'),
      array('id' => 'CASH', 'name' => 'Efectivo'),
      array('id' => 'TOTALCOIN', 'name' => 'Saldo en cuenta TotalCoin'),
    );
    $showmethods = '';
    foreach ($methods as $method):
      if ($activemethods != null && in_array($method['id'], $activemethods)) {
        $showmethods .= '<input name="totalcoin_methods[]" type="checkbox" checked="yes" value="'.$method['id'].'">'.$method['name'].'<br />';
      } else {
        $showmethods .= '<input name="totalcoin_methods[]" type="checkbox" value="'.$method['id'].'"> '.$method['name'].'<br />';
      }
    endforeach;

    return $showmethods;
}

function tc_type_checkout()
{
    $type_checkout = get_option('totalcoin_typecheckout');
    $type_checkout = $type_checkout === false || is_null($type_checkout) ? "Iframe" : $type_checkout;

    $type_checkout_options = array("Iframe", "Redirect");

    $select_type_checkout = '<select name="totalcoin_typecheckout" id="type_checkout">';
    foreach($type_checkout_options as $select_type):

      $selected = "";
      if($select_type == $type_checkout):
        $selected = 'selected="selected"';
      endif;

      $select_type_checkout .= '<option value="' . $select_type . '" id="type-checkout-' . $select_type . '" ' . $selected . ' >' . $select_type . '</option>';
    endforeach;
    $select_type_checkout .= "</select>";

    return $select_type_checkout;
}

function tc_currency()
{
    if (get_option('totalcoin_currency') == null || get_option('totalcoin_currency') == '') {
      $totalcoin_currency = 'ARS';
    } else {
      $totalcoin_currency = get_option('totalcoin_currency');
    }

    $currencys = array('ARS' =>'Pesos Argentinos');

    $showcurrency = '<select name="totalcoin_currency">';
    foreach ($currencys as  $currency => $key):
      if($currency == $totalcoin_currency){
        $showcurrency .= '<option value="'.$currency.'" selected="selected">'.$key.'</option>';
      } else {
        $showcurrency .= '<option value="'.$currency.'">'.$key .'</option>';
      }
    endforeach;
    $showcurrency .= '</select>';

    return $showcurrency;
}

function tc_country()
{
    if (get_option('totalcoin_country') == null || get_option('totalcoin_country') == '') {
      $totalcoin_country = 'ARG';
    } else {
      $totalcoin_country = get_option('totalcoin_country');
    }

    $countries = array('ARG' =>'Argentina');

    $showcountry = '<select name="totalcoin_country">';
    foreach ($countries as  $country => $key):
      if($country == $totalcoin_country){
        $showcountry .= '<option value="'.$country.'" selected="selected">'.$key.'</option>';
      } else {
        $showcountry .= '<option value="'.$country.'">'.$key .'</option>';
      }
    endforeach;
    $showcountry .= '</select>';

    return $showcountry;
}

function tc_get_last_order_status_from_transaction_history($transaction_histories) {
    $ordered_transaction_histories = Array();
    foreach ($transaction_histories as $transaction_history) {
      $date_created = date_create($transaction_history['Date']);
      $history = Array();
      $history['date'] = date_format($date_created, 'Y-m-d H:i:s');
      $history['status'] = $transaction_history['TransactionState'];
      $ordered_transaction_histories[] = $history;
    }

    if (count($ordered_transaction_histories) > 1) {
      usort($ordered_transaction_histories, function($a, $b) {
          return strtotime($a['date']) - strtotime($b['date']);
      });
      $ordered_transaction_histories = end($ordered_transaction_histories);
      $last_status = $ordered_transaction_histories['status'];
    } else {
      $last_status = $ordered_transaction_histories[0]['status'];
    }

    return $last_status;
}

function tc_ipn_callback(){
  global $wpdb;
  if (($_REQUEST['reference'] != '') && ($_REQUEST['merchant'] != '')) {
    $tc_id = $_REQUEST['reference'];
    $api_key = get_option('totalcoin_apikey');
    $tc = new TotalCoinAPI("", $api_key);
    $data = $tc->get_ipn_info($tc_id);
    if ($data['IsOk']) {
        $order_status = tc_get_last_order_status_from_transaction_history($data['Response']['TransactionHistories']);
        $order_id = $data['Response']['MerchantReference'];
        switch ($order_status) {
          case 'Approved':
            $purchase_log_sql = "UPDATE `".WPSC_TABLE_PURCHASE_LOGS."` SET transactid = ".$tc_id.", processed = 3, notes = 'La orden ha sido autorizada, se está esperando la liberación del pago.' WHERE id= '".$order_id."' LIMIT 1";
            break;
          case 'InProccess':
            $purchase_log_sql = "UPDATE `".WPSC_TABLE_PURCHASE_LOGS."` SET transactid = ".$tc_id.", processed = 2, notes = 'La orden está siendo procesada.' WHERE id= '".$order_id."' LIMIT 1";
            break;
          case 'Rejected':
            $purchase_log_sql = "UPDATE `".WPSC_TABLE_PURCHASE_LOGS."` SET transactid = ".$tc_id.", processed = 6, notes = 'Pago rechazado por TotalCoin, contactar al cliente.' WHERE id= '".$order_id."' LIMIT 1";
            break;
          default:
            $purchase_log_sql = "UPDATE `".WPSC_TABLE_PURCHASE_LOGS."` SET transactid = '".$tc_id."', processed = 2, notes = 'La orden ha sido pagada. El dinero ya se encuentra disponible.' WHERE id= '".$order_id."' LIMIT 1";
        }
        $purchase_log = $wpdb->get_results($purchase_log_sql,ARRAY_A);
    }
  }
}

add_action('init', 'tc_ipn_callback');
