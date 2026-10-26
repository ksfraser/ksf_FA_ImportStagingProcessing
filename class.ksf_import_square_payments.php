<?php

/****************************************************************************************
*
*       **      MANTIS 2368     Staging for ITEMS       **
*       **      MANTIS 3073     MODEL for ITEMS		**
*       **      MANTIS 3078     MODEL - Default Customer**
*       **      MANTIS 3028     EDIT items		**
*
 * *************************************************************************************/

/****************************************************************************************
 * Table and handling class for staging of imported financial data
 *
 * This table will hold each record that we are importing.  That way we can check if
 * we have already seen the record when re-processing the same file, or perhaps one
 * from the same source that overlaps dates so we would have duplicate data.
 *
 * *************************************************************************************/


$path_to_root = "../..";

/*******************************************
 * If you change the list of properties below, ensure that you also modify
 * build_write_properties_array
 * */

//TODO
//	Update the queries in the functions to use $this->table_details['tablename'] instead of .TB_PREF."bi_transactions 

/*
 *
 * Each import type needs to read in the source document, and process line by line placing a record into this class.
 * This class then needs to insert the record.
 *
 * This table should not have any views (forms).
 * */

require_once( __DIR__ . '/../ksf_modules_common/class.generic_fa_interface.php' );
require_once( __DIR__ . '/../ksf_modules_common/defines.inc.php' );

/**//**************************************************************************************************************
* A DATA class to handle the storage and retrieval of bank records.  STAGE the records before processing into FA.
*
*
*
*	***** WARNING *** WARNING *** WARNING *****
*	MySQL has a row limit of 4k.  Having a bunch of large fields can lead to errors and issues.
*
*	+---------------------------+-------------+------+-----+---------------------+-------------------------------+
*	| Field                     | Type        | Null | Key | Default             | Extra                         |
*	+---------------------------+-------------+------+-----+---------------------+-------------------------------+
*	| square_import_payments_id | int(11)     | NO   | PRI | NULL                | auto_increment                |
*	| last_updated              | timestamp   | NO   |     | current_timestamp() | on update current_timestamp() |
*	| square_payment_id         | varchar(32) | NO   | UNI | NULL                |                               |
*	| total_collected           | float       | NO   |     | NULL                |                               |
*	| trans_type                | int(11)     | NO   |     | NULL                |                               |
*	| trans_no                  | varchar(32) | NO   |     | NULL                |                               |
*	+---------------------------+-------------+------+-----+---------------------+-------------------------------+
*	
* Inherits:
        *    ORIGIN
        *       function __construct( $loglevel = PEAR_LOG_DEBUG )
        *       function set_var( $var, $value )
        *       function get_var( $var )
        *       function var2data()
        *       function fields2data( $fieldlist )
        *       function LogError( $message, $level = PEAR_LOG_ERR )
        *       function LogMsg( $message, $level = PEAR_LOG_INFO )
         *    DB_BASE
        *       function __construct( $host, $user, $pass, $database, $prefs_tablename )
        *       function connect_db()
        *       function is_installed()
        *       function set_prefix()
        *       function create_prefs_tablename()
        *       function mysql_query( $sql = null, $errmsg = NULL )
        *       function set_pref( $pref, $value )
        *       function get_pref( $pref )
        *       function loadprefs()
        *       function updateprefs()
        *       function create_table( $table_array, $field_array )
        *    GENERIC_FA_INTERFACE
        *       function __construct( $host, $user, $pass, $database, $pref_tablename )
        *       function eventloop( $event, $method )
        *       function eventregister( $event, $method )
        *       function add_submodules()
        *       function module_install()
        *       function install()
        *       function loadprefs()
        *       function updateprefs()
        *       function checkprefs()
        *       function call_table( $action, $msg )
        *       function action_show_form()
        *       function show_config_form()
        *       function form_export()
        *       function related_tabs()
        *       function show_form()
        *       function base_page()
        *       function display()
        *       function run()
        *       function modify_table_column( $tables_array )
        *        function adjust_stock_id_lengths( $barcode_max_length, $sku_length, $stock_id )
        *       / *@fp@* /function append_file( $filename )
        *       /*@fp@* /function overwrite_file( $filename )
        *       /*@fp@* /function open_write_file( $filename )
        *       function write_line( $fp, $line )
        *       function close_file( $fp )
        *       function file_finish( $fp )
        *       function backtrace()
        *       function write_sku_labels_line( $stock_id, $category, $description, $price )
        *       function show_generic_form($form_array)
	*    GENERIC_FA_INTERFACE_MODEL
	* 	TBD
* Provides:
*	function define_table()
*	function set( $field, $value = null, $enforce = true )
*	function arr2obj( $in_arr )
*	function insert_transaction()
*	function summary_sql( $TransAfterDate, $TransToDate, $statusFilter )
*	function reset_transactions($tid, $cids, $trans_no, $trans_type)
*	function update_transactions($tid, $cids, $status, $trans_no, $trans_type, $matched = 0, $created = 0, $g_partner = null, $g_option = "" )
*	function update_transactions_account($tid, $account, $accountName )
*	function db_prevoid( $trans_type, $trans_no )
*	function get_transaction( $tid = null)
*	function get_normal_pairing( $account = null)
*	function trz2obj( $trz )
*	function hand_insert_sql()
*	function hand_update_sql()
*	function trans_exists()
*	function update( $arr )
*
*	+---------------------------+-------------+------+-----+---------------------+-------------------------------+
*	| Field                     | Type        | Null | Key | Default             | Extra                         |
*	+---------------------------+-------------+------+-----+---------------------+-------------------------------+
*	| square_import_payments_id | int(11)     | NO   | PRI | NULL                | auto_increment                |
*	| last_updated              | timestamp   | NO   |     | current_timestamp() | on update current_timestamp() |
*	| square_payment_id         | varchar(32) | NO   | UNI | NULL                |                               |
*	| total_collected           | float       | NO   |     | NULL                |                               |
*	| trans_type                | int(11)     | NO   |     | NULL                |                               |
*	| trans_no                  | varchar(32) | NO   |     | NULL                |                               |
*	+---------------------------+-------------+------+-----+---------------------+-------------------------------+
******************************************************************************************************************/
class ksf_import_square_payments_model extends generic_fa_interface_model {
	protected $square_import_payments_id;		//!< Index of table
	protected $last_updaated         ;	//!< timestamp   | NO   |     | current_timestamp() |       |
	protected $square_payment_id        ;	//!<string	varchar(32) | NO   | UNI | NULL                |                               |
	protected $total_collected          ;	//!<float	float       | NO   |     | NULL                |                               |
	protected $trans_type               ;	//!<int		int(11)     | NO   |     | NULL                |                               |
	protected $trans_no                 ;	//!<string	varchar(32) | NO   |     | NULL                |                               |


