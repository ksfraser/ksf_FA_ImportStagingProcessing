<?php
/**********************************************************************
***********************************************************************/
$path_to_root = __DIR__ . "/../..";

include_once( __DIR__ . "/includes/parsers.inc");

/*
require_once( "class.ksf_import_square_transactions.php" );
require_once( "class.ksf_import_square_items.php" );
require_once( "class.ksf_import_square_sale.php" );
*/

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

class import_statements_gui extends origin
{
	//var $help_context;	//inherited
	protected $importsummary;
	function __construct()
	{
		$this->help_context = "Import Square Transactions";
	}
	function display()
	{
		page(_($help_context = "Import Square Transactions"));
	}
	/**//**********************************************************
	* display the form to upload the files
	*
	* @param none
	* @returns none
	****************************************************************/
	function do_upload_form() 
	{
		start_form(true);
		$parsers = array();
		$_parsers = getParsers();
		foreach($_parsers as $pid => $pdata) 
		{
			$parsers[$pid] = $pdata['name'];
		}
		div_start('doc_tbl');
			start_table(TABLESTYLE);
				$th = array(_("Select File(s) and type"), '');
				table_header($th);
	
					label_row(_("Format:"), array_selector('parser', null, $parsers, array('select_submit' => true)));
					foreach($_parsers[$_POST['parser']]['select'] as $param => $label) 
					{
				/*
						switch($param) {
							case 'bank_account':
								bank_accounts_list_row($label, 'bank_account', $selected_id=null, $submit_on_change=false);
								break;
							case 'customer':
								break;
							default:
								break;
						}
				*/
					}
				label_row(_("Files"), "<input type='file' name='files[]' multiple />");
				start_row();
					label_cell('Upload', "class='label'");
					submit_cells('upload', _("Upload"));
				end_row();
			hidden('action', 'upload_file');
			end_table(1);
		div_end();
		end_form(2);
	}
	/**//*************************************
	* Display that we are importing statements
	*
	* @param none
	* @return none but outputs to the screen
	******************************************/
	function import_statements() 
	{
		if( ! isset( $this->importsummary ) )
		{
			throw new Exception( "Summary not set so nothing to display" );
		}
		start_table(TABLESTYLE);
		start_row();
		echo "<td width=100%><pre>\n";
		echo '<pre>';
		echo $this->importsummary;
		echo '</pre>';
		echo "</pre></td>";
		end_row();
		start_row();
		echo '<td>';
		submit_center_first('goback', 'Go back');
		echo '</td>';
		end_row();
		end_table(1);
		hidden('parser', $_POST['parser']);
	}
	function parse_uploaded_results( $summary_arr )
	{
		start_table(TABLESTYLE);
		foreach( $summary_arr as $row )
		{
			//var_dump( $row );
			start_row();
			echo "<td width=100%><pre>\n";
			echo "======================================\n";
			echo "Processing file " . $row['file']['filename'] . " with format " . $row['file']['parser'] . "...\n";
			echo "\n";
			echo "Valid statements   :" .  $row['smt_ok'] . "\n";
			echo "Invalid statements :" .  $row['smt_err'] . "\n";
			echo "Total transactions :" .  $row['trz_ok'] . "\n";
			echo "======================================\n";
			echo "</pre></td>";
	
			end_row();
		}
/*
* The import routines insert the data as we load it, so this Insert Data button is redundant.
		start_row();
		echo '<td>';
		submit_center_first('import', 'Insert Data');
		echo '</td>';
		end_row();
*/
		start_row();
		echo '<td>';
		submit_center_first('goback', 'Go back');
		echo '</td>';
		end_row();
		
		end_table(1);
		HIDDEN('parser', $_POST['parser']);
	}
	/**//*****************************************
	* Wrapper to FA display_error
	*
	* @param string message
	* @returns none displays to screen
	**********************************************/
	function display_error( $msg )
	{
		display_error( $msg );
	}
	/**//*****************************************
	* Wrapper to FA display_warning
	*
	* @param string message
	* @returns none displays to screen
	**********************************************/
	function display_warning( $msg )
	{
		display_warning( $msg );
	}
	/**//*****************************************
	* Wrapper to FA display_notification
	*
	* @param string message
	* @returns none displays to screen
	**********************************************/
	function display_notification( $msg )
	{
		display_notification( $msg );
	}
}

