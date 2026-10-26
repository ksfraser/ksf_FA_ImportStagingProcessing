<?php


require_once( '../ksf_modules_common/class.generic_fa_interface.php' ); 
require_once( 'class.import_statements_gui.php' ); 

//global $path_to_root;
$path_to_root = "../..";


/************************************************************************//**
 * This class acts as a controller.
 *
 *
 * uses inherited call_table
 * uses class write_file
 * uses class email_file
 *
 * *************************************************************************/
class ksf_import_square extends generic_fa_interface
{
	var $include_header;
	var $maxrowsallowed;
	var $lastoid;
	var $environment;
	var $debug;
	var $fields_array;
	protected $default_customer;
	protected $days_before;
	protected $days_after;
	protected $gui;	//!<object	This should probably be in generic_fa_interface
	protected $customer_id;
	protected $already_matched;	//!<array list of trans_no of matched transactions.  Needs to be be loaded and saved into SESSION on change!
	/** @var SourceConfig|null Source-specific config provided by importing module */
	protected $sourceConfig;
	protected $debtor_no;		//!<int the current debtor
	protected $transaction_id;	//!<int
	protected $deposit_id;		//!<int
	protected $payment_id;		//!<int
	protected $transaction_date;	//!<string date
	protected $o_transaction;	//!<object import_square_transaction
	protected $createCustomerValue;	//!<string for on button
	protected $rounding_amount;	//!<float how much to round for errors
	protected $default_location;	//!<int Which Inventory Location transactions are sold out of
	protected $default_dimension1;	//!<int
	protected $default_dimension2;	//!<int


	function __construct( $pref_tablename )
	{
		simple_page_mode(true);
		global $db;
		$this->db = $db;
		//echo "ksf_import_square constructor";
		parent::__construct( null, null, null, null, $pref_tablename );
		
		$this->tmp_dir = "../../tmp";
		//$this->set_var( 'vendor', "ksf_import_square" );
		/*
		$this->fields_array = array();
		$this->fields_array[] = array( 'field' => 'inactive', 'table' => 'stock_master', 'header' => '', 'join' => '0', 'where' => '=0');
		 */
		/******************************/
		$this->config_values[] = array( 'pref_name' => 'environment', 'label' => 'Environment (devel/accept/prod)' );
		$this->config_values[] = array( 'pref_name' => 'debug', 'label' => 'Debug (0,1+)' );
		$this->config_values[] = array( 'pref_name' => 'default_customer', 'label' => 'Default Customer for unspecified customers', 'type' => 'customer_list' );
		$this->config_values[] = array( 'pref_name' => 'days_before', 'label' => 'When matching sales orders how many days before to look', 'type' => 'integer' );
		$this->config_values[] = array( 'pref_name' => 'days_after', 'label' => 'When matching sales orders how many days after to look', 'type' => 'integer' );
		$this->config_values[] = array( 'pref_name' => 'rounding_amount', 'label' => 'Amount for rounding when searching matching transactions', 'type' => 'integer' );
		$this->config_values[] = array( 'pref_name' => 'default_location', 'label' => 'Default location for sales to be recorded against', 'type' => 'locations_list' );
		$this->config_values[] = array( 'pref_name' => 'default_dimension1', 'label' => 'Default dimension 1', 'type' => 'dimensions_list' );
		$this->config_values[] = array( 'pref_name' => 'default_dimension2', 'label' => 'Default dimension 2', 'type' => 'dimensions_list' );
		$this->dolabels = 0;
		
		//The forms/actions for this module
		//Hidden tabs are just action handlers, without accompying GUI elements.
		//$this->tabs[] = array( 'title' => '', 'action' => '', 'form' => '', 'hidden' => FALSE );
		/** Mantis 3033 Config **/
		$this->tabs[] = array( 'title' => 'Configuration', 'action' => 'config', 'form' => 'action_show_form', 'hidden' => FALSE );
		$this->tabs[] = array( 'title' => 'Config Updated', 'action' => 'update', 'form' => 'checkprefs', 'hidden' => TRUE );
		$this->tabs[] = array( 'title' => 'Usage Instructions', 'action' => 'usage', 'form' => 'usage_form', 'hidden' => FALSE );
		$this->tabs[] = array( 'title' => 'Install Module', 'action' => 'create', 'form' => 'install', 'hidden' => TRUE );
		/** Mantis 2373 Load (CSV) screen **/
		$this->tabs[] = array( 'title' => 'Import CSV', 'action' => 'create', 'form' => 'install', 'hidden' => TRUE );
		$this->tabs[] = array( 'title' => 'Display Transactions', 'action' => 'create', 'form' => 'install', 'hidden' => TRUE );
		$this->tabs[] = array( 'title' => 'Process Transactions', 'action' => 'create', 'form' => 'install', 'hidden' => TRUE );
		//We could be looking for plugins here, adding menu's to the items.
		$this->tabs[] = array( 'title' => 'Import Files', 'action' => 'import_files', 'form' => 'import_files_form', 'hidden' => FALSE );
		$this->tabs[] = array( 'title' => 'Upload File', 'action' => 'upload_file', 'form' => 'upload_file_form', 'hidden' => TRUE );
		$this->tabs[] = array( 'title' => 'Process Transactions', 'action' => 'process_transactions', 'form' => 'process_transactions_form', 'hidden' => FALSE );
		$this->tabs[] = array( 'title' => 'Process Payments', 'action' => 'process_payments', 'form' => 'process_payments_form', 'hidden' => FALSE );
		$this->tabs[] = array( 'title' => 'Process Deposits', 'action' => 'process_deposits', 'form' => 'process_deposits_form', 'hidden' => FALSE );
		$this->tabs[] = array( 'title' => 'Add Deposit', 'action' => 'add_deposit', 'form' => 'add_deposit_form', 'hidden' => TRUE );
		$this->tabs[] = array( 'title' => 'Add Payment', 'action' => 'add_payment', 'form' => 'add_payment_form', 'hidden' => TRUE );
		$this->tabs[] = array( 'title' => 'Payment Added', 'action' => 'payment_added', 'form' => 'payment_added_form', 'hidden' => TRUE );
		$this->tabs[] = array( 'title' => 'Add Customer', 'action' => 'add_customer', 'form' => 'add_customer_form', 'hidden' => TRUE );
		$this->tabs[] = array( 'title' => 'Match Payment', 'action' => 'match_payment', 'form' => 'match_payment_form', 'hidden' => TRUE );
		$this->tabs[] = array( 'title' => 'Match Deposit', 'action' => 'match_deposit', 'form' => 'match_deposit_form', 'hidden' => TRUE );
		$this->tabs[] = array( 'title' => 'Match Customer', 'action' => 'match_customer', 'form' => 'match_customer_form', 'hidden' => TRUE );
		$this->tabs[] = array( 'title' => 'View Transaction', 'action' => 'view_transaction', 'form' => 'view_transaction_form', 'hidden' => TRUE );
		$this->tabs[] = array( 'title' => 'View Transaction Items', 'action' => 'transaction_items', 'form' => 'transaction_items_form', 'hidden' => TRUE );
		$this->tabs[] = array( 'title' => 'Match a Transaction to an Invoice', 'action' => 'match_transaction_invoice', 'form' => 'match_transaction_invoice_form', 'hidden' => TRUE );
		$this->tabs[] = array( 'title' => "Edit an Item's Sku", 'action' => 'edit_item_sku', 'form' => 'edit_item_sku_form', 'hidden' => TRUE );
		$this->add_submodules();
 		$this->gui = new import_statements_gui();
		$this->set( "help_context", $this->gui->get( "help_context" ) );
		$this->initAlreadyMatched();
		require_once( 'class.ksf_import_square_transactions.php' );
		$this->o_transaction = new ksf_import_square_transactions_model();
		if( isset( $_SESSION['transaction_id'] ) )
			$this->set( "transaction_id", $_GET['transaction_id'] );
		if( isset( $_SESSION['payment_id'] ) )
			$this->set( "payment_id", $_GET['payment_id'] );
		if( isset( $_SESSION['deposit_id'] ) )
			$this->set( "deposit_id", $_GET['deposit_id'] );
		if( isset( $_SESSION['transaction_date'] ) )
			$this->set( "transaction_date", $_GET['transaction_date'] );

		$this->createCustomerValue = "Create Customer";

	/*	
	 */
	}
	/**//***************************************************
	* Set variables
	*
	*	While we inherit a set, we need to specially handle certain values
	*
	* @since 20250314
	*
	* @param string field
	* @param string value
	* @param bool enforce
	* @returns bool
	***********************************************
	function set( $field, $value = null, $enforce = true )
	{
		switch( $field )
		{
			case 'already_matched':
				$this->already_matched[] = $value;
				$_SESSION['already_matched'] = serialize( $this->already_matched );
				return TRUE;
				break;
			case 'transaction_id':
			case 'deposit_id':
			case 'payment_id':
				$this->o_transaction->set( $field, $var );
				return parent::set( $field, $value, $enforce );
				break;
			case 'transaction_date':
				$this->o_transaction->set( 'Date', $var );
				return parent::set( $field, $value, $enforce );
				break;
			default:
				return parent::set( $field, $value, $enforce );
		}
	}
	/**//************************************************************************
	* Set already_matched to either values saved in session or as empty array.
	*
	*	TODO:
	*		use framework class that saves/retrieves data from session.
	*
	* @since 20250314
	*
	* @param none uses SESSION
	* @return none sets already_matched
	*****************************************************************************/
	function initAlreadyMatched()
	{
		if( isset( $_SESSION['already_matched'] ) )
			$this->already_matched = unserialize( $_SESSION['already_matched'] );
		else
			$this->already_matched = array();
	}
	/**//***************************
	*
	* @params none
	* @returns none
	*******************************/
	function run()
	{
/*
		if( $this->debug >= PEAR_LOG_WARN )
		{
* /
			var_dump( $_POST );	
			var_dump( $_GET );	
/*
		}
*/
		parent::run();
	}
	/**//***************************
	* Display the Import Files form
	*
	* @since 20250310
	*
	* @params none
	* @returns none
	*******************************/
	function import_files_form()
	{
        	$this->gui->do_upload_form();

	}
	/**//***************************
	* do_upload_form passes the uploaded files back to us here.  Process them.
	*
	* @since 20250310
	*
	* @params none
	* @returns none
	*******************************/
	function upload_file_form()
	{
		if (@$_POST['upload'] && ($_FILES['files']['error'][0] == 0)) {
			require_once( '../ksf_modules_common/class.parse_uploaded_files.php' );
			require_once( "class.ksf_import_square_transactions.php" );
			require_once( "class.ksf_import_square_items.php" );
			require_once( "class.ksf_import_square_sales_model.php" );

        		$puf = new parse_uploaded_files( $this->gui );
        		//We have transaction_id in both Square CSV file types.
        		$puf->process_upload( "transaction_id" );
		}
	}
	/**//***************************
	*
	* @params none
	* @returns none
	*******************************/
	function process_transactions_form()
	{
		require_once( '../ksf_modules_common/class.fa_gl.php' );
		$this->formFromTo( null, $this->action );
	        start_table(TABLESTYLE, "width='100%'");
		table_header(array("Transaction Details", "Operation/Status"));
		hidden( 'action', $this->action );
		$tr = $this->o_transaction;
		$res_arr = $tr->getTransactionsDateRange( $this->transAfterDate, $this->transToDate, $this->statusFilter );
		//var_dump( $res );
		 start_row();
                echo '<td width="50%">';
		//$this->display_transaction_table( $res_arr, "column" );
		$this->display_transaction_table( $res_arr, "column", array( ST_CUSTPAYMENT ) );

		//Now we need to display the matching events and/or action buttons
		// such as ADD PAYMENT (customer payment)
		// or ADD DEPOSIT (fund transfer)
		// or ADD SALES INVOICE


	}
	/**//***************************
	*
	* @params none
	* @returns none
	*******************************/
	function process_payments_form()
	{
		require_once( '../ksf_modules_common/class.fa_gl.php' );
		$this->formFromTo( null, $this->action );
	        start_table(TABLESTYLE, "width='100%'");
		table_header(array("Transaction Details", "Operation/Status"));
		hidden( 'action', $this->action );
/*
		require_once( 'class.ksf_import_square_transactions.php' );
		$tr = new ksf_import_square_transactions_model();
*/
		$tr = $this->o_transaction;
		$res_arr = $tr->getPaymentsDateRange( $this->transAfterDate, $this->transToDate, $this->statusFilter );
		//var_dump( $res );
		 start_row();
                echo '<td width="50%">';
		$this->display_transaction_table( $res_arr, "row", array( ST_JOURNAL, ST_CUSTPAYMENT ) );

		//Now we need to display the matching events and/or action buttons
		// such as ADD PAYMENT (customer payment)
		// or ADD DEPOSIT (fund transfer)
		// or ADD SALES INVOICE


	}
	/**//***************************
	*
	* @params none
	* @returns none
	*******************************/
	function process_deposits_form()
	{
		$this->formFromTo( null, $this->action );
	        start_table(TABLESTYLE, "width='100%'");
		table_header(array("Transaction Details", "Operation/Status"));
		hidden( 'action', $this->action );
/*
		require_once( 'class.ksf_import_square_transactions.php' );
		$tr = new ksf_import_square_transactions_model();
*/
		$tr = $this->o_transaction;
		$res_arr = $tr->getDepositsDateRange( $this->transAfterDate, $this->transToDate, $this->statusFilter );
		//var_dump( $res );
		 start_row();
                echo '<td width="50%">';
		//$this->display_transaction_table( $res_arr );
		$this->display_transaction_table( $res_arr, "row", array( ST_JOURNAL, ST_BANKTRANSFER ) );

		//Now we need to display the matching events and/or action buttons
		// such as ADD PAYMENT (customer payment)
		// or ADD DEPOSIT (fund transfer)
		// or ADD SALES INVOICE
		//SELECT Date as sq_date, deposit_id, gross_sales, tax, total_collected, fees, net_total, card, 
		//	g.amount, g.type, g.type_no, g.tran_date FROM `1_ksf_import_square_transactions` s, 1_gl_trans g 
		//	where s.card = abs(g.amount) and g.tran_date >= s.Date and g.tran_date < DATE_ADD( s.Date, interval 5 day ) and g.account=1062 and g.type in ( '4', '0')


	}	
	function display_table( $myrow, $rowOrColumn = "row" )
	{
		if( is_array( $myrow ) )
		{
                		start_table(TABLESTYLE2, "width='100%' border=1");
			$header = "";
			$trow = "";
			foreach( $myrow as $key => $value )
			{
				if( "row" == $rowOrColumn )
				{
					//label_row($label, $value, $params="", $params2="", $leftfill=0, $id=null)
					//Getting warnings/errors because value is being sent as an array
					if( is_array( $value ) )
					{
						//var_dump( $value );
						foreach( $value as $row )
						{
							$this->display_table( $row, "row" );
						}
					}
					else
					{
						label_row( $key, $value );
					}
				}
				else
				{
					$header .= "<th>$key</th>";
					$trow .="<td>$value</td>";
				}
			}
	
			//var_dump( $header );
			//var_dump( $trow );

			echo $header . "<tr>" . $trow . "</tr>";
			end_table();
		}
	}
	/**//**************************************************
	* Format the "View Transaction" link
	*
	* This was migrated into generic_fa_interface,
	* of which we are a child class.
	*
	* @param int transaction type
	* @param int transaction number
	* @returns string URL
	*****************************************************/
	function viewTransURL( $type, $trans_no )
	{
		return $this->viewTransTypeURL( $type, $trans_no );
	}
	/**//**************************************************
	* Format the "View Transaction" link to use Journal Entry
	*
	* This was migrated into generic_fa_interface,
	* of which we are a child class.
	*
	* @param int transaction type
	* @param int transaction number
	* @returns string URL
	***************************************************** /
	function viewJournalEntryURL( $type, $trans_no )
	{	
		return parent::viewJournalEntryURL( $type, $trans_no );
	}
	/**/
	/**//**************************************************
	* Format the "Match Transaction to Deposit/Payment/" link to use Journal Entry
	*
	* @param int transaction type
	* @param int transaction number
	* @param string the payment or deposit ID
	* @param string type of ID
	* @returns string URL
	*****************************************************/
	function viewMatchTransactionURL( $type, $trans_no, $id, $id_type = "transaction_id" )
	{
		//$type = (int)$type;
		switch( $type )
		{
			//case "4":
			case ST_BANKTRANSFER:
			case ST_BANKDEPOSIT:
			case ST_JOURNAL:
				//id will be a deposit_id
						$ret = "<a target=_blank href='" . $_SERVER['SCRIPT_NAME'] . "?action=match_deposit&transaction_type=" . $id_type . "&transaction_id=" . $id . "&type=" . $type . "&type_no=" . $trans_no . "'>Match Deposit  " . $id . " to " . $trans_no . "</a>";
				break;
			case ST_CUSTPAYMENT:
				//id will be a payment_id
						$ret = "<a target=_blank href='" . $_SERVER['SCRIPT_NAME'] . "?action=match_payment&transaction_type=" . $id_type . "&transaction_id=" . $id . "&type=" . $type . "&type_no=" . $trans_no . "'>Match Payment " . $id . " to " . $trans_no . "</a>";
						//$ret = "<a target=_blank href='" . $_SERVER['SCRIPT_NAME'] . "?action=match_payment&payment_id=" . $id . "&type=" . $type . "&type_no=" . $trans_no . "'>Match Payment " . $id . " to " . $trans_no . "</a>";
				break;
			case ST_SALESINVOICE:
						$ret = "<a target=_blank href='" . $_SERVER['SCRIPT_NAME'] . "?action=match_invoice&transaction_type=" . $id_type . "&transaction_id=" . $id . "&type=" . $type . "&type_no=" . $trans_no . "'>Match Invoice " . $id . " to " . $trans_no . "</a>";
				break;
			case ST_CUSTCREDIT:
			case ST_CUSTDELIVERY:
			case ST_BANKPAYMENT:
			case ST_LOCTRANSFER:
			case ST_SUPPCREDIT:
			case ST_SUPPINVOICE:
			case ST_SUPPAYMENT:
			case ST_SUPPPAYMENT:
			default:
				$ret = "MATCH not defined yet for type:" . $type . "::" . __FILE__ . "::" . __LINE__ ;
				break;
		}
		return $ret;
	}
	/**//**************************************************
	* Format the "View Transaction Details" link
	*
	* @param string transaction string from square
	* @returns string URL
	*****************************************************/
	function viewTransactionDetailsURL( $transaction_id )
	{
		$ret = "<a target=_blank href='" . $_SERVER['SCRIPT_NAME'] . "?action=view_transaction&transaction_id=" . $transaction_id . "'>View Transaction " . $transaction_id . "</a>";
		//$ret = "<a target=_blank href='" . $_SERVER['SCRIPT_NAME'] . "?action=view_transaction&transaction_id=" . $transaction_id . "'>View Transaction " . $transaction_id . "</a>";
		return $ret;
	}
	/**//**************************************************
	* Format the "View Deposit Details" link
	*
	* @param string URL from Square
	* @param string deposit string from square
	* @param string type of transaction to view.  Default Deposit
	* @returns string URL
	*****************************************************/
	function viewDepositDetailsURL( $deposit_details, $deposit_id, $type = "Deposit" )
	{
		$ret = "<a target=_blank href='" . $deposit_details . "'>View " . $type . " " . $deposit_id . "</a>";
		//$ret = "<a target=_blank href='" . $deposit_details . "'>View Deposit " . $deposit_id . "</a>";
		return $ret;
	}
	
