<?php

$path_to_root = "../..";
$page_security = 'SA_SALESTRANSVIEW';
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/includes/ui/ui_input.inc");
include_once($path_to_root . "/includes/ui/ui_lists.inc");
include_once($path_to_root . "/includes/ui/ui_globals.inc");
include_once($path_to_root . "/includes/ui/ui_controls.inc");
include_once($path_to_root . "/includes/ui/items_cart.inc");
include_once($path_to_root . "/includes/data_checks.inc");


$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();

page(_($help_context = "Process Transactions"), @$_GET['popup'], false, "", $js);

$optypes = array(
);

include_once($path_to_root . "/modules/ksf_modules_common/defines.inc.php");
/**
$trans_types_readable = array( 
	ST_JOURNAL => "Journal Entry",
	ST_BANKPAYMENT => "Bank Payment",
	ST_BANKDEPOSIT => "Bank Deposit",
	ST_BANKTRANSFER => "Bank Transfer",
	ST_SALESINVOICE => "Sales Invoice",
	ST_CUSTCREDIT => "Customer Credit",
	ST_CUSTPAYMENT => "Customer Payment",
	ST_CUSTDELIVERY => "Customer Delivery",
	ST_LOCTRANSFER => "Location Transfer",
	ST_INVADJUST => "Inventory Adjustment",
	ST_PURCHORDER => "Purchase Order",
	ST_SUPPINVOICE => "Supplier Invoice",
	ST_SUPPCREDIT => "Supplier Credit",
	ST_SUPPAYMENT => "Supplier Payment",
	ST_SUPPRECEIVE => "Supplier Received",
	ST_WORKORDER => "Work Order",
	ST_MANUISSUE => "Manufacturing Issue",
	ST_MANURECEIVE => "Manufacturing Receive",
	ST_SALESORDER => "Sales Order",
	ST_SALESQUOTE => "Sales Quote",
	ST_COSTUPDATE => "Cost Update",
	ST_DIMENSION => "Dimension",
);
**/



//---------------------------------------------------------------------------------
//--------------Unset (Reset) a Transaction----------------------------------------
//---------------------------------------------------------------------------------
// actions
unset($k, $v);
	 //display_notification( print_r( $_POST, true ) . "\n" );
