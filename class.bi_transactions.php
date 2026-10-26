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
*	+---------------------+--------------+------+-----+---------+----------------+
*	| Field               | Type         | Null | Key | Default | Extra          |
*	+---------------------+--------------+------+-----+---------+----------------+
*	| id                  | int(11)      | NO   | PRI | NULL    | auto_increment |
*	| smt_id              | int(11)      | NO   |     | NULL    |                |
*	| valueTimestamp      | date         | YES  |     | NULL    |                |
*	| entryTimestamp      | date         | YES  |     | NULL    |                |
*	| account             | varchar(24)  | YES  |     | NULL    |                |
*	| accountName         | varchar(60)  | YES  |     | NULL    |                |
*	| transactionType     | varchar(3)   | YES  |     | NULL    |                |
*	| transactionCode     | varchar(32)  | YES  |     | NULL    |                |
*	| transactionCodeDesc | varchar(32)  | YES  |     | NULL    |                |
*	| transactionDC       | varchar(2)   | YES  |     | NULL    |                |
*	| transactionAmount   | double       | YES  |     | NULL    |                |
*	| transactionTitle    | varchar(256) | YES  |     | NULL    |                |
*	| status              | int(11)      | YES  |     | 0       |                |
*	| matchinfo           | varchar(256) | YES  |     | NULL    |                |
*	| fa_trans_type       | int(11)      | YES  |     | 0       |                |
*	| fa_trans_no         | int(11)      | YES  |     | 0       |                |
*	| fitid               | varchar(32)  | NO   |     | NULL    |                |
*	| acctid              | varchar(32)  | NO   |     | NULL    |                |
	| merchant            | varchar(64)  | NO   |     | NULL    |                |
	| category            | varchar(64)  | NO   |     | NULL    |                |
	| sic                 | varchar(64)  | NO   |     | NULL    |                |
	| memo                | varchar(64)  | NO   |     | NULL    |                |
	| checknumber         | int(11)      | NO   |     | NULL    |                |
	| matched             | int(1)       | NO   |     | 0       |                |
	| created             | int(1)       | NO   |     | 0       |                |
*	+---------------------+--------------+------+-----+---------+----------------+
*	
*
******************************************************************************************************************/
class bi_transactions_model extends generic_fa_interface_model {
	var $id_bi_transactions_model;	//!< Index of table
	protected $id;                  //| int(11)      | NO   | PRI | NULL    | auto_increment |
	protected $smt_id;              //| int(11)      | NO   |     | NULL    |                |
	protected $valueTimestamp;      //| date         | YES  |     | NULL    |                |
	protected $entryTimestamp;      //| date         | YES  |     | NULL    |                |
	protected $account;             //| varchar(24)  | YES  |     | NULL    |                |
	protected $accountName;         //| varchar(60)  | YES  |     | NULL    |                |
	protected $transactionType;     //| varchar(3)   | YES  |     | NULL    |                |
	protected $transactionCode;     //| varchar(32)  | YES  |     | NULL    |                |
	protected $transactionCodeDesc; //| varchar(32)  | YES  |     | NULL    |                |
	protected $transactionDC;       //| varchar(2)   | YES  |     | NULL    |                |
	protected $transactionAmount;   //| double       | YES  |     | NULL    |                |
	protected $transactionTitle;    //| varchar(256) | YES  |     | NULL    |                |
	protected $status;              //| int(11)      | YES  |     | 0       |                |
	protected $matchinfo;           //| varchar(256) | YES  |     | NULL    |                |
	protected $fa_trans_type;       //| int(11)      | YES  |     | 0       |                |
	protected $fa_trans_no;         //| int(11)      | YES  |     | 0       |                |
	protected $fitid;
	protected $acctid;
	protected $merchant;            //| varchar(64)  | NO   |     | NULL    |                |
	protected $category;            //| varchar(64)  | NO   |     | NULL    |                |
	protected $sic;                 //| varchar(64)  | NO   |     | NULL    |                |
	protected $memo;                //| varchar(64)  | NO   |     | NULL    |                |
	protected $checknumber;	//!<int
	protected $matched;	//!<bool
	protected $created;	//!<bool
	protected $g_partner;	//!<varchar	Which action (bank/Quick Entry/...
	protected $g_option;	//!<varchar	Which choice - ATB/Groceries/...