	function __construct()
	{
		//display_notification( __FILE__ . "::" . __LINE__ );
		parent::__construct( null, null, null, null, null);
		//display_notification( __FILE__ . "::" . __LINE__ );
		$this->iam = "import_square_payments";
		$this->define_table();
		$this->matched = 0;
		$this->created = 0;
	}
	/**//***************************************************************
	* Describe the table we are the MODEL for so that auto code can work.
	*
	* @param none
	* @returns none
	**********************************************************************/
	function define_table()
	{
		//$ind = "id";
		//$ind = "id_" . $this->iam;
		$ind = $this->iam . "_id";
		$fields_array = $this->fields_array;
		$fields_array[] = array('name' => $ind, 'type' => 'int(11)', 'auto_increment' => 'yes', 'readwrite' => 'read' );
		$fields_array[] = array('name' => 'last_updated', 'type' => 'timestamp', 'null' => 'NOT NULL', 'default' => 'CURRENT_TIMESTAMP', 'readwrite' => 'read' );
		if( strlen( $this->company_prefix ) < 2 )
                {
                        $this->company_prefix = TB_PREF;
                }
		$table_details =  $this->table_details;	//declared array in parent constructor
                $table_details['tablename'] = $this->company_prefix . "ksf_" . $this->iam;
		$table_details['primarykey'] = $ind;
		$table_details['orderby'] = 'valueTimestamp, id';
		//$table_details['orderby'] = 'transaction_date, transaction_id';
		$table_details['index'][0]['type'] = 'unique';
		$table_details['index'][0]['columns'] = "square_payment_id";
		$table_details['index'][0]['keyname'] = "square_payment_id";
/*
*/
		//$sidl = 'varchar(' . STOCK_ID_LENGTH . ')';
		//$descl = 'varchar(' . DESCRIPTION_LENGTH . ')';

		$fields_array[] = array('name'=> 'square_import_payments_id', 'type' => 'int(11) ', 'null' => 'NOT NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' ); 
		$fields_array[] = array('name'=> 'last_updated', 'type' => 'timestamp ', 'null' => 'NOT NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'current_timestamp()' ); // on update current_timestamp() |
		$fields_array[] = array('name'=> 'square_payment_id', 'type' => 'varchar(32)', 'null' => 'NOT NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' ); //| UNI 
		$fields_array[] = array('name'=> 'total_collected', 'type' => 'float   ', 'null' => 'NOT NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );
		$fields_array[] = array('name'=> 'trans_type', 'type' => 'int(11)  ', 'null' => 'NOT NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );
		$fields_array[] = array('name'=> 'trans_no', 'type' => 'varchar(32)', 'null' => 'NOT NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );



		//In theory OUR ->set should also set table_interface but it didn't seem to be working
/*
		$this->table_details = $table_details;
		$this->fields_array = $fields_array;
		$this->table_interface->set( "table_details", $this->table_details );
		$this->table_interface->set( "fields_array", $this->fields_array );
*/
		$this->set( "table_details", $table_details );
		$this->set( "fields_array", $fields_array );
/*
		$this->table_interface->set( "table_details", $table_details );
		$this->table_interface->set( "fields_array", $fields_array );
*/
		$this->set( "fields_array", $fields_array );
		$this->set( "table_details", $table_details );
		//var_dump( $this->table_interface );


		//display_notification( __FILE__ . "::" . __LINE__ . "::" . print_r( $this->fields_array , true ) );
	}
        /*****************************************************************//**
        * Set the field if possible
        *
        *       Tries to set the field in this class as well as in table_interface
        *       assumption being we are going to do something with the field in
        *       the database (else why set the model...)
        *
        * @param string field to set
        * @param mixed value to set
        * @param bool should we allow the class to only set __construct time fields
        * @return nothing. (parent) throws exceptions
        **********************************************************************/
	function set( $field, $value = null, $enforce = true )
	{
		//display_notification( __FILE__ . "::" . __CLASS__ . "::"  . __METHOD__ . ":" . __LINE__, "WARN" );
		//display_notification( __FILE__ . "::" . __LINE__ . ":" . "Setting $field to $value" );
		switch( $field )
		{
			default:
				$value = filter_var( $value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
			break;
		}
		
		$ret = parent::set( $field, $value, $enforce );
		//display_notification( __FILE__ . "::" . __CLASS__ . "::"  . __METHOD__ . ":" . __LINE__, "WARN" );
		return $ret;
	}
	function insert()
	{
		return $this->insert_transaction();
	}
	function insert_transaction()
	{
		//display_notification( __FILE__ . "::" . __LINE__ . "::" . print_r( $this, true ) );
		return $this->insert_data( get_object_vars($this) );
	}
        /**//************************************************************************************
        * Search the database  for a transaction
        *
        * @since 20250319
        *
        * @param string payment ID
        * @returns array results.
        ******************************************************************************************/
        function getTransaction( $payment_id )
        {
                $sql = " SELECT ";
                $colcount = 0;  //used to put a comma between fields
                $sql .= " * ";
                $sql .= " FROM " . TB_PREF .$this->iam . " t ";
                $sql .= " WHERE ";
                $sql .= " square_payment_id = '" . $payment_id . "'";
                //$sql.= " ORDER BY Date ASC";
                //var_dump( $sql );
                $res = db_query($sql, 'unable to get transaction data');
                $res_arr = array();
                $count = 0;
                while($myrow = db_fetch_assoc($res))
                {
                        //var_dump( $myrow );
                        $res_arr[] = $myrow;
                }
                //var_dump( $res_arr );
                return $res_arr;
        }

	/***************************************************************************************//**
	* Insert a matched payment record
	*
	* @since 20250325
	* 	
	* @param string transaction type (payment/transaction/deposit)
	* @param string transaction ID
	* @param int FA transaction type	
	* @param int FA transaction number
	* @returns mixed db_query returned value (bool?)
	******************************************************************************************/
	function insertPaymentMatch( $transaction_type, $transaction_id, $trans_type, $trans_no )
	{
                $sql = "INSERT IGNORE into `" . TB_PREF . "ksf_import_square_payments` (  trans_type, trans_no, total_collected, square_payment_id  )";
                $sql .= " SELECT '" . $trans_type . "', '" . $trans_no . "', total_collected, payment_id from " . TB_PREF . "ksf_import_square_transactions where `" . $transaction_type . "` = '" . $transaction_id . "'";
                $res = db_query( $sql, "Couldn't insert transaction" );
		return $res;
	}
	function getPaymentMatch( $trans_type, $trans_no )
	{
		$sql = "SELECT * FROM " . TB_PREF . "ksf_import_square_payments";
		$sql .= " WHERE trans_no='" . $trans_no . "' and trans_type='" . $trans_type . "'";
		$ret_arr = array();
                $res = db_query( $sql, "Couldn't insert transaction" );
		while( $res_arr = db_fetch_assoc( $res ) )
		{
			$ret_arr[] = $res_arr;
		}
		return $ret_arr;
	}
}
