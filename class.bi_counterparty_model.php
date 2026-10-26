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
*	Various bank account info providers have different fields/formats for their data.
*	We also will handle QuickBookd QIF, as well as Quicken/MS Money/OFX files
*
*	Looking at bank_import (which processes MT940 files) they have 2 staging tables - Statements and Transactions.
*		MT940 is used by the SWIFT network
*	BI also has 2 CSV parsers.  I was able to extend to parse WMMC. 20240112
*
*	Bank Statements show 
*		Money In 
*			Bank Deposits
*			Account Transfer
*			Interest
*		Money Out
*			Bill Payments
*			Account Transfers
*			Interest and Fees
*	CC Statements show
*		Money In
*			Payments (from a bank account)
*			Vendor Transactions (Refunds)
*		Money Out
*			Vendor Transactions (Purchases)
*			Interest
*			Fees
*	Square / Paypal / Dream / ... show
*		Money In
*			Customers purchases (Sales)
*				Customer Info
*				Transaction Info
*		Money Out
*			Bank Account Transfer
*			Customer Refunds
*			Fees
*			Interest??
*
*
*	***** WARNING *** WARNING *** WARNING *****
*	MySQL has a row limit of 4k.  Having a bunch of large fields can lead to errors and issues.
*
******************************************************************************************************************/
class bi_counterparty_model extends generic_fa_interface_model {
	var $id_bi_counterparty_model;	//!< Index of table

	protected $card_type;			//Dream Payments
	protected $card_number;			//Dream Payments
	protected $receipt_sent;		//Dream Payments
	protected $receipt_email;		//Dream Payments
	protected $receipt_mobile_number;	//Dream Payments
	protected $bank_id;									//OFX BANKID		//MT940 BI
	protected $bank_name;
	protected $account_id;									//OFX ACCTID		//MT940
	protected $FID;										//OFX FITID or FID
	protected $org;										//OFX ORG
	protected $memo;									//OFX MEMO				//PAYPAL NOTE
	protected $name;									//OFX NAME - TRANSFER, CHEQUE, DEPOSIT	//Paypal
	protected $currency;									//OFX CURRDEF		//MT940 	//Paypal
	protected $inserted_fa;	//!<bool has this record been added to customers/suppliers?
	protected $vendor_SIC;									//OFX SIC
	//protected $account;	//!<char(24)	**account_id								//MT940
	protected $accountName;	//!<char(60)										//MT940
	// ** These next 2 are overridden by paypal.  Can be an email address, a CC card number (or maybe tx num).
	protected $from_email;														//Paypal
	protected $to_email;														//Paypal
	protected $shipping_address;													//Paypal
	protected $ship_addr_status;													//Paypal CONFIRMED
	protected $address1;														//Paypal
	protected $address2;														//Paypal
	protected $city;														//Paypal
	protected $state;														//Paypal
	protected $zip;															//Paypal
	protected $country;														//Paypal
	protected $phone;														//Paypal
	protected $subject;														//Paypal
	protected $country_code;													//Paypal
	protected $counterpartyType;	//Supplier, Customer.  Eventually Employee?
	protected $counterpartyId;	//The ID of the customer/supplier.


