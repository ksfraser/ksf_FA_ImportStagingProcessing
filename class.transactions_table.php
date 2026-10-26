<?php
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

class transaction_table
{
	//The table is made up of a series of rows
	protected $transaction_table_rows;	//!<array
	/**//**************************************************
	*
	* @param mysql_res
	* @returns none
	*******************************************************/
	function __construct( $mysql_res )
	{
		$this->transaction_table_rows = array();
		$this->res2arr( $mysql_res );
	}
	/**//************************************************
	* Take the query result and build classes
	*
	* @param mysql_res
	* @returns none builds internal array
	****************************************************/
	function res2arr( $mysql_res )
	{
		while($myrow = db_fetch($res)) 
		{
			$this->transaction_table_rows[] = new transaction_table_row( $myrow );
		}
	}
	/**//************************************************
	* Convert our data into an output HTML
	*
	* @param none
	* @return HTML
	*****************************************************/
	function display()
	{
		start_table(TABLESTYLE, "width='100%'");
		table_header(array("Transaction Details", "Operation/Status"));
		foreach( $this->transaction_table_rows as $row )
		{
			$row->display();	
		}
	}
}

class transaction_table_row
{
	/**//**************************************************
	*
	* @param mysql_row db_fetch'd row (array)
	* @returns none
	*******************************************************/
	function __construct( $res_arr )
	{
		 	$this->transactionDC = $res_arr['transactionDC'];
			$this->our_account = $res_arr['our_account'];
			$this->valueTimestamp = $res_arr['valueTimestamp'];
			try {
				$this->bankAccount = shorten_bankAccount_Names( $res_arr['accountName'] );
			}
			catch( Exception $this->e )
			{
				display_notification( __FILE__ . "::" . __LINE__ . ":" . $this->e->getMessage() );
				$this->bankAccount = $res_arr['accountName'];
			}
			$this->bankAccountName = $res_arr['accountName'];
			if( strlen( $res_arr['transactionTitle'] ) < 4 )
			{
				if( strlen( $res_arr['memo'] ) > strlen( $res_arr['transactionTitle'] ) )
				{
					$res_arr['transactionTitle'] .= $res_arr['memo'];
				}
			}
			$this->transactionTitle = $res_arr['transactionTitle'];
			$this->currency = $res_arr['currency'];
			$this->status = $res_arr['status'];
			$this->tid = $res_arr['id'];
			$this->fa_trans_type = $res_arr['fa_trans_type'];
			$this->fa_trans_no = $res_arr['fa_trans_no'];
			if ($res_arr['transactionType'] != 'COM') {
				$this->has_trans = 1;
				$this->amount = $res_arr['transactionAmount'];
			} else if ($res_arr['transactionType'] == 'COM') 
			{
				$this->amount += $res_arr['transactionAmount'];
			}
	}
	/**//************************************************
	* Convert our data into an output HTML
	*
	* @param none
	* @return HTML
	*****************************************************/
	function display()
	{
	}
}

/**//**********************
*
*
* I feel like I'm reinventing my own wheel here!!!
* Probably from my VIEW classes.
*
*************************/
class ttr_table
{
	protected $style;
	protected $width;	//!<int percentage
	/**//*************
	*
	* @param int the style definition
	* @param int The percentage width
	function __construct( $style, width )
	{	
		$this->style = $style;
		$this->width = $width
	}
	/**//************************************************
	* Convert our data into an output HTML
	*
	* @param none
	* @return HTML
	*****************************************************/
	function display()
	{
		return start_table( $this->style, "width='" . $this->width . "%'");
	}
	
}
class ttr_label_row
{
	protected $data;	//!<string
	function __construct( $data )
	{
		$this->data = $data;
	}
	/**//************************************************
	* Convert our data into an output HTML
	*
	* @param none
	* @return HTML
	*****************************************************/
	function display()
	{
		return label_row( $this->data );
	}
}
	


/*************************************************************************************************************/

	//load data
	while($myrow = db_fetch($res)) 
	{
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
	
////////////
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
				if (empty($_POST["partnerId_$tid"])) {
				$match = search_partner_by_bank_account(PT_SUPPLIER, $bankAccount);
				if (!empty($match)) {
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
					label_row(	_("Transfer From:"), 
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
		end_table();
	}
	
	
	div_end();
	end_form();

end_page(@$_GET['popup'], false, false);
?>
