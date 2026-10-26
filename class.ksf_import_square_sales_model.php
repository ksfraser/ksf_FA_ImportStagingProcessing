<?php

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
*	+----------------------------+-------------+------+-----+---------------------+-------------------------------+
*	| Field                      | Type        | Null | Key | Default             | Extra                         |
*	+----------------------------+-------------+------+-----+---------------------+-------------------------------+
*	| ksf_import_square_sales_id | int(11)     | NO   | PRI | NULL                | auto_increment                |
*	| last_updated               | timestamp   | NO   |     | current_timestamp() | on update current_timestamp() |
*	| square_transaction_id      | varchar(32) | NO   | UNI | NULL                |                               |
*	| sales_order_no             | varchar(32) | NO   |     | NULL                |                               |
*	| sales_delivery_no          | varchar(32) | NO   |     | NULL                |                               |
*	| sales_invoice_no           | varchar(32) | NO   |     | NULL                |                               |
*	+----------------------------+-------------+------+-----+---------------------+-------------------------------+
*	
*
******************************************************************************************************************/
class ksf_import_square_sales_model extends generic_fa_interface_model {

	protected $ksf_import_square_sales_id;	//!<int(11)     | NO   | PRI | NULL                | auto_increment                |
	protected $last_updated              ;	//!<timestamp   | NO   |     | current_timestamp() | on update current_timestamp() |
	protected $square_transaction_id     ;	//!<varchar(32) | NO   | UNI | NULL                |                               |
	protected $sales_order_no            ;	//!<varchar(32) | NO   |     | NULL                |                               |
	protected $sales_delivery_no         ;	//!<varchar(32) | NO   |     | NULL                |                               |
	protected $sales_invoice_no          ;	//!<varchar(32) | NO   |     | NULL                |                               |


	function __construct()
	{
		//display_notification( __FILE__ . "::" . __LINE__ );
		parent::__construct( null, null, null, null, null);
		//display_notification( __FILE__ . "::" . __LINE__ );
		$this->iam = "ksf_import_square_sales";
		$this->define_table();
		$this->matched = 0;
		$this->created = 0;
	}
	function define_table()
	{
		//$ind = "id";
		//$ind = "id_" . $this->iam;
		$ind = $this->iam . "_id";
		$this->fields_array[] = array('name' => $ind, 'type' => 'int(11)', 'auto_increment' => 'yes', 'readwrite' => 'read' );
		$this->fields_array[] = array('name' => 'last_updated', 'type' => 'timestamp', 'null' => 'NOT NULL', 'default' => 'CURRENT_TIMESTAMP', 'readwrite' => 'read' );
		if( strlen( $this->company_prefix ) < 2 )
                {
                        $this->company_prefix = TB_PREF;
                }
                $this->table_details['tablename'] = $this->company_prefix . $this->iam;
		$this->table_details['primarykey'] = $ind;
		$this->table_details['orderby'] = 'valueTimestamp, id';
		//$this->table_details['orderby'] = 'transaction_date, transaction_id';

		$this->table_details['index'][0]['type'] = 'unique';
		$this->table_details['index'][0]['columns'] = "square_transaction_id";
		$this->table_details['index'][0]['keyname'] = "square_transaction_id";

		//$sidl = 'varchar(' . STOCK_ID_LENGTH . ')';
		//$descl = 'varchar(' . DESCRIPTION_LENGTH . ')';
		$this->fields_array[] = array('name'=> 'square_transaction_id',	'type' => 'varchar(32)', 'null' => 'NOT NULL', 'readwrite' => 'readwrite', 'comment' => '' , 'default' => 'NULL' ); //    |        |
		$this->fields_array[] = array('name'=> 'sales_order_no',	'type' => 'varchar(32)', 'null' => 'NOT NULL', 'readwrite' => 'readwrite', 'comment' => '' , 'default' => 'NULL' ); //    |        |
		$this->fields_array[] = array('name'=> 'sales_delivery_no',	'type' => 'varchar(32)', 'null' => 'NOT NULL', 'readwrite' => 'readwrite', 'comment' => '' , 'default' => 'NULL' ); //    |        |
		$this->fields_array[] = array('name'=> 'sales_invoice_no',	'type' => 'varchar(32)', 'null' => 'NOT NULL', 'readwrite' => 'readwrite', 'comment' => '' , 'default' => 'NULL' ); //    |        |

		$this->table_interface->set( "fields_array", $this->fields_array );
		$this->table_interface->set( "table_details", $this->table_details );

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
		$ret = parent::set( $field, $value, $enforce );
		//display_notification( __FILE__ . "::" . __CLASS__ . "::"  . __METHOD__ . ":" . __LINE__, "WARN" );
		return $ret;
	}
/*
	function insert()
	{
		$this->table_interface->insert_table( );
		//$this->insert_data( get_object_vars($this) );
		//var_dump( $this->sql );
	}
*/
	/**//**********************************************************************
	* Convert Transaction array to this object
	*
	* @param class
	* @returns int how many fields did we copy
	**************************************************************************/
	function trz2obj( $trz )
	{
		return $this->obj2obj( $trz );
	}
	/**//************************************************************
	* Hand build the INSERT statement
	*
	* @param none
	* @returns string SQL statement
	*****************************************************************/
	function hand_insert_sql()
	{
		return;
/* * /
**Not CODED for this class
               $sql = 	"INSERT IGNORE INTO " . $this->table_details['tablename'] .
			"(smt_id, valueTimestamp, entryTimestamp, account, accountName, transactionType, " .
                    		"transactionCode, transactionCodeDesc, transactionDC, transactionAmount, transactionTitle, merchant, category, status, memo, sic, checknumber ) " .
			" VALUES( " .
		                    db_escape($this->smt_id) . ", ".
		                    db_escape($this->valueTimestamp) . ", ".
		                    db_escape($this->entryTimestamp) . ", ".
		                    db_escape($this->account) . ",".
		                    db_escape($this->accountName) . ", ".
		                    db_escape($this->transactionType) . ", ".
		                    db_escape($this->transactionCode) . ", ".
		                    db_escape($this->transactionCodeDesc) . ", ".
		                    db_escape($this->transactionDC) . ", ".
		                    db_escape($this->transactionAmount) . ", ".
		                    db_escape($this->transactionTitle) . ", ".
		                    db_escape($this->merchant) . ", ".
		                    db_escape($this->category) . ", ".
		                    db_escape($this->status) . ", ".
		                    db_escape($this->memo) . ", ".
		                    db_escape($this->sic) . ", ".
		                    db_escape($this->checknumber) . 
			")";
		return $sql;
/* */
	}
}