	function __construct()
	{
		parent::__construct();
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

		$this->fields_array[] = array('name' => 'inserted_fa', 'label' => 'Inserted into FA', 'type' => 'bool', 'null' => 'NOT NULL',  'readwrite' => 'readwrite', 'default' => '0' );
		$this->fields_array[] = array('name' => 'card_type', 'label' => 'card_type', 'type' => 'varchar(STOCK_ID_LENGTH)', 'null' => 'NOT NULL',  'readwrite' => 'readwrite', 'comment' => '		Dream Payments ' );
		$this->fields_array[] = array('name' => 'card_number', 'label' => 'card_number', 'type' => 'varchar(STOCK_ID_LENGTH)', 'null' => 'NOT NULL',  'readwrite' => 'readwrite', 'comment' => '		Dream Payments ' );
		$this->fields_array[] = array('name' => 'receipt_sent', 'label' => 'receipt_sent', 'type' => 'varchar(STOCK_ID_LENGTH)', 'null' => 'NOT NULL',  'readwrite' => 'readwrite', 'comment' => '	Dream Payments ' );
		$this->fields_array[] = array('name' => 'receipt_email', 'label' => 'receipt_email', 'type' => 'varchar(STOCK_ID_LENGTH)', 'null' => 'NOT NULL',  'readwrite' => 'readwrite', 'comment' => '	Dream Payments ' );
		$this->fields_array[] = array('name' => 'receipt_mobile_number', 'label' => 'receipt_mobile_number', 'type' => 'varchar(STOCK_ID_LENGTH)', 'null' => 'NOT NULL',  'readwrite' => 'readwrite', 'comment' => 'Dream Payments ' );
	
		$this->fields_array[] = array('name' => 'bank_id', 'label' => 'OFX BANKID', 'type' => 'varchar(STOCK_ID_LENGTH)', 'null' => 'NOT NULL',  'readwrite' => 'readwrite', 'comment' => 'OFX BANKID' );
		$this->fields_array[] = array('name' => 'bank_name', 'label' => 'Bank Name', 'type' => 'varchar(STOCK_ID_LENGTH)', 'null' => 'NOT NULL',  'readwrite' => 'readwrite', 'comment' => 'Bank Name' );
		$this->fields_array[] = array('name' => 'account_id', 'label' => 'OFX ACCTID', 'type' => 'varchar(STOCK_ID_LENGTH)', 'null' => 'NOT NULL',  'readwrite' => 'readwrite', 'comment' => 'OFX ACCTID ' );			
		$this->fields_array[] = array('name' => 'FID', 'label' => 'OFX FITID or FID', 'type' => 'varchar(STOCK_ID_LENGTH)', 'null' => 'NOT NULL',  'readwrite' => 'readwrite', 'comment' => 'OFX FITID or FID ' );	
		$this->fields_array[] = array('name' => 'org', 'label' => 'OFX ORG', 'type' => 'varchar(STOCK_ID_LENGTH)', 'null' => 'NOT NULL',  'readwrite' => 'readwrite', 'comment' => 'OFX ORG ' );
		$this->fields_array[] = array('name' => 'memo', 'label' => 'OFX MEMO', 'type' => 'varchar(STOCK_ID_LENGTH)', 'null' => 'NOT NULL',  'readwrite' => 'readwrite', 'comment' => 'OFX MEMO ' );
		$this->fields_array[] = array('name' => 'name', 'label' => 'OFX NAME', 'type' => 'varchar(STOCK_ID_LENGTH)', 'null' => 'NOT NULL',  'readwrite' => 'readwrite', 'comment' => 'OFX NAME ' );	// - TRABSFER, CHEQUE, DEPOSIT
		$this->fields_array[] = array('name' => 'currency', 'label' => 'OFX CURRDEF', 'type' => 'varchar(STOCK_ID_LENGTH)', 'null' => 'NOT NULL',  'readwrite' => 'readwrite', 'comment' => 'OFX CURRDEF ' );

		$this->fields_array[] = array('name' => 'vendor_SIC', 'label' => 'Vendor Store ID Code', 'type' => 'varchar(STOCK_ID_LENGTH)', 'null' => 'NOT NULL',  'readwrite' => 'readwrite', 'comment' => 'Store ID from OFX' );
		$this->fields_array[] = array('name' => 'accountName', 'label' => 'Account Name', 'type' => 'varchar(STOCK_ID_LENGTH)', 'null' => 'NOT NULL',  'readwrite' => 'readwrite', 'comment' => 'mt940 ' );			

		$this->fields_array[] = array('name' => 'from_email', 'label' => 'From Email', 'type' => 'varchar(STOCK_ID_LENGTH)', 'null' => 'NOT NULL',  'readwrite' => 'readwrite', 'comment' => '' );				
		$this->fields_array[] = array('name' => 'to_email', 'label' => 'To Email', 'type' => 'varchar(STOCK_ID_LENGTH)', 'null' => 'NOT NULL',  'readwrite' => 'readwrite', 'comment' => '' );				
		$this->fields_array[] = array('name' => 'shipping_address', 'label' => 'Shipping Address', 'type' => 'varchar(STOCK_ID_LENGTH)', 'null' => 'NOT NULL',  'readwrite' => 'readwrite', 'comment' => '' );					
		$this->fields_array[] = array('name' => 'ship_addr_status', 'label' => 'Shipping Address Status', 'type' => 'varchar(STOCK_ID_LENGTH)', 'null' => 'NOT NULL',  'readwrite' => 'readwrite', 'comment' => '' ); //CONFIRMED
		$this->fields_array[] = array('name' => 'address1', 'label' => 'Address 1', 'type' => 'varchar(STOCK_ID_LENGTH)', 'null' => 'NOT NULL',  'readwrite' => 'readwrite', 'comment' => '' );		
		$this->fields_array[] = array('name' => 'address2', 'label' => 'Address 2', 'type' => 'varchar(STOCK_ID_LENGTH)', 'null' => 'NOT NULL',  'readwrite' => 'readwrite', 'comment' => '' );		
		$this->fields_array[] = array('name' => 'city', 'label' => 'City', 'type' => 'varchar(STOCK_ID_LENGTH)', 'null' => 'NOT NULL',  'readwrite' => 'readwrite', 'comment' => '' );			
		$this->fields_array[] = array('name' => 'state', 'label' => 'State', 'type' => 'varchar(STOCK_ID_LENGTH)', 'null' => 'NOT NULL',  'readwrite' => 'readwrite', 'comment' => '' );			
		$this->fields_array[] = array('name' => 'zip', 'label' => 'Postal Code', 'type' => 'varchar(STOCK_ID_LENGTH)', 'null' => 'NOT NULL',  'readwrite' => 'readwrite', 'comment' => '' );				
		$this->fields_array[] = array('name' => 'country', 'label' => 'Country', 'type' => 'varchar(STOCK_ID_LENGTH)', 'null' => 'NOT NULL',  'readwrite' => 'readwrite', 'comment' => '' );		
		$this->fields_array[] = array('name' => 'phone', 'label' => 'Phone Number', 'type' => 'varchar(STOCK_ID_LENGTH)', 'null' => 'NOT NULL',  'readwrite' => 'readwrite', 'comment' => '' );			
		$this->fields_array[] = array('name' => 'subject', 'label' => 'Subject', 'type' => 'varchar(STOCK_ID_LENGTH)', 'null' => 'NOT NULL',  'readwrite' => 'readwrite', 'comment' => '' );			
		$this->fields_array[] = array('name' => 'country_code', 'label' => 'Country Code', 'type' => 'varchar(STOCK_ID_LENGTH)', 'null' => 'NOT NULL',  'readwrite' => 'readwrite', 'comment' => '' );			
		$this->fields_array[] = array('name' => 'counterpartyType', 'label' => 'Type of Counterparty', 'type' => 'int(11)', 'auto_increment' => 'no', 'null' => 'NOT NULL', 'readwrite' => 'readwrite' );
		$this->fields_array[] = array('name' => 'counterpartyId', 'label' => 'Counterparty ID', 'type' => 'int(11)', 'auto_increment' => 'no', 'null' => 'NOT NULL', 'readwrite' => 'readwrite' );
	}
	function insert_transaction()
	{
		$this->insert_data( get_object_vars($this) );
	}

	
}