	/**//**************************************************
	* Format the "Add Deposit/Payment" Button
	*
	* @param string array data for transaction
	* @returns string Button
	*****************************************************/
	function addPayDepButton( $row )
	{
		if( isset( $row['deposit_id'] ) )
		{
				//function button($name, $value, $title=false, $icon=false,  $aspect='')
				//function submit($name, $value, $echo=true, $title=false, $atype=false, $icon=false)
			//$ret = button("Add Deposit", $row['deposit_id'], _("AddDeposit"), false, '');
			$ret = submit("Add Deposit", $row['deposit_id'], false, _("AddDeposit"), 'default');
			//$ret = submit("Add Deposit", $row['deposit_id'], true, _("AddDeposit"), true, '');
			$ret .= hidden( "deposit_id", $row['deposit_id'], false );
			$ret .= hidden( "action", "addDeposit", false );
		}
		else
		if( isset( $row['payment_id'] ) )
		{
			$ret = submit("Add Payment", $row['payment_id'], true, _("AddPayment"), true, '');
			//$ret = button("Add Payment", $row['payment_id'], _("AddPayment"), false, '');
			$ret .= hidden( "payment_id", $row['payment_id'], false );
			$ret .= hidden( "action", "addPayment", false );
		}
		return $ret;
	}
	/**//**************************************************
	* Format the "Add Deposit/Payment" URL Link
	*
	* @param string array data for transaction
	* @returns string Button
	*****************************************************/
	function addPayDepLink( $row )
	{
		if( isset( $row['deposit_id'] ) )
		{
			$ret = "<a target=_blank href='" . $_SERVER['SCRIPT_NAME'] . "?action=add_deposit&deposit_id=" . $row['deposit_id'] . "'>Add Deposit " . $row['deposit_id'] . "</a>";
			$ret .= hidden( "deposit_id", $row['deposit_id'], false );
			//$ret .= hidden( "action", "addDeposit", false );
		}
		else
		if( isset( $row['payment_id'] ) )
		{
			$ret = "<a target=_blank href='" . $_SERVER['SCRIPT_NAME'] . "?action=add_payment&payment_id=" . $row['payment_id'] . "'>Add Payment " . $row['payment_id'] . "</a>";
			$ret .= hidden( "payment_id", $row['payment_id'], false );
			//$ret .= hidden( "action", "addPayment", false );
		}
		return $ret;
	}
	/**//**************************************************
	* Check to see if our payment has a match  
	*
	* @param string 
	* @param string
	* @returns bool 
	*****************************************************/
	function hasPaymentMatch( $id, $type )
	{
		return false;
	}
	/**//***************************
	*
	* @params array data to display
	* @param string are we displaying the data in rows or columns
	* @param array|null what transaction types to filter for
	* @returns none
	*******************************/
	function display_transaction_table( $res_arr, $rowOrColumn = "row", $typeArr = null )
	{
		require_once( '../ksf_modules_common/class.fa_gl.php' );
		$fa_gl = new fa_gl();
		$trx = array();
		foreach( $res_arr as $myrow )
		{
			start_row();
                	echo '<td width="50%">';
			$myrow['transaction_id'] = $this->viewTransactionDetailsURL( $myrow['transaction_id'] );
			if( isset( $myrow['deposit_id'] ) )
				$myrow['SquareDepositDetailsURL'] = $this->viewDepositDetailsURL( $myrow['deposit_details'], $myrow['deposit_id'] );
/** Square doesn't provide an equivalent payment detail link
			else
			if( isset( $myrow['payment_id'] ) )
				$myrow['payURL'] = $this->viewPaymentDetailsURL( $myrow['deposit_details'], $myrow['payment_id'] );
**/

			if( isset( $myrow['customer_name'] ) )
			{
				$myrow['customer_link'] = "";
/**
				$customer_link_arr = $this->viewCustomerDetailsURL( $myrow );
				//var_dump( $customer_link_arr );
				if( count( $customer_link_arr ) > 0 )
				{
					foreach( $customer_link_arr as $line )
					{
						$myrow['customer_link'] .= $line;
					}
				}
				else
				{
					//Create new customer button!
					submit_center_first('CreateCustomer', $this->createCustomerValue,
					    _('Create Customer'), 'default');
					submit_center_last('Cancel', "Cancel",
	   					_('Cancels changes.'), true);
				$line = start_form() . submit("CreateCustomer",_($this->createCustomerValue),false, 'Create Customer', 'default') . hidden("customer_name", $myrow['customer_name'], false ) . end_form();
						$myrow['customer_link'] .= $line;
					
				}
**/
/*****
				require_once( 'class.ksf_import_external_customers_model.php' );
				$ec = new ksf_import_external_customers_model();
				$cust_match = $ec->findCustomer( $myrow['name'], "SQUARE" );
				if( count( $cust_match ) > 0 )
				{
					if( isset( $cust_match[''] ) )
					{
					}
				}
******/
			}
			else
			{
				$myrow['customer_link'] = "TBD";
			}
/**
**/

			$this->display_table( $myrow, $rowOrColumn );

			if( null !== $typeArr )
			{
				echo "</td><td>";
				$amount = str_replace( ',', '', $myrow['card']);
				if( $amount > 0 )
				{
						$account = $this->sourceConfig ? $this->sourceConfig->getGlAccount() : 0;
					//var_dump( "Card: " . $amount );
				}
				else if( isset( $myrow['cash'] ) )
				{
					$amount = str_replace( ',', '', $myrow['cash']);
					if( $amount > 0 )
					{
						$account = $this->sourceConfig ? $this->sourceConfig->getCashGl() : 0;
						//var_dump( "Cash: " . $amount );
					}
				}
				else
				{
					$amount = 0;
				}
				$trx = $fa_gl->findMatchingTransactions( $typeArr, $amount , $myrow['Date'], 2, 5, $account, $this->already_matched );
				if( false !== $trx and count( $trx ) > 0 )
				{
					//return;
					if( isset( $trx['type'] ) )
					{
						//1d array
						$trx[] = $this->formatMatchURLs( $trx['type'], $trx['type_no'], $trx['deposit_id'] = null, $trx['transaction_id'] = null, $trx['payment_id'] = null );
						var_dump( $trx );
					}
					else
					if( isset( $trx[0] ) )
					{
						//multi D array
						foreach( $trx as $row )
						{
							if( isset( $trx['type'] ) )
							{
								$trx[] = $this->formatMatchURLs( $trx['type'], $trx['type_no'], $trx['deposit_id'] = null, $trx['transaction_id'] = null, $trx['payment_id'] = null );
								var_dump( $trx );
							}
						}
					}
				}
				else
				{
					if( isset( $myrow['deposit_id'] ) )
					{
						$trx['Add_Deposit_Link'] = "<a target=_blank href='" . $_SERVER['SCRIPT_NAME'] . "?action=add_deposit&deposit_id=" . $myrow['deposit_id'] . "'>Add Deposit " . $myrow['deposit_id'] . "</a>";
					}
					if( isset( $myrow['payment_id'] ) )
					{
						$trx['Add_Payment_Link'] = "<a target=_blank href='" . $_SERVER['SCRIPT_NAME'] . "?action=add_payment&payment_id=" . $myrow['payment_id'] . "'>Add Payment " . $myrow['payment_id'] . "</a>";
					}
				/*
					$trx['AddPayLink'] = $this->addPayDepLink( $myrow );
					$trx['AddPayButton'] = $this->addPayDepButton( $myrow );
				*/
				}
				$this->display_table( $trx, "row" );
			}
			else
			{
				if( isset( $this->debtor_no ) )
				{
		                        // Display allocatable invoices for this payment
		                        if( @$inc = include_once( '../ksf_modules_common/class.fa_customer_payment.php' ) )
		                        {
		                                echo "</td><td>";
		                                $fcp = new fa_customer_payment( $this->get( "debtor_no" ) );
		                                $fcp->set( "trans_date", $myrow['Date'] );
		                                $res = $fcp->get_alloc_details();
		                                label_row( "Invoices to Pay", print_r( $res, true) );
		                                                                                //function text_input($name, $value=null, $size='', $max='', $title='', $params='')
		                                //label_row( (_("Allocate Payment to (1) Invoice")), text_input( "Invoice_$tid", 0, 6, '', _("Invoice to Allocate Payment:") ) );
					}
				}
			}
			echo "</td>";
			end_row();

        	}
		
	}
	function formatMatchURLs( $type, $type_no, $deposit_id, $transaction_id, $payment_id = null )
	{
		$ret_arr = array();
		$ret_arr['URL'] = $this->viewTransURL( $type, $type_no );
		$ret_arr['JEURL'] = $this->viewJournalEntryURL( $type, $type_no );
		if( isset( $deposit_id ) )	
		{
			$id = $deposit_id;
			$id_type = 'deposit_id';
		}
		else
		if( isset( $payment_id ) )	
		{
			$id = $payment_id;
			$id_type = 'payment_id';
		}
		else
		{
			$id = $myrow['transaction_id'];
			$id_type='transaction_id';
		}
		//Check to see if the payment has already been matched.  If so, show a link to view.  If not, show a "match payment" link.
		if( $this->hasPaymentMatch( $id, $id_type ) )
		{
		}
		else
		{
			$ret_arr['MATCHURL'] = $this->viewMatchTransactionURL( $ret_arr['type'], $ret_arr['type_no'], $id, $id_type );
		}
		return $ret_arr;
	}
	/**//***************************
	* Add a Bank Transfer from Square GL to Another GL from config
	*
	* @since 20250312
	*
	* @params none
	* @returns none
	*******************************/
	function add_deposit_form()
	{
		// ["action"]=> string(11) "add_deposit" ["deposit_id"]=> string(28) "3ZV1W39A71J501K7WS7YY4XQM65N"
		
 		$inc = require_once( '../ksf_modules_common/class.fa_bank_transfer.php' );
		if( $inc )
		{
/*
			require_once( 'class.ksf_import_square_transactions.php' );
			$tr = new ksf_import_square_transactions_model();
*/
			$tr = $this->o_transaction;
			$res_arr = $tr->getDepositsDateRange( $this->transAfterDate, $this->transToDate, $this->statusFilter );
			$trz = $tr->getDeposit( $_GET['deposit_id'] );
			//var_dump( $trz );
			if( count( $trz ) < 2 )
			{
				throw new Exception( "Deposit ID " . $_GET['deposit_id'] . " did not return any data!" );
			}

			$bttrf = new fa_bank_transfer();
			try
			{
				$bttrf->set( "trans_type", ST_BANKTRANSFER );
			$bttrf->set( "ToBankAccount", $this->sourceConfig ? $this->sourceConfig->getXferToBank() : 0 );
			$bttrf->set( "FromBankAccount", $this->sourceConfig ? $this->sourceConfig->getBankAccount() : 0 );
				if( is_callable( 'gmp_abs' ) )
					$bttrf->set( "amount", gmp_abs( $trz['net_total'] ) );	//gmp_abs accepts strings
				else
					$bttrf->set( "amount", str_replace( ',', '', $trz['net_total'] ) );
				$bttrf->set( "trans_date", $trz['Date'] );
				$bttrf->set( "charge", abs( $trz['fees'] ) );	//It's conceivable fees could be 1k+ so this would fail....
				$bttrf->set( "memo_", ucfirst($this->sourceConfig ? $this->sourceConfig->getSource() : 'import') . " Deposit::" . $trz['deposit_id'] . ":: for CARD amount :" . $trz['card'] . ": and fees of :" . $trz['fees'] . ": for deposit of :" . $trz['net_total'] . ":" );
				//$bttrf->set( "target_amount", $trz['net_total'] );	//Target Amount is for doing currency conversions!
				$bttrf->getNextRef();
			}
			catch( Exception $e )
			{
				display_error( __FILE__ . "::" . __LINE__ . ":" . $e->getMessage() );
				return;
			}
			begin_transaction();
			$bttrf->add_bank_transfer();
		//TODO
		//	Set the xref values in OUR table
			$trans_no = $bttrf->get( "trans_no" );
			$trans_type = $bttrf->get( "trans_type" );

			display_notification("<a href='../../gl/view/gl_trans_view.php?type_id=" . $trans_type . "&trans_no=" . $trans_no . "'>View Entry</a>" );
			commit_transaction();
		}
		else
		{
			throw new Exception( "Couldn't open class.fa_bank_transfer.php" );
		}

	}
	/**//***************************
	*
	* @params none
	* @returns none
	*******************************/
	function add_payment_form()
	{

		//PAN_suffix can help identify a customer.  It is card specific.
		//It would be a branch of a customer.
		//So when we are creating 0Cash customers for all of the unattributed sales
		//  we could create a branch related to the PAN.  If we end up with the same
		//  PAN against an actual name in the future, we could re-associate invoices
		//  and payments.  Is it worth the effort?  Maybe from a marketing perspective.
		//
		//Since a payment is customer specific, I think we want this particular screen
		//  to allow us to select the customer and the invoice similar to bank import.
		//


	}
	/**//***************************
	* Display the successful addition and links to view the record.
	*
	* @since 20250313
	*
	* @params none
	* @returns none
	*******************************/
	function payment_added_form()
	{
		$fcp = new fa_customer_payment( $this->customer_id );
/*
                $fcp->set( 'trans_no', $_GET[''] ); 
		$fcp->set( 'customer_id', $_GET[''] ); 
		$fcp->set( 'Branch_ID', $_GET[''] ); 
		$fcp->set( 'bank_account', $_GET[''] );
                $fcp->set( 'trans_date', $_GET[''] ); 
		$fcp->set( 'reference', $_GET[''] ); 
		$fcp->set( 'amount', $_GET[''] ); 
		$fcp->set( 'discount', $_GET[''] ); 
		$fcp->set( 'memo_', $_GET[''] ); 
		$fcp->set( 'rate', $_GET[''] ); 
		$fcp->set( 'charge', $_GET[''] ); 
		$fcp->set( 'bank_amount', $_GET[''] ); 
		$fcp->set( 'trans_type', ST_CUSTPAYMENT ); );
		$fcp->set( 'SInvoice', $_GET[''] ); );
*/
		//If an invoice number is set, it will allocate the payment
		$fcp->import_write_customer_payment();
		//TODO:
		//	Update the import tables of the created invoice
/***
		display_notification("<a href='../../gl/view/gl_trans_view.php?type_id=" . $trans_type . "&trans_no=" . $deposit_id . "'>View GL Entry</a>" );
		display_notification("<a href='../../sales/view/view_receipt.php?type_id=" . $trans_type . "&trans_no=" . $deposit_id . "'>View Payment and Associated Invoice</a>" );
***/
		$trx['URL'] = $this->viewTransURL( ST_CUSTPAYMENT, $fcp->payment_id );
		$trx['JEURL'] = $this->viewJournalEntryURL( ST_CUSTPAYMENT, $fcp->payment_id );
	}
	/**//***************************
	* Create any new customer from a transaction
	*
	* @params none
	* @returns none
	*******************************/
	function add_customer_form()
	{
	}
	/**//***************************
	* Match an Square deposit to an FA deposit
	*
	* 	Add records to 1_ksf_import_square_bank_transfer
	*
	*	Because this is a funds transfer from Square to FHS
	*	there are no Invoices nor Customers to match.
	*
	* TODO
	*	Rewrite into a MODEL class
	*
	* @since 20250316
	*
	* @params none
	* @returns none
	*******************************/
	function match_deposit_form()
	{
//We have total_collected, etc.
		$sql = "INSERT into " . TB_PREF . "ksf_import_square_bank_transfer (";
			$sql .= "trans_type, ";
			$sql .= "trans_no, ";
			$sql .= "square_deposit_id, ";
			$sql .= "total_collected, ";
			$sql .= "fees, ";
			$sql .= "net_collected ) ";
		$sql .= " SELECT " . $_GET['type'] . ", ";
		$sql .=        $_GET['type_no'] . ",";
		$sql .=        "deposit_id, ";
		$sql .=        "sum(total_collected), ";
		$sql .=        "sum(fees), ";
		$sql .=        "sum(net_total)";
		$sql .= " FROM " . TB_PREF . "ksf_import_square_transactions";
		$sql .= " WHERE deposit_id='" . $_GET['transaction_id'] . "'";
		//$sql .= " WHERE deposit_id='" . $_GET['deposit_id'] . "'";
		//$sql = "INSERT into " . TB_PREF . "ksf_import_square_bank_transfer ( 'square_deposit_id', trans_type, trans_no ) VALUES ( '" . $_GET['deposit_id'] . "', '" . $_GET['type'] . "', '" . $_GET['type_no'] . "' )";
		//var_dump( $sql );
		$res = db_query( $sql, "Couldn't insert transaction" );
		display_notification( "Inserted record " . db_insert_id() . " with values: " . $_GET['deposit_id'] . "::" . $_GET['type'] . "::" . $_GET['type_no'] );
	}
	/**//***************************
	* Match an Square payment to an FA Payment
	*
	*	Add records to 1_ksf_import_square_payments
	*
	* TODO
	*	Rewrite into a MODEL class
	*
	* @since 20250316
	*
	* @params none
	* @returns none
	*******************************/
	function match_payment_form()
	{

		require_once( 'class.ksf_import_square_payments.php' );
		$sp = new ksf_import_square_payments_model();
		$res = $sp->insertPaymentMatch( $_GET['transaction_type'], $_GET['transaction_id'], $_GET['type'], $_GET['type_no'] );
		$match_arr = $sp->getPaymentMatch( $_GET['type'], $_GET['type_no'] );
		$this->display_multirow_table( $match_arr, $this->assoc2header( $match_arr ) );
/*
		display_notification( "Inserted record " . db_insert_id() . " with values: " . $_GET['transaction_id'] . "::" . $_GET['type'] . "::" . $_GET['type_no'] );
		echo( "Inserted record " . db_insert_id() . " with values: " . $_GET['transaction_id'] . "::" . $_GET['type'] . "::" . $_GET['type_no'] );
*/
	}
	/**//***************************
	* View the items for a transaction
	*
	* @since 20250319
	*
	* @params none
	* @returns none
	*******************************/
	function transaction_items_form()
	{
		$res_arr = array();

		if( isset( $_GET['transaction_id'] ) )
		{
			$transaction_id = $_GET['transaction_id'];
		}
		else
		{
			display_error( "Transaction ID not set.  Needed for this screen!" );
		}
		if( isset( $_GET['debtor_no'] ) )
		{
			$debtor_no = $_GET['debtor_no'];
		}

		require_once( 'class.ksf_import_square_transactions.php' );
		$ist = new ksf_import_square_transactions_model();
		//$cust_arr = $ist->getCustomerNameForTransaction( $transaction_id );
		$trans_data_arr = $ist->getTransaction( $transaction_id );
		$tr_da = $trans_data_arr[0];
		$cust_id = $tr_da['Customer_id'];
		$cust_name = $tr_da['customer_name'];
		$cust_ref = $tr_da['customer_reference_id'];
		$tr_date = $tr_da['Date'];
		$cheader = array( 'Customer_id', 'customer_name', 'customer_reference_id', 'PAN_suffix' );
		echo "<H2>Transaction Customer Details</h2>";
		$this->display_multirow_table( $trans_data_arr, $cheader );

		$theader = array( 'Date', 'gross_sales', 'discounts', 'net_sales', 'tax', 'total_collected', 'card', 'cash', 'net_total' );
		if( $tr_da['card'] <> 0 )
		{
			$amount = $tr_da['card'];
			$account = $this->sourceConfig ? $this->sourceConfig->getGlAccount() : 0;
		}
		else
		if( $tr_da['cash'] <> 0 )
		{
			$amount = $tr_da['cash'];
			$account = $this->sourceConfig ? $this->sourceConfig->getCashGl() : 0;
		}
		echo "<H2>Transaction Details</h2>";
		$this->display_multirow_table( $trans_data_arr, $theader );
		$total_collected = $tr_da['total_collected'];
		$net_sales = $tr_da['net_sales'];
		//$total_collected = $trans_data_arr['total_collected'];
		$fa_debtor_no = $this->default_customer;
		if( strlen( $cust_name ) < 7 )
		{
			if( $cust_id > 0 )
			{
			}
		}
		else
		{
			if( isset( $debtor_no ) )
			{
				//We passed in the debtor_no from a different screen
				$fa_debtor_no = $debtor_no;
			}
			else
			{
				//Look for matching customer in EXTERNAL
				require_once( '../ksf_modules_common/class.ksf_import_external_customers_model.php' );
				$ec = new ksf_import_external_customers_model();
				$fa_cust_data = $ec->searchCustomersByName( $cust_name, $this->sourceConfig ? $this->sourceConfig->getSource() : 'unknown' );
				if( isset( $fa_cust_data['fa_debtor_no'] ) )
				{
					$fa_debtor_no = $fa_cust_data['fa_debtor_no'];
				}
				else
				{
					//fa_debtor_no still set to default customer
				}
			}
		}
		
		require_once( '../ksf_modules_common/class.fa_debtor_trans.php' );
		$dt = new fa_debtor_trans_model();
		$dt->set( "rounding_amount", $this->rounding_amount );
//var_dump( $dt );
		$match4_arr = $dt->findMatchingInvoices( $tr_date, ST_SALESINVOICE, $fa_debtor_no, true, $this->days_before, $this->days_after );
		$dt->set( "ov_amount", $net_sales );
		$match3_arr = $dt->findMatchingInvoices( $tr_date, ST_SALESINVOICE, null, false, $this->days_before, $this->days_after );
		$dt->unset_( "ov_amount" );
		$dt->set( "alloc", $total_collected );
		$match2_arr = $dt->findMatchingInvoices( $tr_date, ST_SALESINVOICE, null, true, $this->days_before, $this->days_after );
		$match_arr = $dt->findMatchingInvoices( $tr_date, ST_SALESINVOICE, $fa_debtor_no, true, $this->days_before, $this->days_after );
		$match5_arr = $dt->findMatchingInvoices( $tr_date, ST_SALESINVOICE, $fa_debtor_no, false, $this->days_before, $this->days_after );

		$tMTN = $this->extractMultirowTransNo( $match_arr );
		$tMTN2 = $this->extractMultirowTransNo( $match2_arr );
		$tMTN3 = $this->extractMultirowTransNo( $match3_arr );
		$tMTN4 = $this->extractMultirowTransNo( $match4_arr );
		$tMTN5 = $this->extractMultirowTransNo( $match5_arr );
		$trans_no_arr = array();
//Merge the arrays to get the master list of matching trans_no
		foreach( $tMTN as $row )
		{
			$trans_no_arr[$row['trans_no']] = $row;
		}
		foreach( $tMTN2 as $row )
		{
			$trans_no_arr[$row['trans_no']] = $row;
		}
		foreach( $tMTN3 as $row )
		{
			$trans_no_arr[$row['trans_no']] = $row;
		}	
		foreach( $tMTN4 as $row )
		{
			$trans_no_arr[$row['trans_no']] = $row;
		}	
		foreach( $tMTN5 as $row )
		{
			$trans_no_arr[$row['trans_no']] = $row;
		}	

//Create "Match this trans to a FA trans URL
		// $trx['MATCHURL'] = $this->viewMatchTransactionURL( $trx['type'], $trx['type_no'], $id );

//Load potential matching carts
		require_once( $path_to_root . "/sales/includes/cart_class.inc" );
		$cart_arr = array();
		foreach( $trans_no_arr as $row )
		{
			$cart = new Cart( $row['trans_type'], $row['trans_no'] );
			$cart_arr[$row['trans_no']] = $cart;
			//var_dump( $cart );
		}

		$mheader = array( 'tran_date', 'trans_no', 'debtor_no', 'branch_code', 'ov_amount', 'ov_gst', 'ov_discount', 'alloc' );
		echo "<H2>Matching Invoices and Payments</h2>";
//TODO
//	Matching Returns (Credit notes)
//	We don't currently handle NEGATIVE transactions (i.e. refunds)
			$mtp_count = 0;
			require_once( 'class.ksf_import_square_payments.php' );
			$sp = new ksf_import_square_payments_model();
			//var_dump( $tr_da['payment_id'] );
			$matchp_arr = $sp->searchIn( "square_payment_id", array( $tr_da['payment_id'] ) );
			if( count( $matchp_arr ) > 0 )
			{
				$mtp_count++;
			}
			$mtheader = $this->assoc2header( $matchp_arr );
			//$mtheader = array( 'square_transaction_id', 'sales_invoice_no' );
			$this->display_multirow_table( $matchp_arr, $mtheader );
			//echo "<br />" . __FILE__ . "::" . __LINE__ . "<br />";
			//var_dump( $matchp_arr );

			require_once( 'class.ksf_import_square_sales_model.php' );
			$ss = new ksf_import_square_sales_model();
			$matcht_arr = $ss->searchIn( "square_transaction_id", array( $transaction_id ) );
			if( count( $matcht_arr ) > 0 )
			{
				$mtp_count++;
			}
			$mtheader = $this->assoc2header( $matcht_arr );
			//$mtheader = array( 'square_transaction_id', 'sales_invoice_no' );
			$this->display_multirow_table( $matcht_arr, $mtheader );
			//echo "<br />" . __FILE__ . "::" . __LINE__ . "<br />";
			//var_dump( $matcht_arr );

		echo "<H2>Potential Matching Invoices</h2>";
		if( null !== $match_arr )
		{
			echo "<h3>Exact Date. Debtor set. Amount set</h3>";
			$match_arr = $this->viewMultirowTransTypeURL( $match_arr );
			$this->display_multirow_table( $match_arr, $mheader );
		}
		if( null !== $match2_arr )
		{
			echo "<h3>Exact Date.  Amount set</h3>";
			$match2_arr = $this->viewMultirowTransTypeURL( $match2_arr );
			$this->display_multirow_table( $match2_arr, $mheader );
		}
		if( null !== $match3_arr )
		{
			echo "<h3>Date range.  Amount set</h3>";
			$match3_arr = $this->viewMultirowTransTypeURL( $match3_arr );
			$this->display_multirow_table( $match3_arr, $mheader );
		}
		if( $mtp_count < 1 )
		{
			if( null !== $match4_arr )
			{
				echo "<h3>Exact Date for Debtor $debtor_no</h3>";
				$match4_arr = $this->viewMultirowTransTypeURL( $match4_arr );
				$this->display_multirow_table( $match4_arr, $mheader );
			}
			if( null !== $match5_arr )
			{
				echo "<h3>Date Range for Debtor $debtor_no</h3>";
				$match4_arr = $this->viewMultirowTransTypeURL( $match5_arr );
				$this->display_multirow_table( $match5_arr, $mheader );
			}
		}

		require_once( 'class.ksf_import_square_items.php' );
		$tri = new ksf_import_square_items_model();
		$trz = $tri->getTransaction( $transaction_id, 'invoice' );
		//var_dump( $trz );
		//while( $row = db_fetch_assoc( $res ) )		


		$th = array();	//header array
		foreach( $trz as $row  )
                {
//Thre is a possibility of none of the carts above match.
//An example is Robbie Burns where we sold the items through
//Square to the band at Cost+ on ONE invoice rather than 
//multiple.  So multiple Square transactions match 1 FA invoice.
//
//This will really cause a problem for this reconcilliation
//emd to end as we collected full retail from the AGS customers
//But only invoiced AGS the nets.  So the payments won't match.
//The invoice won't match for dollars.
			//var_dump( "<br />" );
                        //var_dump( __FILE__ . "::" . __LINE__ );
			//var_dump( "<br />" );
                        //var_dump( $row );
			//var_dump( "<br />" );
			//$row['display_items'] = "<a target=_blank href='" . $_SERVER['SCRIPT_NAME'] . "?action=transaction_items&transaction_id=" . $_GET['transaction_id'] . "'>View Transaction Items</a>";
			$match = "";
			$sales_item_id = $row['id'];
			unset( $row['id'] );
//TODO:
//	If the SKU is already matched to an invoice, we need to either disallow the change,
//	Or we would need to unmatch the invoice.
			if( $this->sourceConfig && $this->sourceConfig->isAllowSkuChange() )
			{
					$row['Item'] = "<a target=_blank href='" . $_SERVER['SCRIPT_NAME'] . "?action=edit_item_sku&sales_item_id=" . $sales_item_id . "&stock_id=" . $row['stock_id'] . "'>Edit SKU for " . $row['Item'] . "</a>";
			}
			else
			if( strlen( $row['stock_id'] ) < 2 OR "&quot;" == $row['stock_id'] )
			{
				if( strncasecmp( $row['Item'], "Custom Amount", 13 ) == 0 )
				{
					//We didn't select an item.  Provide a link to edit the SKU so it can match against invoices!
					$row['stock_id'] = "<a target=_blank href='" . $_SERVER['SCRIPT_NAME'] . "?action=edit_item_sku&sales_item_id=" . $sales_item_id . "'>Edit SKU for Custom Amount - " . $row['notes'] . "</a>";
				}
				else
				{
					//We could try to suggest a SKU by searching our ITEMS.
				}
			}

			//Check each of our CARTs from above to see if this line item is in the cart.
			foreach( $cart_arr as $trans_no => $order )
			{
				$row[$trans_no] = $trans_no;	//Create a column for the matching of items into the sales_invoice
				$match = "<a target=_blank href='" . $_SERVER['SCRIPT_NAME'] . "?action=match_transaction_invoice&transaction_id=" . $_GET['transaction_id'] . "&trans_no=" . $trans_no . "&account=" . $account . "&amount=" . $amount . "&Date=" . $tr_date . "'>Match Transaction to Invoice " . $trans_no . "</a>";
				foreach ($order->line_items as $order_item)
				{
					if (strcasecmp($order_item->stock_id, $row['stock_id']) == 0)
					{
						$match .= " SKU Match; ";
						if( $order_item->price * $order_item->quantity  == $row['gross_sales'] )
						{
							$match .= "Dollar Match; ";
						}
						else
						{
							$calculated_net =  $order_item->qty_dispatched * $order_item->price * (1 - $order_item->discount_percent);
							if( $calculated_net  == $row['net_sales'] )
							{
								$match .= "Dollar Match; ";
							}
							else
							{
								$match .= "Calc Net $calculated_net <> " . $row['net_sales'] . "; ";
							}
						}
						if( $order_item->quantity  == $row['quantity'] )
						{
							$match .= "Quantity Match; ";
						}
						if( $order_item->price * $order_item->quantity * $order_item->discount_percent  == $row['discounts'] )
						{
							$match .= "Discounts Match; ";
						}
						$row[$trans_no] = $match;
						//row is in this order
						//line_item (object): { ["id"]=> string(5) "12643" ["stock_id"]=> string(16) "HD-CAPE-BLACK-XS" ["item_description"]=> string(33) "^Inverness Cape - Dancer XS BLACK" 
						//			["price"]=> string(6) "119.05" ["discount_percent"]=> string(3) "0.1" ["standard_cost"]=> string(9) "45.169915" ["descr_editable"]=> string(1) "0" ["valid"]=> bool(true) 
						//			["quantity"]=> int(1) ["qty_done"]=> string(1) "0" ["qty_dispatched"]=> string(1) "1" ["qty_old"]=> string(1) "1" }
						break;
					}
				} 

			}
/*
*/
			foreach( $row as $key => $val )
			{
				if( ! in_array( $key, $th, true ) )
				{
					$th[] = $key;
					//var_dump( "<br />" );
                        		//var_dump( __FILE__ . "::" . __LINE__ );
					//var_dump( "<br />" );
                        		//var_dump( $th );
					//var_dump( "<br />" );
				}
			}
                        $res_arr[] = $row;

                }
		$this->displayMatchedTransNo( $trans_no_arr );
		//$res_arr['display_items'] = $URL = "<a target=_blank href='" . $_SERVER['SCRIPT_NAME'] . "?action=match_customer&debtor_no=" . $debtor_no;
		//$this->display_multirow_table( $trz, $th );
//TODO
//	Remove the already matched invoice numbers from the able to match lines!
//	If we have a result on mtp_count above ( we matched an invoice and/or payment) don't display additional match lines here
		echo "<H2>Transaction Line Items</h2>";
		$this->display_multirow_table( $res_arr, $th );
		//$this->display_transaction_table( $res_arr, "row" );

		if( $mtp_count < 1 )
		{
			$this->viewCreateDirectInvoice( $transaction_id, $res_arr, $tr_da );
		}
		else
		{
//TODO
			//Display an "Edit Invoice" link to edit the match above.
			//But if our lines match exactly above, we shouldn't need to...
		}
	}
	/***************************************************************//**
	* Create a Direct Invoice from a Square Transaction
	*
	* @since 20250327
	* 	
	* @param string transaction ID
	* @param array items to put into the cart
	* @param array transaction data
	* @returns none launches screen
	******************************************************************/
	function viewCreateDirectInvoice( $transaction_id, $items_arr, $transaction_data_arr = array() )
	{
		//If we import the same file (or date range) than a transaction
		//could have the same items multiple times.  We should sort the
		//items by update time, and see if we end up with duplicate carts.
		//IF a cart is duplicate, flag it, note it, and ignore it!
		//	Provide a way to delete the duplicate items?
		$trans_type = ST_SALESINVOICE;
		$cart = new Cart( $trans_type, 0 );
		$cart->document_date = sql2date( $items_arr[0]['Date'] );
	 	global $Refs;
                do {
                        $cart->reference = $Refs->get_next($trans_type);
                } while( ! is_new_reference( $cart->reference, $trans_type ) );

		//$cart->Comments =  $_POST['Comments'];
		$cart->Comments .=  "Import transaction " . $transaction_id . " on " .  $items_arr[0]['Date'];
		if( strlen( $transaction_data_arr['customer_name'] ) > 0 )
		{
			$cart->Comments .=  " by customer " . $transaction_data_arr['customer_name'];
		}	
		if( $transaction_data_arr['cash'] > 0 )
		{
			$cart->Comments .=  " paid $" . $transaction_data_arr['cash'] . " by cash.";
		}
		if( $transaction_data_arr['card'] > 0 )
		{
			$cart->Comments .=  " paid $" . $transaction_data_arr['card'] . " by card " . $transaction_data_arr['PAN_suffix'] . " ";
		}
//Terms is Square or Cash depending on the transaction
		/*
		$cart->payment = $_POST['payment'];
		$cart->payment_terms = get_payment_terms($_POST['payment']);
		*/
		$cart->due_date = $cart->document_date;
		//$cart->due_date = $_POST['delivery_date'];
		$cart->ship_via = 0;
		/** The following comes from the customer
		*$cart->cust_ref = $_POST['cust_ref'];
		*$cart->deliver_to = $_POST['deliver_to'];
		*$cart->delivery_address = $_POST['delivery_address'];
		*$cart->phone = $_POST['phone'];
		*$cart->ship_via = $_POST['ship_via'];
		*$cart->email =$_POST['email'];
		*$cart->customer_id	= $_POST['customer_id'];
		*$cart->Branch = $_POST['branch_id'];
		//We don't use the pricebook in Square.
		//$cart->sales_type = $_POST['sales_type'];
		*/
		$cart->cust_ref = $transaction_data_arr['PAN_suffix'];
		$cart->Location = $this->default_location;
		//$cart->freight_cost = input_num('freight_cost');
		$cart->dimension_id = $this->default_dimension1;
		$cart->dimension2_id = $this->default_dimension2;
		//$cart->ex_rate = input_num('_ex_rate', null);

		foreach( $items_arr as $row )
		{
			//add_to_order does some item manipulation and then calls $order->add_to_cart
			// The manipulation looks like it takes apart sales kits, as well as cases of items.
			global $path_to_root;
			$path_to_root = "../..";
			require_once( $path_to_root .  "/sales/includes/ui/sales_order_ui.inc" );
			add_to_order( $cart, $row['stock_id'], $row['quantity'], $row['gross_sales'], $row['discounts'], $row['Item'] );
		}

		//var_dump( $cart );
		//sales_order_entry expects the cart to be vin $_SESSION['Items'];
		$_SESSION['Items'] = $cart;
		//var_dump( $_SESSION );

		echo $this->salesInvoicePopUpURL( 0, false );



		//ecart_arr[$row['trans_no']] = $cart;
	}
	/*********************************************//******************
	* Display the list of transactions that have already been matched
	*
	* @since 20250326
	*
	* @param array list of trans_no
	* @returns none display HTML
	******************************************************************/
	function displayMatchedTransNo( $tr_arr )
	{
		if( is_array( $tr_arr ) AND count( $tr_arr ) > 0 )
		{
			$checklist = array();
			foreach( $tr_arr as $row )
			{
				$trans_no = $row['trans_no'];
				$checklist[] = $trans_no;
			}
			require_once( 'class.ksf_import_square_sales_model.php' );
			$ss = new ksf_import_square_sales_model();
			$res_arr = $ss->searchIn( "sales_invoice_no", $checklist );
			//var_dump( $res_arr );
			echo "<H2>Invoices that have already been matched</h2>";
			//$header = $this->arrayToHTMLHeader( $res_arr );
			$header = array( 'last_updated', 'square_transaction_id', 'sales_invoice_no' );
			$this->display_multirow_table( $res_arr, $header );
		}
	}
	/**//***************************
	* View the details for 1 square transaction
	*
	* @since 20250316
	*
	* @params none
	* @returns none
	*******************************/
	function view_transaction_form()
	{
		echo "<h2>View Transactions</h2>";
		$res_arr = array();
		$sales_orders = array();
			$tr = $this->o_transaction;
		require_once( '../ksf_modules_common/class.fa_sales_orders.php' );
		$so = new fa_sales_orders_model();
			//order_no, trans_type, debtor_no, branch_code, order_date, total
		if( isset( $this->days_before ) )
			$so->set( "days_before", $this->days_before );
		if( isset( $this->days_after ) )
			$so->set( "days_after", $this->days_after );

		$trz = $tr->getTransaction( $_GET['transaction_id'], 'invoice' );
		//var_dump( $trz );
		//while( $row = db_fetch_assoc( $res ) )
		foreach( $trz as $row  )
                {
			$customer_name = $row['customer_name'];
			$ord_date = $row['Date'];
			$cust_match = $this->getExternalCustomerMatchByName( $customer_name );	

			$debtor_no = $cust_match[0]['debtor_no'];
			//See if there is a matching sales order for this customer.  If not, try the default customer
	   			  //findMatchingInvoices( $ord_date, $trans_type = ST_INVOICE, $debtor_no = null, $b_exact_date = true )
			//var_dump( get_class_methods( $so ) );
			$found = false;
			$try_customers = array( $debtor_no, $this->default_customer, null );	//checking against null in case recorded against a different customer
			$exact_date_arr = array ( 'true', 'false' );
			$exact_date_count = count( $exact_date_arr );
			$cust_count = count( $try_customers );
			$maxloops = $cust_count * $exact_date_count;
			$loops = 0;
			do {
				for( $exact = 0; $exact < $exact_date_count; $exact++ )
				{
					for( $cust = 0; $cust < $cust_count; $cust++ )
					{
						
							$res = $so->findMatchingInvoices( $ord_date, ST_SALESINVOICE, $try_customers[$cust], $exact_date_arr[$exact] );
							//var_dump( $res );
							if( $res )
							{
								$this->displaySalesOrder( $res );
							}
							else
							{
							}
							$loops++;
						}
					}
				} while( true !== $found AND $loops <  $maxloops );
				$row['display_items'] = "<a target=_blank href='" . $_SERVER['SCRIPT_NAME'] . "?action=transaction_items&transaction_id=" . $_GET['transaction_id'] . "&debtor_no=" . $debtor_no . "'>View Transaction Items</a>";
				//$row['transaction_id'] = $this->viewDepositDetailsURL( $row['details'], $_GET['transaction_id'], "Transaction on Square" );
				$row['transactionURL'] = $this->viewDepositDetailsURL( $row['details'], $_GET['transaction_id'], "Transaction on Square" );
				$res_arr[] = $row;
			}
			//$res_arr['display_items'] = $URL = "<a target=_blank href='" . $_SERVER['SCRIPT_NAME'] . "?action=match_customer&debtor_no=" . $debtor_no;
			$this->display_transaction_table( $res_arr, "row" );

			require_once( 'class.ksf_import_square_sales_model.php' );
			$ss = new ksf_import_square_sales_model();
			$ss->set('square_transaction_id', $_GET['transaction_id'] );
			//$ss->set('sales_invoice_no', $trans_no );
			$this->displayMatchedTrans2Invoice( $ss );
	/*
	*/
			
		}
		/**//***************************
		* Match Square customer to FA customer
		*
		* @since 2025
		*
		* @params none uses _GET
		* @returns none
		*******************************/
		function match_customer_form()
		{
					//MATCH customer record to Square record
					//Customer has a debtors_no, branch, crm_person, crm_contact.
					//crm_contact has to be type customer.  If we have a debtor_no we probably have a contact.
					//	Contact should be either INVOICE or GENERAL.  General is the default creation
			require_once( 'class.ksf_import_external_customers_model.php' );
			$ec = new ksf_import_external_customers_model();

				$ec->set('external_customer_type', $this->sourceConfig ? $this->sourceConfig->getSource() : 'unknown') ;
				$ec->set('external_customer_id', $_GET['Customer_id'] );
				$ec->set('external_customer_name', $_GET['customer_name']);
				$ec->set('external_customer_reference', $_GET['customer_reference_id']  );
				$ec->set('fa_debtor_no', $_GET['debtor_no'] );
			if( isset( $_GET['fa_person_id'] ) )
			{
				$ec->set('fa_person_id', $_GET['fa_person_id'] );
			}
			if( isset( $_GET['fa_branch_code'] ) )
			{
				$ec->set('fa_branch_code', $_GET['fa_branch_code'] );
			}
			if( isset( $_GET['fa_crm_contacts_id'] ) )
			{
				$ec->set('fa_crm_contacts_id', $_GET['fa_crm_contacts_id'] );
			}
			if( isset( $_GET['ksf_import_other_table_name'] ) )
			{
				$ec->set('ksf_import_other_table_name', $_GET['ksf_import_other_table_name'] );
			}
			if( isset( $_GET['ksf_import_other_table_id'] ) )
			{
				$ec->set('ksf_import_other_table_id', $_GET['ksf_import_other_table_id'] );
			}
			//$ec->insert();
			//var_dump( "<br /><br />" . __LINE__ . "<br />" );
			$sql = $ec->insertMatchSQL();
			//var_dump( $sql );
			//$res = db_query( $sql, "Can't insert match" );
			db_query( $sql, "Can't insert match" );
	//		$ec->insert_data( get_object_vars($ec) );
			
			display_notification( "Inserted record " . @db_insert_id() . " with values: " . $_GET['customer_name'] . "::" . $_GET['debtor_no'] . "::" . $_GET['customer_reference_id'] );
			echo ( "Inserted record " . @db_insert_id() . " with values: " . $_GET['customer_name'] . "::" . $_GET['debtor_no'] . "::" . $_GET['customer_reference_id'] );
		}
		/**//***************************
		* Insert the match between a transaction and an invoice.
		*
		* @since 2025
		*
		* @params none
		* @returns none
		*******************************/
		function match_transaction_invoice_form()
		{
			require_once( 'class.ksf_import_square_sales_model.php' );
			$ss = new ksf_import_square_sales_model();
			$ss->set('square_transaction_id', $_GET['transaction_id'] );
			$ss->set('sales_invoice_no', $_GET['trans_no'] );
			$ss->insert();
			$last_inserted = db_insert_id();
			display_notification( "Inserted record " . $last_inserted . " with values: " . $_GET['transaction_id'] . "::" . $_GET['trans_no']   );
			$this->displayMatchedTrans2Invoice( $ss );
			/*
			*	$row = $ss->selectByVars();
			*	//var_dump( $row );
			*	$res_arr = $row;
			*	//$res_arr[] = $row;
			*	$header = $this->assoc2header( $row );
			*	$this->display_multirow_table( $res_arr, $header );
			*/
	//TODO:
		//Offer to update the record with the sales_order_no and sales_delivery_no
		//Delivery links to Order.  Order links to Delivery, Invoice, Credit Notes.
		//Invoice links to Payment, Order, Delivery
			
		}
		/**************************************************//**
		* Display matching Payment records and allow Matching
		*
		*	Moved out of dsiplayMatchedTrans2Invoice
		*
		* @since 20250326
		* 
		* @param string Square transsaction ID
		* @param string account number
		* @param float Payment Amount
		* @param float rounding amount for matching
		* @param date Date string
		* @param int Days Before for matching
		* @param int days after for matching
		* @param array Already Matched trans_no
		* @param bool Should we display the Match Payment link
		* @returns none displays HTML
		*******************************************************/
		function displayMatchingPaymentsMatch( $transaction_id, $account, $amount, $rounding_amount = 0.01, $Date, $days_before = 2, $days_after = 5, $already_matched = array(), $display_match_link = true )
		{
				echo "<h2>Matching Payments</h2>";
				echo "<p>If there are multiple payments it could be a Market situation where we sold the identical item in multiple separate transactions.</p>";
				echo "<p>It could also be having many items with the same price. </p>";
				if( $display_match_link )
				{
					echo "<p><b>Ensure you have checked the invoice for the correct matching payment.</b>  Otherwise the Sanity Check reports will flag this transaction!</p>";
				}
					echo "<p>Just because there is only one matching payment doesn't mean it's the correct one!</p>";
				echo "<p>Matching on amount ";
				echo  $amount . " with rounding of ";
				echo  $rounding_amount . " within days ";
				echo  $days_before . "::" . $days_after . " from " . $Date . " in account " . $account . "</p>";
				require_once( '../ksf_modules_common/class.fa_gl.php' );
				$fa_gl = new fa_gl();
				$fa_gl->set( "rounding_amount", $rounding_amount );
				$trx = array();
				$trx = $fa_gl->findMatchingTransactions( array( ST_CUSTPAYMENT ), $amount , $Date, $days_before, $days_after, $account, $already_matched );
				if( false !== $trx and count( $trx ) > 0 )
				{
					foreach( $trx as $row )
					{
						$row['URL'] = $this->viewTransURL( $row['type'], $row['type_no'] );
						if( $display_match_link )
						{
							$row['MatchURL'] = $this->viewMatchTransactionURL( $row['type'], $row['type_no'], $transaction_id, "transaction_id" );
						}
						$this->display_table( $row, "row" );
					}
				}
		}
		/**************************************************//**
		* Display matched transactions to invoices
		*
		* @since 20250324
		*
		* @param object import_square_sales_model
		* @returns none
		*******************************************************/
		function displayMatchedTrans2Invoice( $ss )
		{
			$row = $ss->selectByVars();
			$res_arr = array();
			//var_dump( $row );
			// 	array(1) { [0]=> array(6) { ["ksf_import_square_sales_id"]=> string(3) "103" ["last_updated"]=> string(19) "2025-03-24 22:15:46" 
					//["square_transaction_id"]=> string(25) "3MveOCyqvNsLEl04JHlAtd7eV" ["sales_order_no"]=> string(0) "" 
					//["sales_delivery_no"]=> string(0) "" ["sales_invoice_no"]=> string(4) "1336" } }
			foreach( $row as $res )
			{
				if( isset( $res['sales_invoice_no'] ) AND strlen( $res['sales_invoice_no'] > 1 ) )	
					$res['sales_invoice_no'] = $this->viewTransTypeURL( ST_SALESINVOICE, $res['sales_invoice_no'], true );
				if( isset( $res['sales_delivery_no'] ) AND strlen( $res['sales_delivery_no'] > 1 ))	
					$res['sales_delivery_no'] = $this->viewTransTypeURL( ST_CUSTDELIVERY, $res['sales_delivery_no'], true );
				if( isset( $res['sales_order_no'] ) AND strlen( $res['sales_order_no'] > 1 ) )	
					$res['sales_order_no'] = $this->viewTransTypeURL( ST_SALESORDER, $res['sales_order_no'], true );
				$transaction_id = $res['square_transaction_id'];
				$res['square_transaction_id'] = $this->viewTransactionDetailsURL( $transaction_id );
				$res_arr[] = $res;
			}
			if( isset( $_GET['amount'] ) )
			{
				echo "<div>";
				echo "<h3>Matched Transaction to Invoice/Sales Order/Delivery</h3>";
				$header = $this->assoc2header( $row );
				if( count( $res_arr ) > 0 )
				{
					$this->display_multirow_table( $res_arr, $header );
				}
				else
				{
					//No matching invoices to display
				}
				echo "</div>";
				$this->displayMatchingPaymentsMatch( $transaction_id, $_GET['account'], $_GET['amount'], $this->rounding_amount, $_GET['Date'], $this->days_before, $this->days_after, null );
			}
	}
	/***********************************************//**
	* Assoc array to header array
	*
	* @since 20250324
	*
	* @param array multidimensional array
	* @returns array
	***************************************/
	function assoc2header( $arr )
	{
		//$header = array();
		foreach( $arr as $row) 
		{
			$header = array_keys( $row );
			return $header;
		}
	}
	/**//***************************
	* Instructions on how to use this module
	*
	* @since 2025324
	*
	* @params none
	* @returns none
	*******************************/
	function usage_form()
	{
		echo "<h1>How to Use this Module</h1>";
		echo "<p>This module allows you to import transactions from external sources into Front Accounting.</p>";
		echo "<p>Once the data is imported, you can then match the imported transactions against transactions you have already entered into Front Accounting.</p>";
		echo "<h2>Import Data</h2></p>";
		echo "<p>You need to import both the Transactions CSV as well as the Items CSV.</p>";
		echo "<p>The transaction CSV gives you the associated data between customers, the transaction, their payment, and the associated bank deposit.  These are used to match or create both Customer Payments and Bank Deposits.</p>";
		echo "<p>The Items CSV gives you the details for the transaction.  It lists how many of each sku, charges, taxes, fees, etc.  These are used to match Sales Orders, Deliveries, and Invoices.</p>";
		echo "<h2>The Main Screens</h2>";
		echo '<p>The main screens are "Process Transactions", "Process Payments", and "Process Deposits".</p>';
		echo "<h3>Import Files</h3>";
		echo "<p>This screen is where you select what file type, and what files(s) you want imported.";
		echo "<p><b>NOTE that importing the same file multiple times will cause your transaction to look like it had way more items.</b>";
		echo "<p>The transactions import catches duplicate imports due to database indexes.  The Items table can't";
		echo "<h3>Process Transactions</h3>";
		echo "<h4>Transactions Items</h4>";
		echo "<p>The Transaction items screen displays details about the customer related to the transaction, basic details on the transaction, as well as potential Front Accounting invoices.</p>";
		echo "<p>The screen also lists all of the line items as sent by the source.  These will be matched against the line items of the possible matching Invoices</p>";
		echo "<p>You can indicate the matching invoice on this screen.</p><p><b>NOTE</b> Here is where you will see duplicated line items if you import a transaction file (or overlapping date ranges) multiple times.</p>";
		echo "<h3>Process Payments</h3>";
		echo "<h3>Process Deposits</h3>";
		echo "<h2>Config</h2>";
		echo "<p>The config screen has the following variables:</p>";
		echo "<ul>";
		echo "<li>Environment (unused) - designed for testing but not coded.</li>";
		echo "<li>Debug Level.  How much detail for troubleshooting.</li>";
		echo "<li>Default Customer - who to use for creating invoices when the source doesn't tell us who the customer was.  I have an <i>0Cash Customer</i> for using at Farmers Markets, Highland Games, etc when we don't know who is purchasing</li>";
		echo "<li>How many days to look before the import date for matching Sales Invoices we already entered.</li>";
		echo "<li>How many days to look after the import date for matching Sales Invoices we already entered.</li>";
		echo "<li>Rounding Amount - tolerance for amount matching when searching for matching transactions.</li>";
		echo "<li>Default Location - which inventory location transactions are sold out of.</li>";
		echo "<li>Default Dimension 1/2 - which FA dimensions to assign to imported transactions.</li>";
		echo "</ul>";
		
	}
	/**
	 * Detect GL account mismatch between staged transaction and matched FA transaction.
	 *
	 * When a staged transaction is matched to an existing FA transaction, this
	 * method compares the GL account configured in the source module's config
	 * against the GL account actually used by the FA transaction. A mismatch
	 * indicates the source module and the FA transaction use different payment
	 * destination GL accounts.
	 *
	 * @param array $stagedRow The staged transaction row
	 * @param array $matchedFaTrans The matched FA transaction from findMatchingTransactions
	 * @return array ['mismatch' => bool, 'source_gl' => int, 'fa_gl' => int, 'detail' => string]
	 */
	function detectGlMismatch($stagedRow, $matchedFaTrans)
	{
		$result = ['mismatch' => false, 'source_gl' => 0, 'fa_gl' => 0, 'detail' => ''];

		if (!$this->sourceConfig) {
			return $result;
		}

		// Determine source GL from payment type
		$sourceGl = 0;
		if (isset($stagedRow['card']) && (float)str_replace(',', '', $stagedRow['card']) > 0) {
			$sourceGl = $this->sourceConfig->getGlAccount();
		} elseif (isset($stagedRow['cash']) && (float)str_replace(',', '', $stagedRow['cash']) > 0) {
			$sourceGl = $this->sourceConfig->getCashGl();
		}

		if ($sourceGl <= 0) {
			return $result;
		}

		// Get FA transaction's GL account from the matched record
		$faGl = 0;
		if (isset($matchedFaTrans['account_code'])) {
			$faGl = (int)$matchedFaTrans['account_code'];
		} elseif (isset($matchedFaTrans['account'])) {
			$faGl = (int)$matchedFaTrans['account'];
		}

		if ($faGl <= 0) {
			return $result;
		}

		$result['source_gl'] = $sourceGl;
		$result['fa_gl'] = $faGl;

		if ($sourceGl !== $faGl) {
			$result['mismatch'] = true;
			$result['detail'] = sprintf(
				_('GL Mismatch: Source configured GL %d, but FA transaction uses GL %d'),
				$sourceGl,
				$faGl
			);
		}

		return $result;
	}

