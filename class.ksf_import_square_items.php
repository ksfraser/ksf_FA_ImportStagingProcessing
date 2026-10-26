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
*	+---------------------+--------------+------+-----+---------+----------------+
*	| Field                 | Type        | Null | Key | Default             | Extra |
*	+-----------------------+-------------+------+-----+---------------------+-------+
*	 Date                    | date        | NO   |     | NULL                |       |
*	 Time                    | varchar(8)  | NO   |     | NULL                |       |
*	 Timezone                | varchar(64) | NO   |     | NULL                |       |
*	 gross_sales             | float       | NO   |     | NULL                |       |
*	 discounts               | float       | NO   |     | NULL                |       |
*	 service_charges         | float       | NO   |     | NULL                |       |
*	 gift_card_sales         | float       | NO   |     | NULL                |       |
*	 net_sales               | float       | NO   |     | NULL                |       |
*	 tax                     | float       | NO   |     | NULL                |       |
*	 tip                     | float       | NO   |     | NULL                |       |
*	 partial_refunds         | float       | NO   |     | NULL                |       |
*	 total_collected         | float       | NO   |     | NULL                |       |
*	 source                  | varchar(16) | NO   |     | NULL                |       |
*	 card                    | float       | NO   |     | NULL                |       |
*	 card_entry_methods      | varchar(16) | NO   |     | NULL                |       |
*	 cash                    | float       | NO   |     | NULL                |       |
*	 square_gift_card        | float       | NO   |     | NULL                |       |
*	 other_tender            | float       | NO   |     | NULL                |       |
*	 other_tender_type       | varchar(16) | NO   |     | NULL                |       |
*	 other_tender_note       | varchar(32) | NO   |     | NULL                |       |
*	 fees                    | float       | NO   |     | NULL                |       |
*	 net_total               | float       | NO   |     | NULL                |       |
*	 transaction_id          | varchar(32) | NO   |     | NULL                |       |
*	 payment_id              | varchar(32) | NO   |     | NULL                |       |
*	 card_brand              | varchar(16) | NO   |     | NULL                |       |
*	 PAN_suffix              | int(11)     | NO   |     | NULL                |       |
*	 device_name             | varchar(32) | NO   |     | NULL                |       |
*	 staff_name              | varchar(16) | NO   |     | NULL                |       |
*	 staff_id                | varchar(16) | NO   |     | NULL                |       |
*	 description             | varchar(64) | NO   |     | NULL                |       |
*	 details                 | varchar(64) | NO   |     | NULL                |       |
*	 event_type              | varchar(32) | NO   |     | NULL                |       |
*	 location                | varchar(32) | NO   |     | NULL                |       |
*	 Dining_option           | varchar(16) | NO   |     | NULL                |       |
*	 Customer_id             | int(11)     | NO   |     | NULL                |       |
*	 customer_name           | varchar(64) | NO   |     | NULL                |       |
*	 customer_reference_id   | varchar(16) | NO   |     | NULL                |       |
*	 device_nickname         | varchar(16) | NO   |     | NULL                |       |
*	 third_party_fees        | float       | NO   |     | NULL                |       |
*	 deposit_id              | varchar(32) | NO   |     | NULL                |       |
*	 deposit_date            | date        | NO   |     | NULL                |       |
*	 deposit_details         | varchar(64) | NO   |     | NULL                |       |
*	 fee_percentage_rate     | float       | NO   |     | NULL                |       |
*	 fee_fixed_rate          | float       | NO   |     | NULL                |       |
*	 refund_reason           | varchar(64) | NO   |     | NULL                |       |
*	 discount_name           | varchar(16) | NO   |     | NULL                |       |
*	 transaction_status      | varchar(16) | NO   |     | NULL                |       |
*	 order_reference_id      | varchar(16) | NO   |     | NULL                |       |
*	 fulfillment_note        | varchar(32) | NO   |     | NULL                |       |
*	 free_processing_applied | float       | NO   |     | NULL                |       |
*	 last_updated            | timestamp   | NO   |     | current_timestamp() |       |
*	+---------------------+--------------+------+-----+---------+----------------+
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
******************************************************************************************************************/
class ksf_import_square_items_model extends generic_fa_interface_model {
	var $id;	//!< Index of table
	//var $id_ksf_import_square_transactions;	//!< Index of table
	protected $Date                  ;//!< date        | NO   |     | NULL                |       |
	protected $Time                  ;//!< varchar(8)  | NO   |     | NULL                |       |
	protected $Timezone              ;//!< varchar(64) | NO   |     | NULL                |       |
  	protected $Category;		//!< varchar(32) NOT NULL,
  	protected $Item;		//!< varchar(64) NOT NULL,
  	protected $Price_Point_Name;		//!< varchar(32) NOT NULL,
  	protected $stock_id;		//!< varchar(32) NOT NULL,
  	protected $modifiers_applied;		//!< varchar(32) NOT NULL,
  	protected $quantity;		//!< int(11) NOT NULL,
	protected $gross_sales           ;//!< float       | NO   |     | NULL                |       |
	protected $discounts;              //!< float       | NO   |     | NULL                |       |
	protected $net_sales;              //!< float       | NO   |     | NULL                |       |
	protected $tax;                    //!< float       | NO   |     | NULL                |       |
	protected $transaction_id;         //!< varchar(32) | NO   |     | NULL                |       |
	protected $payment_id;             //!< varchar(32) | NO   |     | NULL                |       |
	protected $device_name;            //!< varchar(32) | NO   |     | NULL                |       |
	protected $notes;	           //!< varchar(64) | NO   |     | NULL                |       |
	protected $details;                //!< varchar(64) | NO   |     | NULL                |       |
	protected $event_type;             //!< varchar(32) | NO   |     | NULL                |       |
	protected $location;               //!< varchar(32) | NO   |     | NULL                |       |
	protected $dining_option;          //!< varchar(16) | NO   |     | NULL                |       |
	protected $Customer_id;            //!< int(11)     | NO   |     | NULL                |       |
	protected $customer_name;          //!< varchar(64) | NO   |     | NULL                |       |
	protected $customer_reference_id;  //!< varchar(16) | NO   |     | NULL                |       |
	protected $unit;
	protected $count;
	protected $itemization_type;
	protected $fulfillment_note;       //!< varchar(32) | NO   |     | NULL                |       |
	protected $last_updaated         ;//!< timestamp   | NO   |     | current_timestamp() |       |


