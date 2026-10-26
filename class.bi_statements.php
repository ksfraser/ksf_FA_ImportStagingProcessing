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

//display_notification( __FILE__ . "::" . __LINE__ );

require_once( '../ksf_modules_common/class.generic_fa_interface.php' );
//display_notification( __FILE__ . "::" . __LINE__ );
require_once( '../ksf_modules_common/defines.inc.php' );
//display_notification( __FILE__ . "::" . __LINE__ );

/**//**************************************************************************************************************
* A DATA class to handle the storage and retrieval of bank records.  STAGE the records before processing into FA.
*
*
*
*	***** WARNING *** WARNING *** WARNING *****
*	MySQL has a row limit of 4k.  Having a bunch of large fields can lead to errors and issues.
*
*	+---------------------+--------------+------+-----+---------+----------------+
*	| Field               | Type         | Null | Key | Default | Extra          |
*	+---------------------+--------------+------+-----+---------+----------------+
*	| id           | int(11)     | NO   | PRI | NULL    | auto_increment |
*	| bank         | varchar(22) | YES  | MUL | NULL    |                |
*	| account      | varchar(24) | YES  |     | NULL    |                |
*	| currency     | varchar(3)  | YES  |     | NULL    |                |
*	| startBalance | double      | YES  |     | NULL    |                |
*	| endBalance   | double      | YES  |     | NULL    |                |
*	| smtDate      | date        | YES  |     | NULL    |                |
*	| number       | int(11)     | YES  |     | NULL    |                |
*	| seq          | int(11)     | YES  |     | NULL    |                |
*	| statementId  | varchar(64) | YES  |     | NULL    |                |
*	+---------------------+--------------+------+-----+---------+----------------+
*	
*
******************************************************************************************************************/
class bi_statements_model extends generic_fa_interface_model 
{
	protected $id;                  	//| int(11)      | NO   | PRI | NULL    | auto_increment |
	protected $bank;		// varchar(22) | YES  | MUL | NULL    |                |
	protected $account;		// varchar(24) | YES  |     | NULL    |                |
	protected $currency;		// varchar(3)  | YES  |     | NULL    |                |
	protected $startBalance;	// double      | YES  |     | NULL    |                |
	protected $endBalance;		// double      | YES  |     | NULL    |                |
	protected $smtDate;		// date        | YES  |     | NULL    |                |
	protected $number;		// int(11)     | YES  |     | NULL    |                |
	protected $seq;			// int(11)     | YES  |     | NULL    |                |
	protected $statementId;		// varchar(64) | YES  |     | NULL    |                |