if( isset( $_POST['UnsetTrans'] ) )
{
	 	//display_notification( "Disassociate " . print_r( $_POST['UnsetTrans'], true ) );
	foreach( $_POST['UnsetTrans'] as $key => $value )
	{
	 	//display_notification( "Key/Value " . print_r( $key, true ) . ":" . print_r( $value, true ) );
		$unset=$key;
		//value is "Unset Transaction"
		$cids = array();
		reset_transactions($unset, $cids, 0, 0,0 );
	 	display_notification( "Disassociated $unset from $id"  );
	}
}
/*----------------------------------------------------------------------------------------------*/
/*------------------------Add Customer----------------------------------------------------------*/
/*----------------------------------------------------------------------------------------------*/
if (isset($_POST['AddCustomer'])) {
	foreach( $_POST['AddCustomer'] as $key => $value )
	{
		$AddCustomerKey = $key;
	}
	 //display_notification( print_r( $_POST['AddCustomer'], true )  );
	 //display_notification( print_r( $AddCustomerKey . "::" . $_POST["vendor_short_$AddCustomerKey"]  . "::" . $_POST["vendor_long_$AddCustomerKey"], true )  );
	 $trz = get_transaction($key);
	 //display_notification( print_r( $trz, true )  );
	$id = my_add_customer( $trz );
	if( $id > 0 )
	{
		//	display_notification( __FILE__ . "::" . __LINE__ );
	 	display_notification( "Created Customer ID $id"  );
	} else
	{
		  //  display_notification( __FILE__ . "::" . __LINE__ );
	 	display_warning( "Created Customer ID $id"  );
	}
}
/*----------------------------------------------------------------------------------------------*/
/*-------------------Add Vendor-----------------------------------------------------------------*/
/*----------------------------------------------------------------------------------------------*/
if (isset($_POST['AddVendor'])) {
	foreach( $_POST['AddVendor'] as $key => $value )
	{
		$AddVendorKey = $key;
	}
	 //display_notification( print_r( $_POST['AddVendor'], true )  );
	 //display_notification( print_r( $AddVendorKey . "::" . $_POST["vendor_short_$AddVendorKey"]  . "::" . $_POST["vendor_long_$AddVendorKey"], true )  );
	 $trz = get_transaction($key);
	 //display_notification( print_r( $trz, true )  );
	$id = add_vendor( $trz );
	if( $id > 0 )
	{
	 	display_notification( "Created Supplier ID $id"  );
	} else
	{
	 	display_warning( "Created Supplier ID $id"  );
	}
}
/*----------------------------------------------------------------------------------------------*/
/*-------------------Process Transaction--------------------------------------------------------*/
/*----------------------------------------------------------------------------------------------*/
if (isset($_POST['ProcessTransaction'])) {
	//display_notification( __LINE__ . "::" .  print_r( $_POST, true ));
//20240208 EACH is depreciated.  Should rewrite with foreach
	list($k, $v) = each($_POST['ProcessTransaction']);	//K is index.  V is "process/..."
	if (isset($k) && isset($v) && isset($_POST['partnerType'][$k])) 
	{
			//display_notification( __FILE__ . "::" . __LINE__ . ":: k:" . print_r( $k, true ) . " :: v:" . print_r( $v, true ) . ":: Partner Type: " .  print_r( $_POST['partnerType'][$k], true ) );
		//check params
		$error = 0;
		if ( ! isset( $_POST["partnerId_$k"] ) ) 
		{
			$Ajax->activate('doc_tbl');
			display_error('missing partnerId');
			$error = true;
		}
	
		if (!$error) {
			//	display_notification( __FILE__ . "::" . __LINE__ );
			$tid = $k;
			//time to gather data about transaction
			//load $tid
			//	display_notification( __FILE__ . "::" . __LINE__ );
			$trz = get_transaction($tid);
			//	display_notification( __FILE__ . "::" . __LINE__ );
			//display_notification('<pre>'.print_r($trz,true).'</pre>');
	
			//check bank account
			//	display_notification( __FILE__ . "::" . __LINE__ );
			$our_account = get_bank_account_by_number($trz['our_account']);
			//	display_notification( __FILE__ . "::" . __LINE__ );
			if (empty($our_account)) 
			{
				$Ajax->activate('doc_tbl');
				display_error('the bank account <b>'.$trz['our_account'].'</b> is not defined in Bank Accounts');
				$error = 1;
			}
			//display_notification('<pre>'.print_r($our_account,true).'</pre>');
		}
		if (!$error) {
				//display_notification( __FILE__ . "::" . __LINE__ );
			//get charges
			$chgs = array();
			$_cids = array_filter(explode(',', $_POST['cids'][$tid]));
			foreach($_cids as $cid) {
			$chgs[] = get_transaction($cid);
			}
			//display_notification("tid=$tid, cids=`".$_POST['cids'][$tid]."`");
			//display_notification("cids_array=".print_r($_cids,true));
	
			//now sum up
			//now group data from tranzaction
			$amount = $trz['transactionAmount'];
			$charge = 0;
			foreach($chgs as $t) {
			$charge += $t['transactionAmount'];
			}
	
			//display_notification("amount=$amount, charge=$charge");
			//display_notification("partnerType=".$_POST['partnerType'][$k]);
			$pid = "partnerId_" . $k;
			//display_notification( "partner=".$_POST[ $pid ] );
			$partnerId = $_POST[ $pid ];
	
				//display_notification( __FILE__ . "::" . __LINE__ );
			switch(true) {
	/*************************************************************************************************************/
				//TODO:
				//	See if there is a Purchase Order with the right total.  If so, convert to Invoice
				//	See if there is an invoice with the right date and total.  If so Allocate the Payment.
				//	If there isn't, then create a Purchase Order.
				//		I want to write a "recurring order" similar to on the Sales side.
				//			i.e. Walmart is almost always groceries.
				//			 No Frills R is Pharmacy
				//			 Nissan is always truck
				//	Auto match Vendor (from CC data) to Suppliers.  
				//		Auto Create a supplier if doesn't exist
				//			We get name, address, etc from CC statements.
		            case ($_POST['partnerType'][$k] == 'SP'):
					$trans_no = 0;	//NEW.  A number would be an update - leads to voiding of a bunch of stuff and then redo-ing.
		                    if( $trz['transactionDC'] == 'D' )
		                    {
		                            //Normal SUPPLIER PAYMENT
					$trans_type = ST_SUPPAYMENT;
		                		do {
		                    			$reference = $Refs->get_next($trans_type);
		                		} while(!is_new_reference($reference, $trans_type));
	
					//purchasing/includes/db/supp_payment_db.inc
					// write_supp_payment($trans_no, $supplier_id, $bank_account, $date_, $ref, $supp_amount, $supp_discount, $memo_, $bank_charge=0, $bank_amount=0)
						$payment_id = write_supp_payment( $trans_no, $partnerId, $our_account['id'], sql2date($trz['valueTimestamp']), $reference, user_numeric($trz['transactionAmount']), 0, $trz['transactionTitle'], user_numeric($charge), 0);
						//display_notification("payment_id = $payment_id");
	/***/
					$counterparty_arr = get_trans_counterparty( $payment_id, $trans_type );
				display_notification( __FILE__ . "::" . __LINE__ . print_r( $counterparty_arr, true ) );
	/***/
						//update trans with payment_id details
						if ($payment_id) {
							update_transactions($tid, $_cids, $status=1, $payment_id, $trans_type, false, true,  "SP", $partnerId );
							update_partner_data($partner_id = $partnerId, $partner_type = PT_SUPPLIER, $partner_detail_id = ANY_NUMERIC, $account = $trz['account']);
							display_notification('Supplier Payment Processed:' . $payment_id );

							//While we COULD attach to a Supplier Payment, we don't see them in the P/L drill downs.  More valuable to attach to the related Supplier Invoice
							//display_notification("<a href='http://fhsws002.ksfraser.com/infra/accounting/admin/attachments.php?filterType=" . ST_PAYMENT . "&trans_no=" . $payment_id . "'>Attach Document</a>" );
							//Display a link to the transaction
							//	http://192.168.0.66/infra/accounting/gl/view/gl_trans_view.php?type_id=22&trans_no=227
							display_notification("<a href='../../gl/view/gl_trans_view.php?type_id=" . $trans_type . "&trans_no=" . $payment_id . "'>View Payment</a>" );
							//display_notification("<a href='../../gl/view/gl_trans_view.php?type_id=" . $trans_type . "&trans_no=" . $trans[1] . "'>View Entry</a>" );
		                    		}
					}
		                    else
		                    if( $trz['transactionDC'] == 'C' )
		                    {
					//FA Native creates this as a Supplier Credit Note -> BANK DEPOSIT
					//http://fhsws002.ksfraser.com/infra/accounting/gl/view/gl_deposit_view.php?trans_no=4
					//vs
					//http://fhsws002.ksfraser.com/infra/accounting/purchasing/view/view_supp_payment.php?trans_no=183
					//Needs to be a BANK DEPOSIT in order for the payment to be recognized for allocation.
					// gl/gl_bank.php?NewDeposit=Yes
	
		                            //SUPPLIER REFUND
					$trans_type = ST_BANKDEPOSIT;
					$partner_type = PT_SUPPLIER;
					$partner_detail_id = ANY_NUMERIC;
					$payment_id = 0;
	
		                            $trz['transactionAmount'] = $trz['transactionAmount'] * -1;
						//display_notification("Reference = $reference");
					$cart = new items_cart($trans_type);
						$cart->order_id = $trans_no;
		            		$cart->tran_date = new_doc_date();
		            		if (!is_date_in_fiscalyear($cart->tran_date))
		                    		$cart->tran_date = end_fiscalyear();
		                		do {
		                    		$reference = $Refs->get_next( $trans_type );
							//$Refs->get_next($cart->trans_type, null, $cart->tran_date);
		                		} while(!is_new_reference($reference, $trans_type ));
					$cart->reference = $reference;
	
					while (count($args) < 10) $args[] = 0;
		    			$args = (object)array_combine( array( 'trans_no', 'supplier_id', 'bank_account', 'date_', 'ref', 'bank_amount', 'supp_amount', 'supp_discount', 'memo_', 'bank_charge'), $args);
	
					begin_transaction();
					hook_db_prewrite( $args, $trans_type );
	/* */
					//$supplier_accounts = get_supplier_accounts($partnerId);
					$supplier_accounts = get_supplier($partnerId);	//Does this give us the dimensions?
					$cart->add_gl_item(
						$supplier_accounts["payable_account"],
						$supplier_accounts["dimension_id"],
						$supplier_accounts["dimension2_id"],
						user_numeric($trz['transactionAmount']),
						$trz['transactionTitle']
					);
	
					    if ( $cart->count_gl_items() < 1) {
		            			display_error(_("You must enter at least one payment line."));
		    			}
		    			if ( $cart->gl_items_total() == 0.0) {
		            			display_error(_("The total bank amount cannot be 0."));
		    			}
	
					$payment_id = write_bank_transaction(
		            			$cart->trans_type, 
						$cart->order_id, 
						$our_account['id'],
		            			$cart, 
						sql2date( $trz['valueTimestamp'] ),
		            			$partner_type,
						$partnerId, 
						$partner_detail_id,
		            			$cart->reference,
						$trz['transactionTitle'],
						true, 
						number_format2(abs( $cart->gl_items_total() ) )
					);
	/* */
					//write_bank_trans returns an array with element 0 = trans_type, and 1 = trans_no
	
						//update trans with payment_id details
					if ($payment_id) 
					{
	/***/
						$counterparty_arr = get_trans_counterparty( $payment_id, $trans_type );
						display_notification( __FILE__ . "::" . __LINE__ . print_r( $counterparty_arr, true ) );
	/***/
						update_transactions($tid, $_cids, $status=1, $payment_id[1], $trans_type, false, true,  "SP", $partnerId );
						update_partner_data( $partnerId, $partner_type, $partner_detail_id, $account = $trz['account']);
						display_notification('Supplier Refund Processed:' . print_r( $payment_id, true ) );
						//While we COULD attach to a Supplier Payment, we don't see them in the P/L drill downs.  More valuable to attach to the related Supplier Invoice
						//display_notification("<a href='http://fhsws002.ksfraser.com/infra/accounting/admin/attachments.php?filterType=" . ST_PAYMENT . "&trans_no=" . $payment_id . "'>Attach Document</a>" );
						display_notification("<a href='../../gl/view/gl_trans_view.php?type_id=" . $trans_type . "&trans_no=" . $payment_id[1] . "'>View Entry</a>" );
		                    	}
					hook_db_postwrite($args, $trans_type );
					commit_transaction();
		                    }
	/***/
			break;
	/*************************************************************************************************************/
				//TODO:
				//	Match customers to records
				//		i.e. E-Transfer from XXYYY (CIBC statements)
			case ($_POST['partnerType'][$k] == 'CU' && $trz['transactionDC'] == 'C'):
					display_notification( __FILE__ . "::" . __LINE__ . print_r( $_POST, true ) );
				//20240211 Works.  Not sure why BANKDEPOSIT vice CUSTPAYMENT in original module.
				$trans_type = ST_BANKDEPOSIT;
				$trans_type = ST_CUSTPAYMENT;
				//insert customer payment into database
				do {
					$reference = $Refs->get_next($trans_type);
				} while(!is_new_reference($reference, $trans_type));
				//20240304 The BRANCH doesn't seem to get selected though.
				if( strlen( $trz['transactionTitle'] ) < 4 )
				{
					if( strlen( $trz['memo'] ) > 0 ) 
					{
						$trz['transactionTitle'] .= " : " . $trz['memo'];
					}
				}
/** Mantis 3018
	We are trying to allocate Customer Payments against a specific invoice
*/
				$deposit_id = my_write_customer_payment(
					$trans_no = 0, $customer_id=$partnerId, $branch_id=$_POST["partnerDetailId_$k"], $bank_account=$our_account['id'],
					$date_ = sql2date($trz['valueTimestamp']), $reference, user_numeric($trz['transactionAmount']),
					$discount=0, $trz['transactionTitle'], $rate=0, user_numeric($charge), $bank_amount=0, $trans_type);

				//update trans with payment_id details
				if ($deposit_id) {
					$invoice_no = $_POST['Invoice'];
	/***/
					$counterparty_arr = get_trans_counterparty( $deposit_id, $trans_type );
					//display_notification( __FILE__ . "::" . __LINE__ . print_r( $counterparty_arr, true ) );
	/***/
					$fcp = new fa_customer_payment();
					$fcp->set( "trans_date", $valueTimestamp );
					$fcp->set( "trans_type", $trans_type );
					$fcp->set( "payment_id", $deposit_id );
					$fcp->write_allocation();
					
					update_transactions($tid, $_cids, $status=1, $deposit_id, $trans_type, false, true,  "CU", $partnerId);
					update_partner_data($partnerId, PT_CUSTOMER, $_POST["partnerDetailId_$k"], $trz['memo']);
					update_partner_data($partnerId, $trans_type, $_POST["partnerDetailId_$k"], $trz['memo']);
					display_notification('Customer Payment/Deposit processed');
					display_notification("<a href='../../gl/view/gl_trans_view.php?type_id=" . $trans_type . "&trans_no=" . $deposit_id . "'>View Entry</a>" );
				}
			break;
	/*************************************************************************************************************/
			case ($_POST['partnerType'][$k] == 'QE'):
				$trans_type = ($trz['transactionDC'] == 'D')?ST_BANKPAYMENT:ST_BANKDEPOSIT;
				$cart = new items_cart($trans_type);
					$cart->order_id = 0;
					$cart->original_amount = $trz['transactionAmount'] + $charge;
					do {
						$cart->reference = $Refs->get_next($cart->trans_type);
					} while(!is_new_reference($cart->reference, $cart->trans_type));
					$cart->tran_date = sql2date($trz['valueTimestamp']);
					if (!is_date_in_fiscalyear($cart->tran_date)) 
					{
						$cart->tran_date = end_fiscalyear();
					}
					//this loads the QE into cart!!!
				try {
					if( strlen( $trz['transactionTitle'] ) < 4 )
					{
						if( strlen( $trz['memo'] ) > 0 ) 
						{
							$trz['transactionTitle'] .= " : " . $trz['memo'];
						}
					}
					//display_notification('QE TRANS: ' . print_r( $trz, true ) );

					//function qe_to_cart(&$cart, $id, $base, $type, $descr='')
					$qe_memo = "A:" . $our_account['bank_account_name'] . ":" . $trz['account_name'] . " M:" . $trz['account'] . ":" . $trz['transactionTitle'] . ": " . $trz['transactionCode'];
					$rval = qe_to_cart($cart, $partnerId, $trz['transactionAmount'], ($trz['transactionDC']=='C') ? QE_DEPOSIT : QE_PAYMENT, $qe_memo );
				} catch( Exception $e )
				{
					display_notification('RVAL Exception' . print_r( $e, true ) );
				}
				// function add_gl_item($code_id, $dimension_id, $dimension2_id, $amount, $memo='', $act_descr=null, $person_id=null, $date=null)
	//TODO:
	//	Config which account to log these in
	//	Conig whether to log these.
				$cart->add_gl_item( '0000', 0, 0, 0.01, 'TransRef::'.$trz['transactionCode'], "Trans Ref");
				$cart->add_gl_item( '0000', 0, 0, -0.01, 'TransRef::'.$trz['transactionCode'], "Trans Ref");
				$total = $cart->gl_items_total();
				if ($total != 0) 
				{
					//need to add the charge to the cart
					$cart->add_gl_item(get_company_pref('bank_charge_act'), 0, 0, $charge, 'Charge/'.$trz['transactionTitle']);
					//process the transaction
					begin_transaction();
	
					$trans = write_bank_transaction(
						$cart->trans_type, $cart->order_id, $our_account['id'],
						$cart, sql2date($trz['valueTimestamp']),
							PT_QUICKENTRY, $partnerId, 0,
							$cart->reference, $qe_memo, true, null);
							//$cart->reference, $trz['transactionTitle'], true, null);
	
					$counterparty_arr = get_trans_counterparty( $trans[1], $trans_type );
					//display_notification( __FILE__ . "::" . __LINE__ . print_r( $counterparty_arr, true ) );
				/***
					Array ( 	[0] => 
							Array ( 	[counter] => 171128 
									[type] => 1 
									[type_no] => 602 
									[tran_date] => 2023-06-22 
									[account] => 8523.2 
									[memo_] => Groceries 
									[amount] => 41.78 
									[dimension_id] => 3 
									[dimension2_id] => 10 
									[person_type_id] => 
									[person_id] => 
									[account_name] => Groceries 
									[reference] => 517/2023 
									[real_name] => Administrator 
									[doc_date] => 2023-06-22 
									[supp_reference] => 
							) 
							[1] => Array ( 	... ) 
							[2] => Array ( ...  ) 
							[3] => Array ( ...  ) 
					)
						//partnerID in this case is the QE index.
					set_bank_partner_data( $our_account['id'], $trans_type, $partnerId, $trz['transactionTitle'] );
				***/
					update_transactions($tid, $_cids, $status=1, $trans[1], $trans_type, false, true, "QE", $partnerId );
					commit_transaction();
					//Don't want this preventing the commit!
					set_bank_partner_data( $our_account['id'], $trans_type, $partnerId, $trz['transactionTitle'] );
					//ST_BANKPAYMENT or ST_BANKDEPOSIT
					
					//Let User attach a document
					display_notification("<a href='http://fhsws002.ksfraser.com/infra/accounting/admin/attachments.php?filterType=" . $trans_type . "&trans_no=" . $trans[1] . "'>Attach Document</a>" );
					//Let the user view the created transaction
					//http://192.168.0.66/infra/accounting/gl/view/gl_trans_view.php?type_id=0&trans_no=10825
					display_notification("<a href='../../gl/view/gl_trans_view.php?type_id=" . $trans_type . "&trans_no=" . $trans[1] . "'>View Entry</a>" );

	
					} 
				else 
				{
					display_notification('CART4B ' . print_r( $cart, true ) );
					display_notification("QE not loaded: rval=$rval, k=$k, total=$total");
					//display_notification("debug: <pre>".print_r($_POST, true).'</pre>');
					}
			break;
	/*************************************************************************************************************/
			case ($_POST['partnerType'][$k] == 'BT'):
				$inc = require_once( '../ksf_modules_common/class.fa_bank_transfer.php' );
				if( $inc )
				{
					$bttrf = new fa_bank_transfer();
					try
					{
						$bttrf->set( "trans_type", ST_BANKTRANSFER );
						if( $trz['transactionDC'] == 'C' OR $trz['transactionDC'] == 'B' )
						{
							//display_notification( __LINE__ . " :: " . print_r( $our_account, true )  );
							$bttrf->set( "ToBankAccount", $our_account['id'] );
							$pid = 'partnerId_' . $tid;
							//display_notification( __LINE__ . " :: " . print_r( $_POST[$pid], true )  );
							$bttrf->set( "FromBankAccount", $_POST[$pid] );
						}
						else
						if( $trz['transactionDC'] == 'D' )
						{
							//On a Debit, the bank accounts are reversed.
							//display_notification( __LINE__ . " :: " . print_r( $our_account, true )  );
							$bttrf->set( "FromBankAccount", $our_account['id'] );
							$pid = 'partnerId_' . $tid;
							//display_notification( __LINE__ . " :: " . print_r( $_POST[$pid], true )  );
							$bttrf->set( "ToBankAccount", $_POST[$pid] );
						}
						$bttrf->set( "amount", $trz['transactionAmount'] );
						$bttrf->set( "trans_date", $trz['valueTimestamp'] );
						$bttrf->set( "memo_", $trz['transactionTitle'] . "::" . $trz['transactionCode'] );
						$bttrf->set( "target_amount", $trz['transactionAmount'] );
					}
					catch( Exception $e )
					{
						//display_notification( __FILE__ . "::" . __LINE__ . ":" . $e->getMessage() );
						break;
					}
					try
					{
						$bttrf->getNextRef();
						//$bttrf->trans_date_in_fiscal_year();
					}
					catch( Exception $e )
					{
						break;
					}
					begin_transaction();
					//can_process is baked into add_bank_transfer
					$bttrf->add_bank_transfer();
					$counterparty_arr = get_trans_counterparty( $bttrf->get( "trans_no" ), $bttrf->get( "trans_type" ) );
						////display_notification( __FILE__ . "::" . __LINE__ . print_r( $counterparty_arr, true ) );
					$trans_no = $bttrf->get( "trans_no" );
					$trans_type = $bttrf->get( "trans_type" );
					update_transactions( $tid, $_cids, $status=1, $trans_no, $trans_type, false, true,  "BT", $partnerId );
					//update_transactions( $tid, $_cids, $status=1, $bttrf->get( "trans_no" ), $bttrf->get( "trans_type" ), false, true );
					display_notification("<a href='../../gl/view/gl_trans_view.php?type_id=" . $trans_type . "&trans_no=" . $trans_no . "'>View Entry</a>" );
					set_bank_partner_data( $bttrf->get( "FromBankAccount" ), $bttrf->get( "trans_type" ), $bttrf->get( "ToBankAccount" ), $trz['memo'] );	//Short Form
								//memo/transactionTitle holds the reference number, which would be unique :(
					commit_transaction();
				}
				else
				{
						//display_notification( __LINE__  );
				}
			break;
	/*************************************************************************************************************/
			case ($_POST['partnerType'][$k] == 'MA'):
				$counterparty_arr = get_trans_counterparty( $_POST['Existing_Entry'], $_POST['Existing_Type'] );
					display_notification( __FILE__ . "::" . __LINE__ . print_r( $counterparty_arr, true ) );
				update_transactions($tid, $_cids, $status=1, $_POST['Existing_Entry'], $_POST['Existing_Type'], true, false, null, "" );
				display_notification("<a href='../../gl/view/gl_trans_view.php?type_id=" . $_POST['Existing_Type'] . "&trans_no=" . $_POST['Existing_Entry'] . "'>View Entry</a>" );
				set_partner_data( $counterparty_arr['person_type'], $_POST['Existing_Type'], $counterparty_arr['person_type_id'], $trz['memo'] );	//Short Form
				display_notification("Transaction was manually settled " . print_r( $_POST['Existing_Type'], true ) . ":" . print_r( $_POST['Existing_Entry'], true ) );
			break;
	/*************************************************************************************************************/
				//TODO:
				//	*When the Match score is too low, switching to MATCH still gets overwritten the next ajax load
				//	*Test what happens if there are 3+ matches
				//		right now it doesn't auto match, because we don't have a way to select the trans type/number
				//		Sort by scoring.  Go with highest?
				//20240214 Matching Works.  As long as score is high enough, can "process".
			case ($_POST['partnerType'][$k] == 'ZZ'):
				//display_notification("Entry Matched against an existing Entry (LE/Cp/SP/...)");
				//display_notification(__FILE__ . "::" . __LINE__ . ":" . " Trans Type and No: ".print_r( $_POST["trans_type_$tid"], true) . ":" . print_r( $_POST["trans_no_$tid"], true ) );
					$counterparty_arr = get_trans_counterparty( $_POST["trans_no_$tid"], $_POST["trans_type_$tid"] );
				//display_notification( __FILE__ . "::" . __LINE__ . print_r( $counterparty_arr, true ) );
				if( isset( $_POST["memo_$tid"] ) AND strlen ($_POST["memo_$tid"]) > 0 )
				{
					$memo = $_POST["memo_$tid"];
				}
				else
				if( isset( $_POST["title_$tid"] ) AND strlen ($_POST["title_$tid"]) > 0 )
				{
					$memo = $_POST["title_$tid"];
				}
				else
				{
					$memo = "";
				}
				foreach( $counterparty_arr as $row )
				{
					//display_notification(__FILE__ . "::" . __LINE__  );
					//Any given transaction should only have 1 person associated.
					if( isset( $row['person_id'] ) )
					{
						if( is_numeric( $row['person_id'] ) )
						{
							$person_id = $row['person_id'];
						}
						if( is_numeric( $row['person_type_id'] ) )
						{
							$person_type_id = $row['person_type_id'];
						}
					}
				}
					//display_notification(__FILE__ . "::" . __LINE__  );
					update_transactions( $tid, $_cids, $status=1, $_POST["trans_no_$tid"], $_POST["trans_type_$tid"], true, false,  "ZZ", $partnerId );
					//display_notification(__FILE__ . "::" . __LINE__  );
					display_notification("Transaction was MATCH settled " .  $_POST["trans_type_$tid"] . "::" . $_POST["trans_no_$tid"] . "::" );
				set_partner_data( $person_type, $_POST["trans_type_$tid"], $person_type_id, $memo );	
					//display_notification(__FILE__ . "::" . __LINE__  );
			break;
			} // end of switch
			$Ajax->activate('doc_tbl');
		} //end of if !error

	} // end of is set....
} //end of is isset(post[processTranzaction])