	function __construct()
	{
		//display_notification( __FILE__ . "::" . __LINE__ );
		parent::__construct( null, null, null, null, null);
		//display_notification( __FILE__ . "::" . __LINE__ );
		$this->iam = "ksf_import_square_items";
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
		$ind = "id";
		//$ind = "id_" . $this->iam;
		$fields_array = $this->fields_array;
		$fields_array[] = array('name' => $ind, 'type' => 'int(11)', 'auto_increment' => 'yes', 'readwrite' => 'read' );
		$fields_array[] = array('name' => 'last_updated', 'type' => 'timestamp', 'null' => 'NOT NULL', 'default' => 'CURRENT_TIMESTAMP', 'readwrite' => 'read' );
		if( strlen( $this->company_prefix ) < 2 )
                {
                        $this->company_prefix = TB_PREF;
                }
		$table_details =  $this->table_details;	//declared array in parent constructor
                $table_details['tablename'] = $this->company_prefix . $this->iam;
		$table_details['primarykey'] = $ind;
		$table_details['orderby'] = 'valueTimestamp, id';
		//$table_details['orderby'] = 'transaction_date, transaction_id';
/*
		$table_details['index'][0]['type'] = 'unique';
		$table_details['index'][0]['columns'] = "transaction_id";
		$table_details['index'][0]['keyname'] = "transaction_id";
*/
		//$sidl = 'varchar(' . STOCK_ID_LENGTH . ')';
		//$descl = 'varchar(' . DESCRIPTION_LENGTH . ')';

		$fields_array[] = array('name'=> 'id', 'label' => 'ID', 'type' => 'int(11)', 'null' => 'NOT NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' , 'sales_order' => 'true' );
		$fields_array[] = array('name'=> 'Date', 'label' => '', 'type' => 'date', 'null' => 'NOT NULL' , 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL', 'sales_order' => 'true' );
		$fields_array[] = array('name'=> 'Time', 'label' => '', 'type' => 'varchar(8)', 'null' => 'NOT NULL' , 	'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL', 'sales_order' => '' );
		$fields_array[] = array('name'=> 'Timezone', 'label' => '', 'type' => 'varchar(64)' , 'null' => 'NOT NULL' , 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL', 'sales_order' => '' );
		$fields_array[] = array('name'=> 'Category', 'label' => '', 'type' => 'varchar(32)' , 'null' => 'NOT NULL' , 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL', 'sales_order' => '' );
		$fields_array[] = array('name'=> 'Item', 'label' => '', 'type' => 'varchar(64)' , 'null' => 'NOT NULL' , 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL', 'sales_order' => 'true' );
		$fields_array[] = array('name'=> 'Price_Point_Name', 'label' => '', 'type' => 'varchar(32)' , 'null' => 'NOT NULL' , 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL', 'sales_order' => '' );
		$fields_array[] = array('name'=> 'stock_id', 'label' => '', 'type' => 'varchar(32)' , 'null' => 'NOT NULL' , 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL', 'sales_order' => 'true' );
		$fields_array[] = array('name'=> 'modifiers_applied', 'label' => '', 'type' => 'varchar(32)' , 'null' => 'NOT NULL' , 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL', 'sales_order' => 'true' );
		$fields_array[] = array('name'=> 'quantity', 'label' => '', 'type' => 'int(11)' , 'null' => 'NOT NULL' , 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL', 'sales_order' => 'true' );
		$fields_array[] = array('name'=> 'gross_sales', 'label' => '', 'type' => 'float' , 'null' => 'NOT NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL', 'sales_order' => 'true' );
		$fields_array[] = array('name'=> 'discounts', 'label' => '', 'type' => 'float' , 'null' => 'NOT NULL' , 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL', 'sales_order' => 'true' );
		$fields_array[] = array('name'=> 'net_sales', 'label' => '', 'type' => 'float' , 'null' => 'NOT NULL' , 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL', 'sales_order' => 'true' );
		$fields_array[] = array('name'=> 'tax', 'label' => '', 'type' => 'float' , 'null' => 'NOT NULL' , 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL', 'sales_order' => 'true' );
		$fields_array[] = array('name'=> 'transaction_id', 'label' => '','type' => 'varchar(32)' , 'null' => 'NOT NULL' , 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL', 'sales_order' => '' );
		$fields_array[] = array('name'=> 'payment_id', 'label' => '','type' => 'varchar(32)' , 'null' => 'NOT NULL' , 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL', 'sales_order' => '' );
		$fields_array[] = array('name'=> 'device_name', 'label' => '', 'type' => 'varchar(32)' , 'null' => 'NOT NULL' , 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL', 'sales_order' => '' );
		$fields_array[] = array('name'=> 'notes', 'label' => '', 'type' => 'varchar(64)' , 'null' => 'NOT NULL' , 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL', 'sales_order' => 'true' );
		$fields_array[] = array('name'=> 'details', 'label' => '', 'type' => 'varchar(64)' , 'null' => 'NOT NULL' , 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL', 'sales_order' => '' );
		$fields_array[] = array('name'=> 'event_type', 'label' => '','type' => 'varchar(32)' , 'null' => 'NOT NULL' , 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL', 'sales_order' => '' );
		$fields_array[] = array('name'=> 'location', 'label' => '','type' => 'varchar(32)' , 'null' => 'NOT NULL' , 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL', 'sales_order' => '' );
		$fields_array[] = array('name'=> 'dining_option', 'label' => '', 'type' => 'varchar(16)' , 'null' => 'NOT NULL' , 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL', 'sales_order' => '' );
		$fields_array[] = array('name'=> 'Customer_id', 'label' => '', 'type' => 'int(11)' , 'null' => 'NOT NULL' , 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL', 'sales_order' => '' );
		$fields_array[] = array('name'=> 'customer_name', 'label' => '', 'type' => 'varchar(64)' , 'null' => 'NOT NULL' , 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL', 'sales_order' => '' );
		$fields_array[] = array('name'=> 'customer_reference_id', 'label' => '', 'type' => 'varchar(16)' , 'null' => 'NOT NULL' , 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL', 'sales_order' => '' );
		$fields_array[] = array('name'=> 'unit', 'label' => '', 'type' => 'varchar(16)' , 'null' => 'NOT NULL' , 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL', 'sales_order' => 'true' );
		$fields_array[] = array('name'=> 'count', 'label' => '','type' => 'float', 'null' => 'NOT NULL' , 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL', 'sales_order' => 'true' );
		$fields_array[] = array('name'=> 'itemization_type', 'label' => '','type' => 'varchar(16)' , 'null' => 'NOT NULL' , 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL', 'sales_order' => '' );
		$fields_array[] = array('name'=> 'fulfillment_note', 'label' => '','type' => 'varchar(32)' , 'null' => 'NOT NULL' , 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL', 'sales_order' => 'true' );
		$fields_array[] = array('name'=> 'last_updaated ', 'label' => '', 'type' => 'timestamp', 'null' => 'NOT NULL' , 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'current_timestamp()' , 'sales_order' => '' );


		//In theory OUR ->set should also set table_interface but it didn't seem to be working
/*
		$this->table_details = $table_details;
		$this->fields_array = $fields_array;
		$this->table_interface->set( "table_details", $this->table_details );
		$this->table_interface->set( "fields_array", $this->fields_array );
*/
		$this->set( "table_details", $table_details );
		$this->set( "fields_array", $fields_array );


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
			case 'gross_sales':
			case 'discounts':
			case 'service_charges':
			case 'gift_card_sales':
			case 'net_sales':
			case 'tax':
			case 'tip':
			case 'partial_refunds':
			case 'total_collected':
			case 'card':
			case 'cash':
			case 'square_gift_card':
			case 'other_tender':
			case 'fees':
			case 'net_total':
			case 'third_party_fees':
			case 'fee_percentage_rate':
			case 'fee_fixed_rate':
			case 'free_processing_applied':
				$value = filter_var( $value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
			break;
		}
		
		$ret = parent::set( $field, $value, $enforce );
		//display_notification( __FILE__ . "::" . __CLASS__ . "::"  . __METHOD__ . ":" . __LINE__, "WARN" );
		return $ret;
	}
	/**//****************************************************************
	* Take an array and insert into our variables	
	*
	* @param array
	* @returns int count of inserted variables
	********************************************************************/
	function arr2obj( $in_arr )
	{
		return parent::arr2obj( $in_arr );
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
	function summary_sql( $TransAfterDate, $TransToDate, $statusFilter = 0 )
	{
		$sql = " SELECT * from " . TB_PREF .$this->iam ;
        	$sql .= " WHERE t.Date >= " . db_escape( date2sql( $TransAfterDate ) ) . " AND t.Date <  " . db_escape( date2sql( $TransToDate ) ); 
    		if ( $statusFilter != 255) {
        		$sql .= " AND t.status = ".db_escape( $statusFilter );
    		}
    		$sql.= " ORDER BY Date ASC";
		var_dump( $sql );
    		$res = db_query($sql);
    		//$res = db_query($sql, 'unable to get transactions data');
		var_dump( $res );
		return $res;
	}
        /**//************************************************************************************
        * Search the database  for a transaction
        *
        * @since 20250319
        *
        * @param string transaction ID
        * @param string display type - pay/dep/invoice/all
        * @returns array results.
        ******************************************************************************************/
        function getTransaction( $transaction_id )
        {
                $sql = " SELECT ";
                $colcount = 0;  //used to put a comma between fields
		$display_type = 'sales_order';
                foreach( $this->fields_array as $row )
                {
                        if( 'all' == $display_type )
                        {
                                $sql .= " * ";
                        }
                        else
                        if( isset( $row[$display_type] ) and $row[$display_type] == 'true' )
                        {
                                //This field is to be included in the query
                                if( $colcount )
                                {
                                        $sql .= ", ";
                                }
/* Not summing
                                if( isset( $row['sum'] ) and $row['sum'] == 'true' )
                                {
                                        //Format for 2 decimal places
                                        $sql .= "format( sum( " . $row['name'] . " ), 2 ) as " . $row['name'];
                                        //$sql .= "sum( " . $row['name'] . " ) as " . $row['name'];
                                }
                                else
                                {
*/
                                        $sql .= $row['name'];
/*
                                }
*/
                                $colcount++;
                        }

                }
                $sql .= " FROM " . TB_PREF .$this->iam . " t ";
                $sql .= " WHERE ";
                $sql .= " transaction_id = '" . $transaction_id . "'";
                //$sql.= " ORDER BY Date ASC";
                //var_dump( $sql );
                $res = db_query($sql, 'unable to get transaction data');
                $res_arr = array();
                $count = 0;
                while($myrow = db_fetch($res))
                {
                        //var_dump( $myrow );
                        foreach( $this->fields_array as $row )
                        {
                                if( isset( $myrow[ $row['name'] ] ) )
                                {
                                        $res_arr[$count][ $row['name'] ] = $myrow[ $row['name'] ];
                                }
                        }
                        //var_dump( $res_arr[$count] );
                        $count++;
                }
                //var_dump( $res_arr );
                return $res_arr;
        }
	
}