	function __construct()
	{
//display_notification( __FILE__ . "::" . __LINE__ );
//		parent::__construct();
//display_notification( __FILE__ . "::" . __LINE__ );
		$this->iam = "bi_statements";
//display_notification( __FILE__ . "::" . __LINE__ );
		$this->define_table();
//display_notification( __FILE__ . "::" . __LINE__ );
	}
	function define_table()
	{
		$ind = "id";
		//$ind = "id_" . $this->iam;
//All of these array assignments error out:
//	Indirect modification of overloaded property bi_transactions_model::$fields_array has no effect in file
//Cause would be -> varaibles below are temporary - craeted by __get so that __set might set them.
//Looks like these fields aren't declared anywhere in the inheritance chain, hence the temporary (created by __get)
		$this->fields_array[] = array('name' => $ind, 'type' => 'int(11)', 'auto_increment' => 'yes', 'readwrite' => 'read' );
		$this->fields_array[] = array('name' => 'updated_ts', 'type' => 'timestamp', 'null' => 'NOT NULL', 'default' => 'CURRENT_TIMESTAMP', 'readwrite' => 'read' );
		if( strlen( $this->company_prefix ) < 2 )
		{
			$this->company_prefix = TB_PREF;
		}
		display_notification( __FILE__ . "::" . __LINE__ . ":" . print_r( $this->table_details, true ) );
		display_notification( __FILE__ . "::" . __LINE__ . ":" . print_r( $this, true ) );
		$this->table_details['tablename'] = $this->company_prefix . $this->iam;
		$this->table_details['primarykey'] = $ind;
		//$this->table_details['orderby'] = 'valueTimestamp, id';
		//$this->table_details['orderby'] = 'transaction_date, transaction_id';
/*
		$this->table_details['index'][0]['type'] = 'unique';
		$this->table_details['index'][0]['columns'] = "transaction_id";
		$this->table_details['index'][0]['keyname'] = "transaction_id";
*/
		//$sidl = 'varchar(' . STOCK_ID_LENGTH . ')';
		//$descl = 'varchar(' . DESCRIPTION_LENGTH . ')';

		$this->fields_array[] = array('name'=> 'id', 'label' => 'ID', 	'type' => 'int(11)', 	'null' => 'NOT NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );
		$this->fields_array[] = array('name'=> 'bank', 'label' => 'Bank	', 'type' => ' varchar(64)  ', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );    
		$this->fields_array[] = array('name'=> 'account', 'label' => 'Account', 'type' => ' varchar(64)  ', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );    
		$this->fields_array[] = array('name'=> 'currency', 'label' => 'Currency', 'type' => ' varchar(3)   ', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );    
		$this->fields_array[] = array('name'=> 'startBalance', 'label' => 'Start Balance','type' => ' double ', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );    
		$this->fields_array[] = array('name'=> 'endBalance', 'label' => 'End Balance',	'type' => ' double ', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );    
		$this->fields_array[] = array('name'=> 'smtDate', 'label' => 'Statement Date',	'type' => ' date   ', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );    
		$this->fields_array[] = array('name'=> 'number', 'label' => 'Number','type' => ' int(11)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );    
		$this->fields_array[] = array('name'=> 'seq', 'label' => 'Sequence', 'type' => ' int(11)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );    
		$this->fields_array[] = array('name'=> 'statementId', 'label' => 'Statement ID','type' => ' varchar(64)  ', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );    
		display_notification( __FILE__ . "::" . __LINE__ . ":" . print_r( $this->table_details['tablename'], true ) );
		display_notification( __FILE__ . "::" . __LINE__ . ":" . print_r( $this->table_details, true ) );
	}
	function insert_transaction()
	{
		$this->insert_data( get_object_vars($this) );
	}
	/**//*************************************************************************
	* Update bi_trans with the related info to FA gl transactions
	*
	*       Hooks db_prevoid does similar
	*
	* @param none
	* @returns none
	******************************************************************************/
	function update_statement()
	{
		$sql = "
			UPDATE " . $this->table_details['tablename'] .
			" SET startBalance=". db_escape( $this->startBalance ) . ", " .
				"endBalance=" . db_escape($this->endBalance) . 
			" WHERE id=" . db_escape( $this->id );
		display_notification( __FILE__ . "::" . __LINE__ . " : " . $sql);
		db_query($sql, 'Could not update trans');
	}
	/**//*************************************************************************
	* Reset TBD           when the related FA gl transaction is voided
	*
	* @param int the Transaction Type (JE/BP/SP/...)
	* @param int The transaction number
	* @returns none
	******************************************************************************/
	function db_prevoid( $trans_type, $trans_no )
	{
	}
	/**//**********************************************************************
	* Get a specific transaction's details
	*
	* @param int index
	* @returns array transaction row from db
	***************************************************************************/
	function get_statement( $tid = null) 
	{
		if( $tid == null )
		{
			if( isset( $this->id ) )
				$tid = $this->id;
			else
				throw new Exception( "No ID set to search for" );
		}
	        $sql = "
	              SELECT * FROM ". $this->table_details['tablename'] .
	             "WHERE id=" . db_escape($tid);
	        $result = db_query($sql, "could not get statement with id $tid");
	        return db_fetch($result);
	}
	/**//************************************************************
	* Hand build the INSERT statement
	*
	* @param none
	* @returns string SQL statement
	*****************************************************************/
	function hand_insert_sql()
	{
               $sql = 	"INSERT IGNORE INTO " . $this->table_details['tablename'] . 
			"(bank, account, currency, startBalance, endBalance, smtDate, number, seq, statementId)" .
			" VALUES( " .
				db_escape($this->bank) . ", " .
				db_escape($this->account) . ", " .
				db_escape($this->currency) . ", " .
				db_escape($this->startBalance) . ", " .
				db_escape($this->endBalance) . ", " .
				db_escape($this->timestamp) . ", " .
				db_escape($this->number) . ", " .
				db_escape($this->sequence) . ", " .
				db_escape($this->statementId) . 
			")";
		return $sql;
	}
	/**//************************************************************
	* Determine if this particular statement already exists in the staging table
	*
	*	We need to do duplicate checking.  Most banks allow a person to select a date range for their transactions.
	*
	*	We can have the same total for the same vendor on the same date and not automatically be a duplicate
	*		Example when I buy flats of coke on sale limit 4 per transaction. 
	*
	*
	* @param none
	* @returns bool already exists? 
	*****************************************************************/
	function statement_exists()
	{
                $sql = "
                      SELECT * FROM ". $this->table_details['tablename'] .
                     " WHERE bank=".db_escape($this->bank)." AND statementId=".db_escape($this->statementId);
		$tid = $this->statementId;
		display_notification( __FILE__ . "::" . __LINE__ . " $sql" );
		try
		{
	                $result = db_query($sql, "could not get statement with id $tid");
			$myrow = db_fetch($result);
			if( empty($myrow)) 
			{
				return false;
			}
			else
			{
				$this->arr2obj( $myrow );
				//display_notification( __FILE__ . "::" . __LINE__ . print_r( $this, true ) );
			}
		}
		catch( Exception $e )
		{
			display_notification( __FILE__ . "::" . __LINE__ . $e->getMessage() );
		}
		return true;
	}

}
////I've copied this into ORIGIN.
//	/**//**********************************************************************
//	* Convert Statement class to this object
//	*
//	* @param class
//	* @returns int how many fields did we copy
//	**************************************************************************/
//	function obj2obj( $obj )
//	{
//		//display_notification( __FILE__ . "::" . __LINE__ . print_r( $obj, true ) );
//		if( is_array( $obj ) )
//			return $this->arr2obj( $obj );
//		if( ! is_object( $obj ) )
//			throw new Exception( "Passed in data is neither an array nor an object.  We can't handle here!" );
//
//		$cnt = 0;
//		foreach( get_object_vars($this) as $key => $value )
//		{
//			//display_notification( __FILE__ . "::" . __LINE__ . " " . print_r( $key, true ) );
//			if( isset( $obj->$key ) )
//			{
//				//display_notification( __FILE__ . "::" . __LINE__ . " $key $obj->$key" );
//				//$this->$key = $obj->$key;	
//				$this->set( $key, $obj->$key );	
//				//	display_notification( __FILE__ . "::" . __LINE__ . " " . print_r( $this->$key, true ) );
//				$cnt++;
//			}
//			else
//			{
//				//display_notification( __FILE__ . "::" . __LINE__ . " $key not set in " . print_r( $obj, true ) );
//			}
//		}
//		//	display_notification( __FILE__ . "::" . __LINE__ . print_r( $this, true ) );
//		return $cnt;
//	}
//	/**//**********************************************************************
//	* Convert Transaction array to this object
//	*
//	* @param array
//	* @returns int how many fields did we copy
//	**************************************************************************/
//	function arr2obj( $arr )
//	{
//		//display_notification( __FILE__ . "::" . __LINE__ . print_r( $arr, true ) );
//		if( is_object( $arr ) )
//			return $this->obj2obj( $arr );
//		if( ! is_array( $arr ) )
//			throw new Exception( "Passed in data is neither an array nor an object.  We can't handle here!" );
//
//		$cnt = 0;
//		foreach( get_object_vars($this) as $key => $value )
//		{
//			//display_notification( __FILE__ . "::" . __LINE__ . " " . print_r( $key, true ) );
//			if( isset( $arr[$key] ) )
//			{
//				//display_notification( __FILE__ . "::" . __LINE__ . " $key $arr[$key]" );
//				//$this->$key = $arr[$key];	
//				$this->set( $key, $arr[$key] );	
//				//	display_notification( __FILE__ . "::" . __LINE__ . " " . print_r( $this->$key, true ) );
//				$cnt++;
//			}
//			else
//			{
//				//display_notification( __FILE__ . "::" . __LINE__ . " $key not set in " . print_r( $arr, true ) );
//			}
//		}
//		//	display_notification( __FILE__ . "::" . __LINE__ . print_r( $this, true ) );
//		return $cnt;
//	}
////display_notification( __FILE__ . "::" . __LINE__ );