	/**//***************************
	*
	*
	* @since 2025
	*
	* @params none
	* @returns none
	*******************************/
	function XXX_form()
	{
	}
	/**//***********************************************
	*
	****************************************************/	
	function addCustomer()
	{
		//display_notification( __FILE__ . "::" . __LINE__ );
		if( isset( $_POST['AddCustomer'] ) ) 
		{
		    	//display_notification( __FILE__ . "::" . __LINE__ );
			foreach( $_POST['AddCustomer'] as $key => $value )
			{
				//display_notification( __FILE__ . "::" . __LINE__ );
			 	//display_notification( print_r( $_POST['AddCustomer'], true )  );
			 	//display_notification( print_r( $key . "::" . $_POST["vendor_short_$key"]  . "::" . $_POST["vendor_long_$key"], true )  );
			 	$trz = $this->getTransaction($key);	//originally get_transaction($key)
					//also sets this->trz
				// display_notification( __FILE__ . "::" . __LINE__ );
				$custid = my_add_customer( $trz );
				if( $custid > 0 )
				{
					      //display_notification( __FILE__ . "::" . __LINE__ );
					display_notification( "Created Customer ID $custid"  );
				} else
				{
					//display_notification( __FILE__ . "::" . __LINE__ );
					display_warning( "Failedto create a Customer"  );
				}
			}
		}
		//display_notification( __FILE__ . "::" . __LINE__ );

	}
                /**//*************************************************************
                * Create the URL for a popup window to display a specific customer
                *
                *       NOTE NOTE NOTE
                *               This function is in _view, but that class
                *               is giving problems.
                *
                * @since 20250316
                *
                * @param int debtor_no
                * @param string customer name
		* @param array transaction data
                * @returns string URL
                *********************************************************************/
                function matchCustomerURL( $debtor_no, $name = "", $sq_row )
                {
			//var_dump( $sq_row );
			$URL = "<a target=_blank href='" . $_SERVER['SCRIPT_NAME'] . "?action=match_customer&debtor_no=" . $debtor_no;
			$URL .= "&Customer_id=";
			if( isset( $sq_row['Customer_id'] ) )
			{
				$URL .= $sq_row['Customer_id'];
			}
			$URL .= "&customer_name=";
			if( isset( $sq_row['customer_name'] ) )
			{
				$URL .= $sq_row['customer_name'];
			}
			$URL .= "&customer_reference_id=";
			if( isset( $sq_row['customer_reference_id'] ) )
			{
				$URL .= $sq_row['customer_reference_id'];
			}
			$URL .= "'>Match Customer " . $sq_row['customer_name'] . " to FA Customer " . $name . " </a>";
			return $URL;
                        //return "sales/manage/customers.php?debtor_no=" . $debtor_no . "&popup=1";
                }