/*----------------------------------------------------------------------------------------------*/
/*----------------------------------------------------------------------------------------------*/
/*----------------------------------------------------------------------------------------------*/

/*
// check whether a transaction is ignored
unset($k, $v);
list($k, $v) = each($_POST['IgnoreTrans']);
if (isset($k) && isset($v)) {
		//display_notification( __FILE__ . "::" . __LINE__ );
	updateTrans($_POST['trans_id'][$k], $_POST['charge_id'][$k], TR_MAN_SETTLED);
	$Ajax->activate('doc_tbl');
	display_notification('Manually processed');
}
*/
/************************************************************************************************************************/
/**********************************************  GUI  *******************************************************************/
/************************************************************************************************************************/

// search button pressed
if (get_post('RefreshInquiry')) {
	$Ajax->activate('doc_tbl');
}

//SC: check whether a customer has been changed, so that we can update branch as well
// as there a user can click on one submit button only, there is no need for multiple check
unset($k, $v);
if (isset($_POST['partnerId'])) {
			//display_notification( __FILE__ . "::" . __LINE__ );
	list($k, $v) = each($_POST['partnerId']);
	if (isset($k) && isset($v)) {
		$Ajax->activate('doc_tbl');
	}
}

//SC: 05.10.2012: whether post['partnerType'] exists, refresh
if (isset($_POST['partnerType'])) {
	$Ajax->activate('doc_tbl');
}


