<?php
/**********************************************************************
***********************************************************************/
$page_security = 'SA_BANKACCOUNT';
$path_to_root = __DIR__ . "/../..";
include($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");

include_once( __DIR__ . "/includes/parsers.inc");

require_once( "class.ksf_import_square_transactions.php" );
require_once( "class.ksf_import_square_items.php" );
require_once( "class.ksf_import_square_sales_model.php" );

/*********
*	TODO
*	This process DOES NOT recognize re-uploading a file
*	Since this inserts the data on upload, we could end up with duplicate entries.
*	We can't put a unique index on the tables since we'd have to basically include ALL fields
*	  since you could have multiple lines with the same product, price in the same transaction.
*		Maybe square merges them all together if there is 1 price...but different prices
*		would certainly have separate lines.
*	This could also lead to issues once we edit the customer and then reload - defualt customer
*	would then look like a new transaction even if it is a duplicate.
*	
*	Can't use just timestamps either because we could have multiple tills running at once.
*	
*	Not sure what uniqueness square forces on device names, employee names, etc.  Can you
*	be logged in multiple times at the same time?
*
*	Transaction ID should be unique in the transactions table.  We could check that for the existance
*	before inserting into Items, and insert into transactions once we've inserted into items.  Use
*	a MYSQL transaction and rollback if both tables not touched...
*/

class import_statements_controller
{
	protected $gui;
	protected $statements;

	function __construct()
	{
		$this->gui = new import_statements_gui();
	}
	/**//*************************************
	* import statements and display on screen
	*
	* @param none
	* @return none but outputs to the screen
	******************************************/
	function import_statements() 
	{
		$summary = "";
		$this->statements = unserialize($_SESSION['statements']);
		foreach($this->statements as $id => $smt) 
		{
			$summary .= $this->importStatement($smt) . "\n";
		}
		$this->gui->set( "importsummary", $summary );
		$this->gui->import_statements();
	}
	/**//*******************************************************
	* Import the statements
	*
	* @param array statements
	* @return string summary of import (errors, imports, updates)
	*************************************************************/
	function importStatement($smt) 
	{
			//display_notification( __FILE__ . "::" . __LINE__ . ":" . print_r( $smt, true ) );
		$message = '';

		//Check to see if this transaction has already been inserted
		require_once(  './class.ksf_import_square_transactions.php' );
		$bis = new ksf_import_square_transactions_model();
		$bis->set( "statementId", $smt->statementId );
		$exists = $bis->statement_exists();
		$bis->obj2obj( $smt );
	
		if( ! $exists )
		{
				display_notification( __FILE__ . "::" . __LINE__ . ":: Statement Doesn't Exist.  Inserting" );
			$sql = $bis->hand_insert_sql();
			$res = db_query($sql, "could not insert transaction");
	    		$smt_id = db_insert_id();
			$bis->set( "id", $smt_id );
				display_notification( __FILE__ . "::" . __LINE__ . "Inserted Statement $smt_id" );
	    		$message .= "new, imported";
		} else 
		{
				//display_notification( __FILE__ . "::" . __LINE__ . "Statement Exists.  Updating" );
			$bis->update_statement();
				display_notification( __FILE__ . "::" . __LINE__ . "Updated Statement $smt->statementId " );
	    		$message .= "existing, updated";
		}
		//$smt_id = $bis->get( "statementId" );
		$smt_id = $bis->get( "id" );
	/* */
		require_once( 'class.bi_transactions.php' );
		foreach($smt->transactions as $id => $t) 
		{
			set_time_limit( 0 );	//Don't time out oin php.  Apache might still kill us...
	
			try {
				unset( $bit );
				$bit = new bi_transactions_model();
			} catch( Exception $e )
			{
				display_notification( __FILE__ . "::" . __LINE__ . " " . print_r( $e, true ) );
			}
			$bit->trz2obj( $t );
			$bit->set( "smt_id", $smt_id );
			$dupe = $bit->trans_exists();
			if( $dupe )
			{
				display_notification( __FILE__ . "::" . __LINE__ . " Transaction Exists for statement: $smt_id::" . print_r( $bit, true ) );
	/**
	 * Don't re-insert duplicate.
	 * Update in certain cases. 
	 */
				//trans_exists sets the variables out of the DB
				//$bit->update( $t );
			}
			else
			{
				$sql = $bit->hand_insert_sql();
				$res = db_query($sql, "could not insert transaction");
				$t_id = db_insert_id();
				display_notification( __FILE__ . "::" . __LINE__ . " Inserted $t_id " );
			}
		}	//foreach statement
		$message .= ' ' . count($smt->transactions) . ' transactions';
		return $message;
	/* */
	}	//import_statement fc
}
	