	/**//***************************
	* Format either a "view Customer Record" link or an "Add Customer" link
	*
	* @since 20250316
	*
	* @params array customer details: customer name, * Square customer ID * Square customer Reference ID * Square CC/Debit PAN suffix * Square staff member name to be matched to salesman (create).  
	* @returns array HTML URL(s)
	*******************************/
	function viewCustomerDetailsURL( $myrow )
	{
		$ret_arr = array();
		if( strlen( $myrow['customer_name'] ) > 0 )
		{
			$c_row = $this->getExternalCustomerMatchByName( $myrow['customer_name'] );
			if( null == $c_row )
			{
				//we haven't previously matched the customer, search!
				$c_row = $this->getFACustomerMatchByName( $myrow['customer_name'] );
			}
			if( null != $c_row )
			{
				foreach( $c_row as $crow )
				{
					$ret_arr[] = $this->customerPopUpURL( $crow['debtor_no'], $crow['name'] ) . "<br />";
					$this->set( "debtor_no", $crow['debtor_no'] );
	
					if( ! isset( $crow['ksf_import_external_customers_id'] ) )
					{
						//MATCH customer record to Square record
						//Customer has a debtors_no, branch, crm_person, crm_contact.
						//crm_contact has to be type customer.  If we have a debtor_no we probably have a contact.
						//	Contact should be either INVOICE or GENERAL.  General is the default creation
						$ret_arr[] = $this->matchCustomerURL( $crow['debtor_no'], $crow['name'], $myrow );
					}
					else
					{
						//We've previously matched this customer name in Sq to FA.
					}
				}
			}
			else
			{
				if( isset( $myrow['Customer_id'] ) AND 0 !== $myrow['Customer_id'] )
				{
					$custid = $myrow['Customer_id'];
						/**
							$ret = submit("Add Payment", $row['payment_id'], true, _("AddPayment"), true, '');
							//$ret = button("Add Payment", $row['payment_id'], _("AddPayment"), false, '');
							$ret .= hidden( "payment_id", $row['payment_id'], false );
							$ret .= hidden( "action", "addPayment", false );
						**/
					$ret_arr[] = start_form() . submit("AddCustomer_$custid",_("AddCustomer"),false, 'Add Customer', 'default') . hidden("custid", $custid, false ) . end_form();
				}
			}
		}
		return $ret_arr;
	}
	/**/
	/**//***************************
	* Retrieve an FA debtor_no that we previously matched to a SQ customer name
	*
	* @since 20250318
	*
	* @params string customer name
	* @returns array|null customer's details - 1st match
	*******************************/
	function getExternalCustomerMatchByName( $customer_name )
	{
		//return null;
		require_once( 'class.ksf_import_external_customers_model.php' );
		//require_once( '../ksf_modules_common/class.ksf_import_external_customers.php' );
		$cust = new ksf_import_external_customers_model();
		$cust->set( "external_customer_name", $customer_name );
		$cust->set( "external_customer_type", $this->sourceConfig ? $this->sourceConfig->getSource() : 'unknown' );	
		$count = 0;
		try
		{	
			$ret = array();
			$res = $cust->searchCustomersByName();
			if( false !== $res AND count( $res ) > 0 )
			{
/*
					var_dump(  "<br />" );
					var_dump( __FILE__ . "::" . __LINE__ . "<br />" );
					var_dump( $res );
					var_dump(  "<br />" );
*/
				//should be an assoc row
				//Will return the 1st one only
/*
				foreach( $res as $row )
				{
					var_dump( __FILE__ . "::" . __LINE__ . "<br />" );
					var_dump( $row );
					var_dump(  "<br />" );
*/
				$row = $res;	//1 row returned from ->search...
					//We aren't just passing back the row due to column names not matching
					//setup for using the foreach above...
					$r = array();
					$r['debtor_no'] = $row['fa_debtor_no'];
					$r['person_id'] = $row['fa_person_id'];
					$r['branch_code'] = $row['fa_branch_code'];
					$r['crm_contacts_id'] = $row['fa_crm_contacts_id'];
					$r['name'] = $customer_name;
					$r['ksf_import_external_customers_id'] = $row['ksf_import_external_customers_id'];
					$ret[] = $r;
/*
				}
*/	
			unset( $cust );
			return $ret;
			}
		} catch ( Exception $e )
		{
			throw $e;
		}
		return null;
	}
	/**//***************************
	* Retrieve an FA customer from a SQ customer name
	*
	* @since 20250318
	*
	* @params string customer name
	* @returns array|null list of customers
	*******************************/
	function getFACustomerMatchByName( $customer_name )
	{
		require_once( '../ksf_modules_common/class.fa_customer.php' );
		$cust = new fa_customer();
		$cust->set( "CustName", $customer_name );
		$res = $cust->searchCustomersByName();
		if( $res )
		{
			$dm = $cust->get( "debtors_master" );
			$c_row = $dm->get( "debtors_arr" );
			return $c_row;
		}
		return null;
	}
	/**//***************************
	* Format either a "view Invoice" link or an "Add Invoice" link
	*
	* @since 20250318
	*
	* @params array 
	* @returns array HTML URL(s)
	*******************************/
	function viewInvoicesDetailsURL( $myrow )
	{
		$ret_arr = array();


		require_once( '../ksf_modules_common/class.fa_sales_orders.php' );
		$so = new fa_sales_orders();

		//If we have a matched Square customer to FA customer, we can get the debtor_no
		//Otherwise we can try to match a SQ customer to FA customer
		$c_row = $this->getFACustomerMatchByName( $myrow['customer_name'] );
		if( isset( $c_row[0]['debtor_no'] ) )
		{
			$debtor_no = $c_row[0]['debtor_no'];
		}
		else
		{
			$debtor_no = $this->default_customer;
		}

		$res_arr = $so->findMatchingInvoices( $myrow['Date'], ST_INVOICE, $debtor_no, false );
		//A sales order record has a total field, which should match the total_collected field...
		//It's possible a customer bought from us multiple times a day
		//Possible matches:
		//	Perfect match - sale has the same item and totals on both the Sq record and the FA record
		//			Need to "Match" the record and mark settled!
		//	Imperfect match - dollar total matches, item count is correct, but the SKUs don't match.
		//			NEED TO FLAG so we can check SKUs
		//	Imperfect match - dollar total DOES NOT match, item count is correct,SKUs might match.
		//			Definately need to check both records for the discrepencies and fix
		//	No Match - only 1 record each
		//			This will probably be a data error then.  Or wrong customer associated.
		//	No FA record
		//		Need to insert

		if( count( $res_arr ) > 0 )
		{
			//var_dump( $res_arr );
			//	$ret_arr[] = $this->invoicePopUpURL( $crow['debtor_no'], $crow['name'] ) . "<br />";
			//	$ret_arr[] = $this->matchInvoiceURL(  );
		}
		else
		{
			$res_arr = $so->findMatchingInvoices( $myrow['Date'], ST_INVOICE, null, false );
			if( count( $res_arr ) > 0 )
			{
				//var_dump( $res_arr );
			}
			else
			{
				$ret_arr[] = start_form() . submit("AddInvoice",_("AddInvoice"),false, 'Add Invoice', 'default') . hidden("sq_trans_id", $XXXXXXX, false ) . end_form();
			}
		}
		return $ret_arr;
	}
	/**/
	/**//***************************
	* Edit the SKU of an item
	*
	* @since 20250324
	*
	* @params none
	* @returns none
	*******************************/
	function updateSalesItemSKU( $sales_item_id, $stock_id )
	{
		require_once( 'class.ksf_import_square_items.php' );
		$tri = new ksf_import_square_items_model();
		try
		{
			$tri->set( "id", $sales_item_id );
			$tri->set( "stock_id", $stock_id );
			//$tri->update();
			$res = $tri->table_interface->update_table();
			//var_dump( $res );
			//$sql = $tri->table_interface->get( "sql" );
			//var_dump( $sql );
			
		} catch ( Exception $e )
		{
			display_error( $e->getMessage() );
		}
	}
	/**//***************************
	* Edit the SKU of an item
	*
	* @since 20250324
	*
	* @params none
	* @returns none
	*******************************/
	function edit_item_sku_form()
	{
		//var_dump( $_GET );
		//var_dump( $_POST );
		if( isset( $_POST['stock_id'] ) )
		{
			$stock_id = $_POST['stock_id'];
		}
		else if( isset( $_GET['stock_id'] ) )
		{
			$stock_id = $_GET['stock_id'];
		}
		if( isset( $_GET['sales_item_id'] ) )
			$sales_item_id = $_GET['sales_item_id'];
		else
		if( isset( $_POST['sales_item_id'] ) )
			$sales_item_id = $_POST['sales_item_id'];

		$updateSKUValue = "Update the SKU";

		if( isset( $_POST['UpdateSKU'] ) AND $updateSKUValue == $_POST['UpdateSKU'] )
		{
			$this->updateSalesItemSKU( $_POST['sales_item_id'], $_POST['stock_id'] );
			return;
		}

		$js = '';
		$js .= get_js_open_window(900, 500);
		$js .= get_js_date_picker();
		$_SESSION['page_title'] = _( $help_context = "Alter SKU for Custom Amount" );

	//	page($_SESSION['page_title'], false, false, "", $js);

		global 	$Ajax;
	  	$Ajax->activate('items_table');
  		set_focus('stock_id');
  		set_focus('_stock_id_edit');

		start_form(true);
			start_table(TABLESTYLE_NOBORDER);
				start_row();
    				stock_items_list_cells(_("Select an item:"), 'stock_id', $stock_id,
					  _('New item'), true, check_value('show_inactive'));
				$new_item = get_post('stock_id')=='';
				check_cells(_("Show inactive:"), 'show_inactive', null, true);
				end_row();
			end_table();

			if (get_post('_show_inactive_update')) {
				$Ajax->activate('stock_id');
				set_focus('stock_id');
			}

			div_start('details');
				$stock_id = get_post('stock_id');
			div_end();

		submit_center_first('UpdateSKU', $updateSKUValue,
		    _('save SKU'), 'default');
		submit_center_last('CancelSKU', "Cancel the change",
	   		_('Cancels changes.'), true);
		submit_js_confirm('UpdateSKU', _('You are about to change the SKU.\nDo you want to continue?'));

		hidden('popup', @$_REQUEST['popup']);
		hidden('action', "edit_item_sku" );
		hidden('sales_item_id', "$sales_item_id" );
		end_form();

	}
}

?>