start_form();

div_start('doc_tbl');

if (1) {
	//------------------------------------------------------------------------------------------------
	// this is filter table
	start_table(TABLESTYLE_NOBORDER);
	start_row();
	if (!isset($_POST['statusFilter']))
		$_POST['statusFilter'] = 0;
	if (!isset($_POST['TransAfterDate']))
		$_POST['TransAfterDate'] = begin_month(Today());

	if (!isset($_POST['TransToDate']))
		$_POST['TransToDate'] = end_month(Today());

	date_cells(_("From:"), 'TransAfterDate', '', null, -30);
	date_cells(_("To:"), 'TransToDate', '', null, 1);
	label_cells(_("Status:"), array_selector('statusFilter', $_POST['statusFilter'], array(0 => 'Unsettled', 1 => 'Settled', 255 => 'All')));

	submit_cells('RefreshInquiry', _("Search"),'',_('Refresh Inquiry'), 'default');
	end_row();
	end_table();

	//if (!@$_GET['popup'])
	//	end_form();


/*************************************************************************************************************/
/***********************************  Transactions  **********************************************************/
/*************************************************************************************************************/
	//------------------------------------------------------------------------------------------------
	// this is data display table
	$trzs = array();
	$vendor_list = get_vendor_list();	//array

	error_reporting(E_ALL);

	$sql = " SELECT t.*, s.account our_account, s.currency from " . TB_PREF . "bi_transactions t LEFT JOIN " . TB_PREF . "bi_statements as s ON t.smt_id = s.id";
	$sql .= " WHERE t.valueTimestamp >= '" . date2sql( $_POST['TransAfterDate'] ) . "' AND t.valueTimestamp < '" . date2sql( $_POST['TransToDate'] ) . "'";
	if( $_POST['statusFilter'] == 0 OR $_POST['statusFilter'] == 1 )
	{
		$sql .= "  AND t.status = '" . $_POST['statusFilter'] . "'";
	}
	$sql .= " ORDER BY t.valueTimestamp ASC";

	try 
	{
		$res = db_query($sql, 'unable to get transactions data'); 
		//The following shows how many rows/columns of results there are but without doint the fetch, just the mysql_results object.
		//display_notification( __FILE__ . "::" . __LINE__ . " " . print_r( $res, true ) );
	} catch( Error $e )
	{
			display_notification( __FILE__ . "::" . __LINE__ . " " . $e->getMessage() );
	}
/*************************************************************************************************************/
	start_table(TABLESTYLE, "width='100%'");
	table_header(array("Transaction Details", "Operation/Status"));

	//load data
	$row_count = 0;
	while($myrow = db_fetch($res)) 
	{
		$row_count++;
		//display_notification( __FILE__ . "::" . __LINE__ );
		$trz_code = $myrow['transactionCode'];
		if( !isset( $trzs[$trz_code] ) ) 
		{
				$trzs[$trz_code] = array();
		}
		$trzs[$trz_code][] = $myrow;
	}
	
	//This foreach loop should probably be rolled up into the WHILE loop above.
	foreach($trzs as $trz_code => $trz_data) 
	{
		//try to match something, interpreting saved info if status=TR_SETTLED
		//$minfo = doMatching($myrow, $coyBankAccounts, $custBankAccounts, $suppBankAccounts);
	
		//now group data from tranzaction
		$tid = 0;
		$cids = array();
	
		//bring trans details
		$has_trz = 0;
		$amount = 0;
		$charge = 0;
	
		foreach($trz_data as $idx => $trz) 
		{
				//display_notification( __FILE__ . "::" . __LINE__ );
		 	$transactionDC = $trz['transactionDC'];
			$our_account = $trz['our_account'];
			$valueTimestamp = $trz['valueTimestamp'];
				//display_notification( __FILE__ . "::" . __LINE__ );
			try {
				//display_notification( __FILE__ . "::" . __LINE__ );
				$bankAccount = shorten_bankAccount_Names( $trz['accountName'] );
		 		//display_notification( __FILE__ . ":" .  __LINE__ . " " . print_r( $bankAccount, true )  );
			}
			catch( Exception $e )
			{
				display_notification( __FILE__ . "::" . __LINE__ . ":" . $e->getMessage() );
				$bankAccount = $trz['accountName'];
			}
	
			$bankAccountName = $trz['accountName'];
			if( strlen( $trz['transactionTitle'] ) < 4 )
			{
				if( strlen( $trz['memo'] ) > strlen( $trz['transactionTitle'] ) )
				{
					$trz['transactionTitle'] .= $trz['memo'];
				}
			}
				//display_notification( __FILE__ . "::" . __LINE__ );
			$transactionTitle = $trz['transactionTitle'];
			$currency = $trz['currency'];
			$status = $trz['status'];
			$tid = $trz['id'];
			$fa_trz_type = $trz['fa_trans_type'];
			$fa_trz_no = $trz['fa_trans_no'];
	
			//display_notification( __FILE__ . "::" . __LINE__ );
			if ($trz['transactionType'] != 'COM') {
				//display_notification( __FILE__ . "::" . __LINE__ );
				$has_trz = 1;
				$amount = $trz['transactionAmount'];
			} else if ($trz['transactionType'] == 'COM') 
			{
				//display_notification( __FILE__ . "::" . __LINE__ );
				$amount += $trz['transactionAmount'];
			}
		}	//foreach trz_data
		$cids = implode(',', $cids);


		start_row();
		echo '<td width="50%">';
		
		start_table(TABLESTYLE2, "width='100%'");
		label_row("Trans Date (Event Date):", $valueTimestamp . " :: (" . $trz['entryTimestamp'] . ")" , "width='25%' class='label'");
		switch( $transactionDC )
		{
			case 'C':
				label_row("Trans Type:", "Credit");
			break;
			case 'D':
				label_row("Trans Type:", "Debit");
			break;
			case 'B':
				label_row("Trans Type:", "Bank Transfer");
			break;
		}
		$bank = get_bank_account_by_number( $our_account );
			//Info from 0_bank_accounts
			//	Account Name
			//	Type
			//	Currency
			//	GL Account
			//	Bank
			//	Number
			//	Address
		//var_dump( $bank );
		/*
			Array ( [account_code] => 1061
				[account_type] => 0
				[bank_account_name] => CIBC Savings account 
				[bank_account_number] => 00449 12-93230 
				[bank_name] => CIBC 
				[bank_address] => 
				[bank_curr_code] => CAD 
				[dflt_curr_act] => 1 
				[id] => 1 
				[bank_charge_act] => 5690 
				[last_reconciled_date] => 0000-00-00 00:00:00 
				[ending_reconcile_balance] => 0 
				[inactive] => 0 )
		*/
		label_row("Our Bank Account - (Account Name)(Number):", $our_account . ' - ' . $bank['bank_name'] . " (" . $bank['bank_account_name'] . ")(" . $bank['account_code'] . ")"  );
		label_row("Other account:", $bankAccount . ' / '. $bankAccountName);
		label_row("Amount/Charge(s):", $amount.' / '.$charge." (".$currency.")");
		label_row("Trans Title:", $transactionTitle);
		if( ! in_array(  trim($bankAccount), $vendor_list['shortnames'] ) )
		{
	
			//display_notification( __FILE__ . "::" . __LINE__ . "::" . " Looked for: //" . trim($bankAccount) . "// but didn't fint it.  :: " . print_r( $vendor_list['shortnames'], true )  );
			
				         //function submit($name, $value, $echo=true, $title=false, $atype=false, $icon=false)
				label_row("Add Vendor", submit("AddVendor[$tid]",_("AddVendor"),false, '', 'default'));
				//label_row("Add Customer", submit("AddCustomer[$tid]",_("AddCustomer"),false, '', 'default'));
			hidden( "vendor_short_$tid", $bankAccount );
			hidden( "vendor_long_$tid", $bankAccountName );
			$matched_supplier = null;
		}
		else
		{
			//IS INARRAY
			$matched_vendor = array_search( trim($bankAccount), $vendor_list['shortnames'], true );
			$matched_supplier = $vendor_list[$matched_vendor]['supplier_id'];
			hidden( 'vendor_id', $matched_vendor );
			label_row("Matched Vendor", print_r( $matched_vendor, true ) . "::" . print_r( $vendor_list[$matched_vendor]['supplier_id'], true ) . "::" . print_r( $vendor_list[$matched_vendor]['supp_name'], true ) );
			//label_row("Add Vendor", submit("AddVendor[$tid]",_("AddVendor"),false, '', 'default'));
		}
		end_table();
		$arr_arr = find_matching_existing( $trz, $bank );

		echo "</td><td width='50%' valign='top'>";
		start_table(TABLESTYLE2, "width='100%'");
		//now display stuff: forms and information

/*************************************************************************************************************/
		if ($status == 1) 
		{
			// the transaction is settled, we can display full details
			label_row("Status:", "<b>Transaction is settled!</b>", "width='25%' class='label'");
			switch ($trz['fa_trans_type']) 
			{
				case ST_SUPPAYMENT:
					label_row("Operation:", "Payment");
					// get supplier info
					
					label_row("Supplier:", $minfo['supplierName']);
					label_row("From bank account:", $minfo['coyBankAccountName']);
				break;
				case ST_BANKDEPOSIT:
					label_row("Operation:", "Deposit");
					//get customer info from transaction details
					$fa_trans = get_customer_trans($fa_trz_no, $fa_trz_type);
					label_row("Customer/Branch:", get_customer_name($fa_trans['debtor_no']) . " / " . get_branch_name($fa_trans['branch_code']));
				break;
				case 0:
					label_row("Operation:", "Manual settlement");
				break;
				default:
					label_row("Status:", "other transaction type; no info yet " . print_r( $trz, true ) );
					$pid = $trz['id'];
				break;
			}
				label_row(	"Unset Transaction Association", submit( 	"UnsetTrans[$pid]", _("Unset Transaction $fa_trans_no"), false, '', 'default' ));
	/*************************************************************************************************************/
		} else {	
			//transaction NOT settled
			// this is a new transaction, but not matched by routine so just display some forms
			if( $transactionDC == 'C' )
				$oplabel = "Depost";
			else
				$oplabel = "Payment";
	
			if( !isset( $_POST['partnerType'][$tid] ) ) 
			{
				switch( $transactionDC )
				{
					case 'C':
							$_POST['partnerType'][$tid] = 'CU';
						$oplabel = "Depost";
					break;
					case 'D':
							$_POST['partnerType'][$tid] = 'SP';
						$oplabel = "Payment";
					break;
					case 'B':
							$_POST['partnerType'][$tid] = 'BT';
						$oplabel = "Bank Transfer";
					break;
				}
			}
	/**/
			if( count( $arr_arr ) > 0 )
			{
				//Rewards (points) might have a split so only 1 count
				//	Walmart
				//	PC Points
				//	Gift Cards (Restaurants, Amazon)
				//but everyone else should have 2
				
				if( count( $arr_arr ) < 3 )
				{
				
					//We matched some JEs
					if( 50 <= $arr_arr[0]['score'] )
					{
						//var_dump( __LINE__ );
						//It was an excellent match
						if( $arr_arr[0]['is_invoice'] )
						{
							//This TRZ is a supplier payment
							//that matches an invoice exactly.
								$_POST['partnerType'][$tid] = 'SP';
						}
						else
								$_POST['partnerType'][$tid] = 'ZZ';
						$oplabel = "MATCH";
					hidden("trans_type_$tid", $arr_arr[0]['type'] );
					hidden("trans_no_$tid", $arr_arr[0]['type_no'] );
						
					}
					else
						var_dump( __LINE__ );
				}
				else
				{
					//If there are 3+ then we need to sort by score and take the highest scored item!
				}
			}
	/**/
			label_row("Operation:", $oplabel, "width='25%' class='label'");
			//label_row("Operation:", (($transactionDC=='C') ? "Deposit" : "Payment"), "width='25%' class='label'");
			label_row("Partner:", array_selector("partnerType[$tid]", $_POST['partnerType'][$tid], $optypes, array('select_submit'=> true)));
	
	/*************************************************************************************************************/
		//3rd cell
			if ( !isset( $_POST["partnerId_$tid"] ) ) 
			{
				$_POST["partnerId_$tid"] = '';
			}
	
			switch($_POST['partnerType'][$tid]) {
			//supplier payment
			case 'SP':
				//propose supplier
				if (empty($_POST["partnerId_$tid"])) 
				{
					$match = search_partner_by_bank_account(PT_SUPPLIER, $bankAccount);
					if (!empty($match)) 
					{
						$_POST["partnerId_$tid"] = $match['partner_id'];
					}
				}
				//			 supplier_list($name, $selected_id=null, $spec_option=false, $submit_on_change=false, $all=false, $editkey = false)
	
				label_row(_("Payment To:"), supplier_list("partnerId_$tid", $matched_supplier, false, false));
				break;
			//customer deposit
			case 'CU':
				//propose customer
				if (empty($_POST["partnerId_$tid"])) {
					$match = search_partner_by_bank_account(PT_CUSTOMER, $bankAccount);
					if (!empty($match)) {
						$_POST["partnerId_$tid"] = $match['partner_id'];
						$_POST["partnerDetailId_$tid"] = $match['partner_detail_id'];
					}
				}
				$cust_text = customer_list("partnerId_$tid", null, false, true);
				if (db_customer_has_branches($_POST["partnerId_$tid"])) {
					$cust_text .= customer_branches_list($_POST["partnerId_$tid"], "partnerDetailId_$tid", null, false, true, true);
				} else {
					hidden("partnerDetailId_$tid", ANY_NUMERIC);
					$_POST["partnerDetailId_$tid"] = ANY_NUMERIC;
				}
				label_row(_("From Customer/Branch:"),  $cust_text);
				//label_row("debug", "customerid_tid=".$_POST["partnerId_$tid"]." branchid[tid]=".$_POST["partnerDetailId_$tid"]);
/** Mantis 3018
 *	List FROM and TO invoices needing payment (allocations) */
				$_GET['customer_id'] = $_POST["partnerId_$tid"];
/* */
				if( @$inc = include_once( '../ksf_modules_common/class.fa_customer_payment.php' ) )
				{
					$fcp = new fa_customer_payment();
					$fcp->set( "trans_date", $valueTimestamp );
					label_row( "Invoices to Pay", $fcp->show_allocatable() );
                                							//function text_input($name, $value=null, $size='', $max='', $title='', $params='')
                                	label_row( (_("Allocate Payment to (1) Invoice")), text_input( "Invoice", 0, 6, '', _("Invoice to Allocate Payment:") ) );
				}
/* */

/* ! Mantis 3018 */
	
			break;
			// Bank Transfer
			case 'BT':	//partnerType
				if (empty($_POST["partnerId_$tid"])) 
				{
					$match = search_partner_by_bank_account(ST_BANKTRANSFER, $bankAccount);
						if (!empty($match)) 
					{
						$_POST["partnerId_$tid"] = $match['partner_id'];
						$_POST["partnerDetailId_$tid"] = $match['partner_detail_id'];
						}
					else
					{
						$_POST["partnerId_$tid"] = ANY_NUMERIC;
					}
				}
			//function bank_accounts_list($name, $selected_id=null, $submit_on_change=false, $spec_option=false)
				 //bank_accounts_list_row( _("From:") , 'bank_account', null, true);
/** Mantis 2963
*	Bank Transfer To/From label */
					if( $transactionDC == 'C' )
					{
						$rowlabel = "Transfer From:"; 
					}
					else
					{
						$rowlabel = "Transfer To:"; 
					}
/** ! Mantis 2963 */
						label_row(	_( $rowlabel ), 
				 		bank_accounts_list( "partnerId_$tid", $_POST["partnerId_$tid"], null, false)
				 		//bank_accounts_list_row( _("From:") , 'bank_account', null, false)
				);
			break;
			// quick entry
			case 'QE':	//partnerType
				//label_row("Option:", "<b>Process via quick entry</b>");
				$qe_text = quick_entries_list("partnerId_$tid", null, (($transactionDC=='C') ? QE_DEPOSIT : QE_PAYMENT), true);
				$qe = get_quick_entry(get_post("partnerId_$tid"));
				$qe_text .= " " . $qe['base_desc'];
			
				label_row("Quick Entry:", $qe_text);
			break;
			case 'MA':
				hidden("partnerId_$tid", 'manual');
				//function array_selector($name, $selected_id, $items, $options=null)
					//value => description
				$opts_arr = array( 
						ST_JOURNAL => "Journal Entry",
						ST_BANKPAYMENT => "Bank Payment",
						ST_BANKDEPOSIT => "Bank Deposit",
						ST_BANKTRANSFER => "Bank Transfer",
						//ST_SALESINVOICE => "Sales Invoice",
						ST_CUSTCREDIT => "Customer Credit",
						ST_CUSTPAYMENT => "Customer Payment",
						//ST_CUSTDELIVERY => "Customer Delivery",
						//ST_LOCTRANSFER => "Location Transfer",
						//ST_INVADJUST => "Inventory Adjustment",
						//ST_PURCHORDER => "Purchase Order",
						//ST_SUPPINVOICE => "Supplier Invoice",
						ST_SUPPCREDIT => "Supplier Credit",
						ST_SUPPAYMENT => "Supplier Payment",
						//ST_SUPPRECEIVE => "Supplier Receiving",
					);
				$name="Existing_Type";
					label_row(_("Existing Entry Type:"), array_selector( $name, 0, $opts_arr ) );
				//function text_input($name, $value=null, $size='', $max='', $title='', $params='')
				label_row( 
					(_("Existing Entry:")), 
					text_input( "Existing_Entry", 0, 6, '', _("Existing Entry:") ) 
				);
			break;
			case 'ZZ':	//partnerType
				//Matched an existing item
				hidden("partnerId_$tid", $arr_arr[0]['type'] );
				hidden("partnerDetailId_$tid", $arr_arr[0]['type_no'] );
				hidden("trans_type_$tid", $arr_arr[0]['type'] );
				hidden("trans_no_$tid", $arr_arr[0]['type_no'] );
				hidden("memo_$tid", $trz['memo'] );
				hidden("title_$tid", $trz['transactionTitle'] );
			break;
			}
			label_row("", submit("ProcessTransaction[$tid]",_("Process"),false, '', 'default'));
	
			//other common info
			hidden("cids[$tid]",$cids);
	
			if( count( $arr_arr ) > 0 )
			{
				$match_html = "";
				$matchcount = 1;
				foreach( $arr_arr as $matchgl )
				{
					//[type] => 0 [type_no] => 8811 [tran_date] => 2023-01-03 [account] => 2620.frontier [memo_] => 025/2023 [amount] => 432.41 [person_type_id] => [person_id] => [account_name] => Auto Loan Frontier (Nissan Finance) [reference] => 025/2023 [score] => 111 [is_invoice]
					if( isset( $matchgl['tran_date'] ) )
					{
							/******************************************************************************************
							* In Savings Account, Customer Payment is a DEBIT.
							* NISSAN is a DEBIT out of Savings in the IMPORT file.  So amount in example should be -
							*
							*Customer Payment is a CREDIT from import file.  Amount should match exact the Bank trans.
							*
							* so if the bank account number matches and adjusted amount matches...
							*****************************************************************************************/
						$match_html .= "<b>$matchcount</b>: ";
						$match_html .= " Transaction " . $trans_types_readable[$matchgl['type']] . ":" . $matchgl['type_no'];
						$match_html .= " Score " . $matchgl['score'] . " ";
						if( strcasecmp( $our_account, $matchgl['account'] ) OR strcasecmp( $bank['bank_account_name'], $matchgl['account'] ) )
						{
							$match_html .= "Account <b>" . $matchgl['account'] . "</b> ";
						}
						else
						{
							/* */
							$match_html .= "MATCH BANK:: ";
							$match_html .=  print_r( $our_account, true );
								//$match_html .= "::" . print_r( $bank, true );
							$match_html .= "::" . print_r( $bank['bank_account_name'], true );
							$match_html .= " Matching " . print_r( $matchgl, true );
							//$match_html .= " Matching " . print_r( $matchgl['account'], true );
							$match_html .= "Account " . $matchgl['account'] . "---";
							/* */
						}
						$match_html .= " " . $matchgl['account_name'] . " ";
						if( $transactionDC == 'D' )
						{
							$scoreamount = -1 * $amount;
						}
						else
						{
							$scoreamount = 1 * $amount;
						}
						if( $scoreamount == $matchgl['amount'] )
						{
							$match_html .= "<b> " . $matchgl['amount'] . "</b> ";
						}
						else
						{
							$match_html .= $matchgl['amount'];
						}
						if( isset( $matchgl["person_type_id"] ) )
						{
							$cdet = get_customer_details_from_trans( 	$matchgl['type'], 
													$matchgl['type_no']
								);
	
							//display_notification( __FILE__ . "::" . __LINE__ . ":" . print_r( $cdet, true ) );
							$match_html .= " //Person " . $cdet['name'] . "/" . $cdet["br_name"];
							//$match_html .= " //Person " . print_r( $cdet, true ) . "/" . $matchgl["person_id"];
							//$match_html .= " //Person " . $matchgl['person_type_id'] . "/" . $matchgl["person_id"];
						}
	
						$match_html .= "<br />";
						$matchcount++;
					} //if isset
				} //foreach
					label_row("Matching GLs", $match_html );
					//label_row("Matching GLs", print_r( $arr_arr, true ) );
			}
			else
			{
					label_row("Matching GLs", "No Matches found automatically" );
			}
		}
		end_table();
		echo "</td>";
		end_row();
		}
		if ($row_count == 0) {
			label_row("", "<i>No transactions found for the selected date range and status.</i>");
		}
		end_table();
	}
	
	
	div_end();
	end_form();

end_page(@$_GET['popup'], false, false);
?>
