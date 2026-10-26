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

/*
 *
 * Each import type needs to read in the source document, and process line by line placing a record into this class.
 * This class then needs to insert the record.
 *
 * This table should not have any views (forms).
 * */

require_once( '../ksf_modules_common/class.generic_fa_interface.php' );
require_once( '../ksf_modules_common/defines.inc.php' );

/**//**************************************************************************************************************
* A DATA class to handle the storage and retrieval of bank records.  STAGE the records before processing into FA.
*
*
*
*	***** WARNING *** WARNING *** WARNING *****
*	MySQL has a row limit of 4k.  Having a bunch of large fields can lead to errors and issues.
*
******************************************************************************************************************/
class bi_transationTitle_model extends generic_fa_interface_model {
	var $id_bi_transactionTitle_model;	//!< Index of table

	protected $transaction_id;		//Dream Payments order_num //WooCommerce	//OFX TRNUID		//MT940		//Paypal
	protected $bi_transactionTitle;		//all the titles concatenated						//MT940 bi
	protected $bi_transactionTitle1;										//MT940 bi
	protected $bi_transactionTitle2;										//MT940 bi
	protected $bi_transactionTitle3;										//MT940 bi
	protected $bi_transactionTitle4;										//MT940 bi
	protected $bi_transactionTitle5;										//MT940 bi
	protected $bi_transactionTitle6;										//MT940 bi
	protected $bi_transactionTitle7;										//MT940 bi
	protected $bi_transactionTitle8;										//MT940 bi
	protected $bi_transactionTitle9;										//MT940 bi
	protected $staging_id;			//The ID of the staging table record that is the master for this record


	function __construct()
	{
	}
	function define_table()
	{
		$ind = "id_" . $this->iam;
		$this->fields_array[] = array('name' => $ind, 'type' => 'int(11)', 'auto_increment' => 'yes', 'readwrite' => 'read' );
		$this->fields_array[] = array('name' => 'updated_ts', 'type' => 'timestamp', 'null' => 'NOT NULL', 'default' => 'CURRENT_TIMESTAMP', 'readwrite' => 'read' );
		$this->table_details['tablename'] = $this->company_prefix . $this->iam;
		$this->table_details['primarykey'] = $ind;
		$this->table_details['orderby'] = 'transaction_date, transaction_id';
		$this->table_details['index'][0]['type'] = 'unique';
		$this->table_details['index'][0]['columns'] = "transaction_id";
		$this->table_details['index'][0]['keyname'] = "transaction_id";
		//$this->fields_array[] = array('name' => 'stock_id', 'label' => 'SKU', 'type' => 'varchar(256)', 'null' => 'NOT NULL',  'readwrite' => 'readwrite');
		//$sidl = 'varchar(' . STOCK_ID_LENGTH . ')';
		//$descl = 'varchar(' . DESCRIPTION_LENGTH . ')';

		$this->fields_array[] = array('name' => 'transaction_id', 'label' => 'transaction_id', 'type' => 'varchar(STOCK_ID_LENGTH)', 'null' => 'NOT NULL',  'readwrite' => 'readwrite', 'comment' => 'WooCommerce Trans ID ' );
		$this->fields_array[] = array('name' => 'transactionTitle', 'label' => 'BI Transaction Title', 'type' => 'varchar(STOCK_ID_LENGTH)', 'null' => 'NOT NULL',  'readwrite' => 'readwrite', 'comment' => 'Bank Import - mt940' );		
		$this->fields_array[] = array('name' => 'transactionTitle1', 'label' => 'BI Transaction Title 1', 'type' => 'varchar(STOCK_ID_LENGTH)', 'null' => 'NOT NULL',  'readwrite' => 'readwrite', 'comment' => 'Bank Import - mt940' );		
		$this->fields_array[] = array('name' => 'transactionTitle2', 'label' => 'BI Transaction Title 2', 'type' => 'varchar(STOCK_ID_LENGTH)', 'null' => 'NOT NULL',  'readwrite' => 'readwrite', 'comment' => 'Bank Import - mt940' );		
		$this->fields_array[] = array('name' => 'transactionTitle3', 'label' => 'BI Transaction Title 3', 'type' => 'varchar(STOCK_ID_LENGTH)', 'null' => 'NOT NULL',  'readwrite' => 'readwrite', 'comment' => 'Bank Import - mt940' );		
		$this->fields_array[] = array('name' => 'transactionTitle4', 'label' => 'BI Transaction Title 4', 'type' => 'varchar(STOCK_ID_LENGTH)', 'null' => 'NOT NULL',  'readwrite' => 'readwrite', 'comment' => 'Bank Import - mt940' );		
		$this->fields_array[] = array('name' => 'transactionTitle5', 'label' => 'BI Transaction Title 5', 'type' => 'varchar(STOCK_ID_LENGTH)', 'null' => 'NOT NULL',  'readwrite' => 'readwrite', 'comment' => 'Bank Import - mt940' );		
		$this->fields_array[] = array('name' => 'transactionTitle6', 'label' => 'BI Transaction Title 6', 'type' => 'varchar(STOCK_ID_LENGTH)', 'null' => 'NOT NULL',  'readwrite' => 'readwrite', 'comment' => 'Bank Import - mt940' );		
		$this->fields_array[] = array('name' => 'transactionTitle7', 'label' => 'BI Transaction Title 7', 'type' => 'varchar(STOCK_ID_LENGTH)', 'null' => 'NOT NULL',  'readwrite' => 'readwrite', 'comment' => 'Bank Import - mt940' );		
		$this->fields_array[] = array('name' => 'transactionTitle8', 'label' => 'BI Transaction Title 8', 'type' => 'varchar(STOCK_ID_LENGTH)', 'null' => 'NOT NULL',  'readwrite' => 'readwrite', 'comment' => 'Bank Import - mt940' );		
		$this->fields_array[] = array('name' => 'transactionTitle9', 'label' => 'BI Transaction Title 9', 'type' => 'varchar(STOCK_ID_LENGTH)', 'null' => 'NOT NULL',  'readwrite' => 'readwrite', 'comment' => 'Bank Import - mt940' );		
		$this->fields_array[] = array('name' => 'staging_id', 'label' => 'Master Record in Staging Table', 'type' => 'varchar(STOCK_ID_LENGTH)', 'null' => 'NOT NULL',  'readwrite' => 'readwrite', 'comment' => 'Bank Import - mt940' );		
	}
	function insert_transaction()
	{
		$this->insert_data( get_object_vars($this) );
	}

	
}