	function __construct()
	{
		//display_notification( __FILE__ . "::" . __LINE__ );
		parent::__construct( null, null, null, null, null);
		//display_notification( __FILE__ . "::" . __LINE__ );
		$this->iam = "bi_transactions";
		$this->define_table();
		$this->matched = 0;
		$this->created = 0;
	}
	function define_table()
	{
		$ind = "id";
		//$ind = "id_" . $this->iam;
		$this->fields_array[] = array('name' => $ind, 'type' => 'int(11)', 'auto_increment' => 'yes', 'readwrite' => 'read' );
		$this->fields_array[] = array('name' => 'updated_ts', 'type' => 'timestamp', 'null' => 'NOT NULL', 'default' => 'CURRENT_TIMESTAMP', 'readwrite' => 'read' );
		if( strlen( $this->company_prefix ) < 2 )
                {
                        $this->company_prefix = TB_PREF;
                }
                $this->table_details['tablename'] = $this->company_prefix . $this->iam;
		$this->table_details['primarykey'] = $ind;
		$this->table_details['orderby'] = 'valueTimestamp, id';
		//$this->table_details['orderby'] = 'transaction_date, transaction_id';
/*
		$this->table_details['index'][0]['type'] = 'unique';
		$this->table_details['index'][0]['columns'] = "transaction_id";
		$this->table_details['index'][0]['keyname'] = "transaction_id";
*/
		//$sidl = 'varchar(' . STOCK_ID_LENGTH . ')';
		//$descl = 'varchar(' . DESCRIPTION_LENGTH . ')';

		$this->fields_array[] = array('name'=> 'id', 'label' => 'ID', 'type' => 'int(11)', 'null' => 'NOT NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );
		$this->fields_array[] = array('name'=> 'smt_id', 'label' => 'Statement ID', 'type' => 'int(11)', 'null' => 'NOT NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );
		$this->fields_array[] = array('name'=> 'valueTimestamp', 'label' => 'Value Timestamp', 'type' => 'date', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );
		$this->fields_array[] = array('name'=> 'entryTimestamp', 'label' => 'Entry Timestamp', 'type' => 'date', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );
		$this->fields_array[] = array('name'=> 'account', 'label' => 'Account', 'type' => 'varchar(24)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );
		$this->fields_array[] = array('name'=> 'accountName', 'label' => 'Account Name', 'type' => 'varchar(60)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );
		$this->fields_array[] = array('name'=> 'transactionType', 'label' => 'Transaction Type', 'type' => 'varchar(3)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );
		$this->fields_array[] = array('name'=> 'transactionCode', 'label' => 'Transaction Code', 'type' => 'varchar(32)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );
		$this->fields_array[] = array('name'=> 'transactionCodeDesc', 'label' => 'Transaction Desc', 'type' => 'varchar(32)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );
		$this->fields_array[] = array('name'=> 'transactionDC', 'label' => 'Transaction DC', 'type' => 'varchar(2)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );
		$this->fields_array[] = array('name'=> 'transactionAmount', 'label' => 'Transaction Amount', 'type' => 'double', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );
		$this->fields_array[] = array('name'=> 'transactionTitle', 'label' => 'Transaction Title', 'type' => 'varchar(256)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );
		$this->fields_array[] = array('name'=> 'status', 'label' => 'Status', 'type' => 'int(11)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => '0' );
		$this->fields_array[] = array('name'=> 'matchinfo', 'label' => 'Match Info', 'type' => 'varchar(256)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );
		$this->fields_array[] = array('name'=> 'fa_trans_type', 'label' => 'FA Transaction Type', 'type' => 'int(11)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => '0' );
		$this->fields_array[] = array('name'=> 'fa_trans_no', 'label' => 'FA Transaction Number', 'type' => 'int(11)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => '0' );
		$this->fields_array[] = array('name'=> 'fitid', 'label' => 'Financial Institute', 'type' => 'varchar(32)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );
		$this->fields_array[] = array('name'=> 'acctid', 'label' => 'Account ID', 'type' => 'varchar(32)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );
		$this->fields_array[] = array('name'=> 'merchant', 'label' => 'Merchant', 'type' => 'varchar(64)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );
		$this->fields_array[] = array('name'=> 'category', 'label' => 'Category', 'type' => 'varchar(64)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );
		$this->fields_array[] = array('name'=> 'sic', 'label' => 'S I Code', 'type' => 'varchar(64)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );
		$this->fields_array[] = array('name'=> 'memo', 'label' => 'Memo', 'type' => 'varchar(64)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );
		$this->fields_array[] = array('name'=> 'checknumber', 'label' => 'Check Number', 'type' => 'int(11)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );
		$this->fields_array[] = array('name'=> 'matched', 'label' => 'Matched', 'type' => 'int(1)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => '0' );
		$this->fields_array[] = array('name'=> 'created', 'label' => 'Created', 'type' => 'int(1)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => '0' );
		$this->fields_array[] = array('name'=> 'g_partner', 'label' => 'Transaction Type (Partner)', 'type' => 'varchar(32)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => '0' );
		$this->fields_array[] = array('name'=> 'g_option', 'label' => 'Transaction Type Detail', 'type' => 'varchar(32)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => '0' );
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
	function insert_transaction()
	{
		$this->insert_data( get_object_vars($this) );
	}
	function summary_sql( $TransAfterDate, $TransToDate, $statusFilter )
	{
		$sql = " SELECT t.*, s.account our_account, s.currency from " . TB_PREF ."bi_transactions t LEFT JOIN " . TB_PREF . "bi_statements as s ON t.smt_id = s.id";
        	$sql .= " WHERE t.valueTimestamp >= ".db_escape( date2sql( $TransAfterDate ) ) ." AND t.valueTimestamp <  " . db_escape( date2sql( $TransToDate ) );
    		if ( $statusFilter != 255) {
        		$sql .= " AND t.status = ".db_escape( $statusFilter );
    		}
    		$sql.= " ORDER BY t.valueTimestamp ASC";
		var_dump( $sql );
/**/
    		$res = db_query($sql, 'unable to get transactions data');
		var_dump( $res );
		return $res;
/**/
	}
	/**//*************************************************************************
	* Update bi_trans clearing status
	*
	*	   If we had created the transaction, should we void it?
	*		By not voiding it, the transaction will later be "matched".
	*
	* @param int BI transaction index
	* @param array list of related transactions
	* @param int The transaction number
	* @param int the Transaction Type (JE/BP/SP/...)
	* @returns none
	******************************************************************************/
	function reset_transactions($tid, $cids, $trans_no, $trans_type) 
	{
		$cids[] = $tid;
		$cids = implode(',', $cids);
	
		$sql = "
			UPDATE ".TB_PREF."bi_transactions
			SET status=0,
				fa_trans_no=".db_escape($trans_no).",
				fa_trans_type=".db_escape($trans_type);
			$sql .= ",
					matched=0";
			$sql .= ",
					created=0";
			$sql .= "
				WHERE id in ($cids)";
		display_notification($sql);
		db_query($sql, 'Could not update trans');
	}
	/**//*************************************************************************
	* Update bi_trans with the related info to FA gl transactions
	*
	*       Hooks db_prevoid does similar
	*
	* @param int BI transaction index
	* @param array list of related transactions
	* @param int The status to set
	* @param int The transaction number
	* @param int the Transaction Type (JE/BP/SP/...)
	* @param bool matched the transaction
	* @param bool created the transaction
	* @param string|null Transaction type code SP/BT/QE/...
	* @param string The QE or vendor or customer or... int as string
	* @returns none
	******************************************************************************/
	function update_transactions($tid, $cids, $status, $trans_no, $trans_type, $matched = 0, $created = 0, $g_partner = null, $g_option = "" ) 
	{
		$cids[] = $tid;
		$cids = implode(',', $cids);
	
		$sql = "
			UPDATE ".$this->table_details['tablename'] . 
			" SET status=".db_escape($status).",
				fa_trans_no=".db_escape($trans_no).",
				fa_trans_type=".db_escape($trans_type);
		if( $matched )
		{
			$sql .= ", matched=1";
		}
		else
		if( $created )
		{
			$sql .= ", created=1";
		}
/** MANTIS 2933*/
		if( null != $g_partner )
		{
			$sql .= ", g_partner='" . $g_partner . "'";
			$sql .= ", g_option='" . $g_option . "'";
		}
/** ! MANTIS 2933*/
			$sql .= "
			WHERE id in ($cids)";
		//display_notification($sql);
		db_query($sql, 'Could not update trans');
	}
	/**//*************************************************************************
	* Update bi_trans with the related info to FA gl transactions
	*
	* @param int BI transaction index
	* @param string account
	* @param string account Name
	* @returns none
	******************************************************************************/
	function update_transactions_account($tid, $account, $accountName )
	{
	        $cids = array();
	        $cids[] = $tid;
	        $cids = implode(',', $cids);
	
	        $sql = "
	                UPDATE ".TB_PREF."bi_transactions
	                SET
	                        account=" .     db_escape($account).",
	                        accountName=" . db_escape($accountName)."
	                WHERE id in ($cids)";
	        display_notification($sql);
	        db_query($sql, 'Could not update trans');
	}
	/**//*************************************************************************
	* Reset bi_trans data when the related FA gl transaction is voided
	*
	* @param int the Transaction Type (JE/BP/SP/...)
	* @param int The transaction number
	* @returns none
	******************************************************************************/
	function db_prevoid( $trans_type, $trans_no )
	{
		//When a FA GL entry is being voided
		$sql = "
			UPDATE " . $this->table_details['tablename'] .
			" SET status=0, fa_trans_no=0, fa_trans_type=0
			WHERE
				fa_trans_no=".db_escape($trans_no)." AND
				fa_trans_type=".db_escape($trans_type)." AND
				status = 1";
		//display_notification($sql);
		db_query($sql, 'Could not void transaction');
	}
	/**//**********************************************************************
	* Get a specific transaction's details
	*
	* @param int index
	* @returns array transaction row from db
	***************************************************************************/
	function get_transaction( $tid = null) 
	{
		if( $tid == null )
		{
			if( isset( $this->id ) )
				$tid = $this->id;
			else
				throw new Exception( "No ID set to search for" );
		}
	        $sql = "
	            SELECT t.*, s.account our_account FROM ".TB_PREF."bi_transactions t
	            LEFT JOIN ".TB_PREF."bi_statements as s ON t.smt_id = s.id
	            WHERE t.id=".db_escape($tid);
	        $result = db_query($sql, "could not get transaction with id $tid");
	        return db_fetch($result);
	}
	/**//**********************************************************************
	* Get a the normal actions for a counterparty
	*
	* @since 20240729 
	* @param string Account to search for
	* @returns array transaction rows from db
	***************************************************************************/
	function get_normal_pairing( $account = null) 
	{
		$sql = "SELECT count(*) as count, `account`, `g_option`, `g_partner` FROM `0_bi_transactions` group by account, g_option, g_partner";
		if( null != $account )
			$sql .= " WHERE account = '" . $account . "'";
	        $result = db_query($sql, "could not get transaction with id $tid");
	        return db_fetch($result);
	}
	/**//**********************************************************************
	* Convert Transaction array to this object
	*
	* @param class
	* @returns int how many fields did we copy
	**************************************************************************/
	function trz2obj( $trz )
	{
		return $this->obj2obj( $trz );
/*
		$cnt = 0;
		foreach( get_object_vars($this) as $key )
		{
			if( isset( $trz->$key ) )
			{
				$this-set( "$key", $trz->$key );	
				$cnt++;
			}
		}
		return $cnt;
*/
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
	}
	/**//************************************************************
	* Hand build the UPDATE statement
	*
	* @param none
	* @returns string SQL statement
	*****************************************************************/
	function hand_update_sql()
	{
               $sql = 	"UPDATE " . $this->table_details['tablename'] .
			"SET " .
			"smt_id=" .  db_escape($this->smt_id) . ", ".
			"valueTimestamp=" .  db_escape($this->valueTimestamp) . ", ".
			"entryTimestamp=" .  db_escape($this->entryTimestamp) . ", ".
			"account=" .  db_escape($this->account) . ",".
			"accountName=" .  db_escape($this->accountName) . ", ".
			"transactionType=" .  db_escape($this->transactionType) . ", ".
                    	"transactionCode=" .  db_escape($this->transactionCode) . ", ".
                    	"transactionCodeDesc=" .  db_escape($this->transactionCodeDesc) . ", ".
                    	"transactionDC=" .  db_escape($this->transactionDC) . ", ".
                    	"transactionAmount=" .  db_escape($this->transactionAmount) . ", ".
                    	"transactionTitle=" .  db_escape($this->transactionTitle) . ", ".
                    	"merchant=" .  db_escape($this->merchant) . ", ".
                    	"category=" .  db_escape($this->category) . ", ".
                    	"status=" .  db_escape($this->status) . ", ".
                    	"memo=" .  db_escape($this->memo) . ", ".
                    	"sic=" .  db_escape($this->sic) . ", ".
                    	"checknumber=" .  db_escape($this->checknumber);
		$sql .=	" WHERE ";
		return $sql;
	}
	/**//************************************************************
	* Determine if this particular transaction already exists in the staging table
	*
	*	We need to do duplicate checking.  Most banks allow a person to select a date range for their transactions.
	*	That is probably why the original author, when taking apart his mt90 files, made each day its own statement.
	*
	*	We can have the same total for the same vendor on the same date and not automatically be a duplicate
	*		Example when I buy flats of coke on sale limit 4 per transaction. 
	*	In this case it is the transaction reference (transactionCode) that is the unique identifier.
	*
	*	The trans ref is not guaranteed unique between banks.  Only within the bank.
	*
	*	I've changed the table definition to have a unique key on transactionCode-accountName-transactionAmount.
	*
	* @param none
	* @returns bool already exists? 
	*****************************************************************/
	function trans_exists()
	{
		$sql = " SELECT * from " . $this->table_details['tablename'] .
			" WHERE transactionCode=" . db_escape($this->transactionCode) . " AND accountName=" . db_escape($this->accountName);
		//	display_notification( __FILE__ . "::" . __LINE__ . " " . $sql );

                $res = db_query($sql, "could not Select transaction");
                $dupes=0;
                while($row = db_fetch($res) )
                {
                	if( isset( $row['transactionCode'] ) )
                        	$dupes++;
           	}
		//There should only be 1 result with this account + transaction code.  Banks are supposed to be unique internally.
		//Should we throw an error if there are more than 1?
		if( $dupes > 0 )
		{
			if( $dupes == 1 )
			{
				//Set this classes' variables from the db result.
				try
				{
					$ret = $this->arr2obj( $row );
					//ret is number of fields set.
				} 
				catch( Exception $e )
				{
					display_notification( __FILE__ . "::" , __LINE__ . "::" . $e->getCode() . ":" . $e->getMessage() );
				}
			}
			return true;
		}
		else
		{
			return false;
		}
	}
	/**//******************************************************************
	* Update a transaction record
	*
	* There are a couple of use cases here
	* 	We are re-importing the same file because we've enhanced the tables
	*		Update the memo, category, status, sic, etc fields
	*	The record appears in multiple files (we've changed export dates at the bank)
	*		If this is a more recent file, we want to update other info too
	*			update amount.
	*				If the transaction is processed, we don't want to change this without adjusting the GLs too!!
	*			void the fa_trans and re-do
	*		If this is an older file, we only want to update info that is blank
	*
	* @param array the transaction from bank_import's import
	* @returns bool success
	*****************************************************************/
	function update( $arr )
	{
		//This class's variables were set by trans_exists when called by import_statements

		//Find out what has changed
		$diffarr = array();
		foreach( get_object_vars($this) as $key => $value )
                {
                        if( isset( $arr[$key] ) )
                        {
                                if( $this->isdiff( $key, $arr[$key] ) )
				{
					$diffarr[$key] = $value;
				}
                        }
                        else
                        {
                                //      display_notification( __FILE__ . "::" . __LINE__ . " $key not set in " . print_r( $arr, true ) );
                        }
                }

		if( $this->matched )
		{
			//If matched, we may have just looked at Journals and amount
		}
		if( $this->created )
		{
			if( isset( $diffarr['transactionCode'] ) )
			{
				//Transaction Code is the key we checked against.  If it changed we have a logic error elsewher
				throw new Exception( "Transaction Code, which was our search key changed.  LOGIC ERROR!", KSF_INVALID_DATA_VALUE );
			}
			if( isset( $diffarr['accountName'] ) )
			{
				//account can't change - not an identical transaction.  ERROR somewhere
				throw new Exception( "We should not have matched against this transaction - accountName changed!", KSF_INVALID_DATA_VALUE );
			}
			if( isset( $diffarr['account'] ) )
			{
				//account can't change - not an identical transaction.  ERROR somewhere
				throw new Exception( "We should not have matched against this transaction - account changed!", KSF_INVALID_DATA_VALUE );
			}
			if( isset( $diffarr['valueTimestamp'] ) OR isset( $diffarr['entryTimestamp'] ) )
			{
				//time can't change if transactions are immutable - not an identical transaction.  ERROR somewhere
				throw new Exception( "We should not have matched against this transaction - Timestamps changed!", KSF_INVALID_DATA_VALUE );
			}
			if( isset( $diffarr['transactionType'] ) )
			{
				//Transaction Type shouldn't change, but there might be some reason it could - some change in my code somewhere and re-import an old file?  
				//throw new Exception( "We should not have matched against this transaction - account changed!", KSF_INVALID_DATA_VALUE );
			}
			if( isset( $diffarr['transactionAmount'] ) )
			{
				if( abs($diffarr['transactionAmount']) !== abs($this->transactionAmount) )
				{
					throw new Exception( "(ABS) Transaction Amount changed! It is possible that the sign changed due to our re-processing, but the absolute value shouldn't", KSF_INVALID_DATA_VALUE );
				}
				else
				{
					//Sign changed but value didn't.  We will want to update our record.
					$this->set( "transactionAmount", $diffarr['transactionAmount'] );
				}
			}
			//smt_id could change if we changed date ranges and imported.
			if( isset( $diffarr['smt_id'] ) )
			{
				$this->set( "smt_id",  $diffarr['smt_id'] );
			}
			//transactionCodeDesc and transactionDC could possibly change
			if( isset( $diffarr['transactionCodeDesc'] ) )
			{
				$this->set( "transactionCodeDesc",  $diffarr['transactionCodeDesc'] );
			}
			if( isset( $diffarr['transactionDC'] ) )
			{
				$this->set( "transactionDC",  $diffarr['transactionDC'] );
			}
			//If status is set, then we've matched/created.,,
			//matchinfo shouldn't match on a re-import
			//fa_trans_type and fa_trans_no should not match.
			//fitid and acctid should not change.  However our earlier imports didn't set it.
			if( isset( $diffarr['fitid'] ) )
			{
				$this->set( "fitid",  $diffarr['fitid'] );
			}
			if( isset( $diffarr['acctid'] ) )
			{
				$this->set( "acctid",  $diffarr['acctid'] );
			}
			//merchant, category and sic may not have been set in the past.
			if( isset( $diffarr['merchant'] ) )
			{
				$this->set( "merchant",  $diffarr['merchant'] );
			}
			if( isset( $diffarr['category'] ) )
			{
				$this->set( "category",  $diffarr['category'] );
			}
			if( isset( $diffarr['sic'] ) )
			{
				$this->set( "sic",  $diffarr['sic'] );
			}
			

			//If created, we might have to update/void transactions     
			//	| fa_trans_type       | int(11)      | YES  |     | 0       |                |
			//	| fa_trans_no         | int(11)      | YES  |     | 0       |                |
			//if so, status, matched, created need to be set to 0 as well!
		}
	}
}
