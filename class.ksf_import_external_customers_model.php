<?php

require_once( '../ksf_modules_common/class.generic_fa_interface_model.php' );

/**//*********************************************************************
* Model class for the ?_ksf_import_external_customers table
*	
*	+----------------------------------+-------------+------+-----+---------------------+-------------------------------+
*	| Field                            | Type        | Null | Key | Default             | Extra                         |
*	+----------------------------------+-------------+------+-----+---------------------+-------------------------------+
*	| ksf_import_external_customers_id | int(11)     | NO   | MUL | NULL                | auto_increment                |
*	| last_updated                     | datetime    | NO   |     | current_timestamp() | on update current_timestamp() |
*	| external_customer_type           | varchar(32) | NO   |     | NULL                |                               |
*	| external_customer_id             | varchar(64) | YES  |     | NULL                |                               |
*	| external_customer_name           | varchar(64) | YES  |     | NULL                |                               |
*	| external_customer_reference      | varchar(64) | NO   |     | NULL                |                               |
*	| fa_debtor_no                     | int(11)     | NO   |     | NULL                |                               |
*	| fa_person_id                     | int(11)     | NO   |     | NULL                |                               |
*	| fa_branch_code                   | int(11)     | NO   |     | NULL                |                               |
*	| fa_crm_contacts_id               | int(11)     | NO   |     | NULL                |                               |
*	| ksf_import_other_table_name      | varchar(64) | NO   |     | NULL                |                               |
*	| ksf_import_other_table_id        | int(11)     | NO   |     | NULL                |                               |
*	+----------------------------------+-------------+------+-----+---------------------+-------------------------------+
*	
*
**********************************************************************************************************/
class ksf_import_external_customers_model extends generic_fa_interface_model
{

	protected $ksf_import_external_customers_id;	//!<int(11) | NO | MUL | NULL| auto_increment|
	protected $last_updated;			//!<datetime| NO | | current_timestamp() | on update current_timestamp() |
	protected $external_customer_type;		//!<varchar(32) | NO | | NULL| |
	protected $external_customer_id;		//!<varchar(64) | YES| | NULL| |
	protected $external_customer_name;		//!<varchar(64) | YES| | NULL| |
	protected $external_customer_reference;		//!<varchar(64) | YES| | NULL| |
	protected $fa_debtor_no;			//!<int(11) | NO | | NULL| |
	protected $fa_person_id;			//!<int(11) | NO | | NULL| |
	protected $fa_branch_code;			//!<int(11) | NO | | NULL| |
	protected $fa_crm_contacts_id;			//!<int(11) | NO | | NULL| |
	protected $ksf_import_other_table_name;		//!<varchar(64) | NO | | NULL| |
	protected $ksf_import_other_table_id;		//!<int(11) | NO | | NULL| |

	function __construct()
	{
		//display_notification( __FILE__ . "::" . __LINE__ . "::" );
		parent::__construct( null, null, null, null, null);

		$this->matched = 0;
		$this->created = 0;
		$this->iam = "ksf_import_external_customers";
		$this->arr2obj_ran = false;
		$this->define_table();
	}
	/**//***************************************************************
	* Set our fields from an array
	*
	* @since 20250306
	*
	* @param array data to set
	* @returns int how many fields set
	********************************************************************/
	function arr2obj( $arr )
	{
		//display_notification( __FILE__ . "::" . __LINE__ . "::" );

		try{
			$res = parent::arr2obj( $arr );
			if( $res > 0 )
			{
				$this->arr2obj_ran = true;
			}
			else
			{
				display_warning( __FILE__ . "::" . __LINE__ . ":: arr2obj came back with res <=0: $res" );
				display_warning( __FILE__ . "::" . __LINE__ . "::" . print_r( $this, true ) );
			}
			return $res;
		} 
		catch( Exception $e )
		{
			throw $e;
		}
	}
	/**//***************************************************************
	* Describe the table we are the MODEL for so that auto code can work.
	*
	* @param none
	* @returns none
	**********************************************************************/
	function define_table()
	{
		$ind = $this->iam . "_id";
		$this->fields_array[] = array('name' => $ind, 'type' => 'int(11)', 'auto_increment' => 'yes', 'readwrite' => 'read' );
		$this->fields_array[] = array('name' => 'last_updated', 'type' => 'timestamp', 'null' => 'NOT NULL', 'default' => 'CURRENT_TIMESTAMP', 'readwrite' => 'read' );
		$this->table_details['tablename'] = $this->company_prefix . $this->iam;
		$this->set( 'tablename', $this->company_prefix . $this->iam );	//This should set table_interface too
		$this->table_details['primarykey'] = $ind;
		//$this->table_details['orderby'] = 'transaction_date, transaction_id';
/*
		$this->table_details['index'][0]['type'] = 'unique';
		$this->table_details['index'][0]['columns'] = "transaction_id";
		$this->table_details['index'][0]['keyname'] = "trans_id";
*/
		//$this->fields_array[] = array('name' => 'stock_id', 'label' => 'SKU', 'type' => 'varchar(256)', 'null' => 'NOT NULL',  'readwrite' => 'readwrite');
		//$sidl = 'varchar(' . STOCK_ID_LENGTH . ')';
		//$descl = 'varchar(' . DESCRIPTION_LENGTH . ')';

		//$this->fields_array[] = array('name' => 'inserted_fa', 'label' => 'Inserted into FA', 'type' => 'bool', 'null' => 'NOT NULL',  'readwrite' => 'readwrite', 'default' => '0' );
		$this->fields_array[] = array('name' => 'ksf_import_external_customers_id', 'type' => 'int(11)', 'null' => 'NOT NULL', 'default' => '', 'readwrite' => 'read');
		$this->fields_array[] = array('name' => 'last_updated', 'type' => 'datetime', 'null' => 'NOT NULL', 'default' => 'current_timestamp()', 'readwrite' => 'read');
		$this->fields_array[] = array('name' => 'external_customer_type', 'type' => 'varchar(32)', 'null' => 'NOT NULL', 'default' => '', 'readwrite' => 'readwrite');
		$this->fields_array[] = array('name' => 'external_customer_id', 'type' => 'varchar(64)', 'null' => 'NOT NULL', 'default' => '', 'readwrite' => 'readwrite');
		$this->fields_array[] = array('name' => 'external_customer_name', 'type' => 'varchar(64)', 'null' => 'NOT NULL', 'default' => '', 'readwrite' => 'readwrite');
		$this->fields_array[] = array('name' => 'fa_debtor_no', 'type' => 'int(11)', 'null' => 'NOT NULL', 'default' => '', 'readwrite' => 'readwrite');
		$this->fields_array[] = array('name' => 'fa_person_id', 'type' => 'int(11)', 'null' => 'NOT NULL', 'default' => '', 'readwrite' => 'readwrite');
		$this->fields_array[] = array('name' => 'fa_branch_code', 'type' => 'int(11)', 'null' => 'NOT NULL', 'default' => '', 'readwrite' => 'readwrite');
		$this->fields_array[] = array('name' => 'fa_crm_contacts_id', 'type' => 'int(11)', 'null' => 'NOT NULL', 'default' => '', 'readwrite' => 'readwrite');
		$this->fields_array[] = array('name' => 'ksf_import_other_table_name', 'type' => 'varchar(64)', 'null' => 'NOT NULL', 'default' => '', 'readwrite' => 'readwrite');
		$this->fields_array[] = array('name' => 'ksf_import_other_table_id', 'type' => 'int(11)', 'null' => 'NOT NULL', 'default' => '', 'readwrite' => 'readwrite');
		
		$this->table_interface->unset_( "table_details" );
		$this->table_interface->set( "table_details", $this->table_details );
		$this->table_interface->unset_( "fields_array" );
		$this->table_interface->set( "fields_array", $this->fields_array );

	}
	/**//***************************************************************************
	* Take an instance of this object, and insert its values into the table.
	*
	* @param none uses internal
	* @returns none
	********************************************************************************/
	function insert()
	{
		
		if( $this->arr2obj_ran )
		{
			//copied from insert_data
  			try
                        {
                                $this->table_interface->insert_table();
                                $this->table_interface->update_table();
                                return true;
                        }
                        catch( Exception $e )
                        {
                                return false;
                        }
/*
			try {
				$this->insert_data( get_object_vars($this) );
			} catch( Exception $e )
			{
				throw $e;
			}
*/
		}
		else
		{
			throw new Exception( "Our variables haven't been set properly so we can't insert", KSF_FIELD_NOT_SET );
		}
	}
	/**//*********************************************************************
	* Hand crafted SQL to insert a match
	*
	* GOTCHA - code is based upon import_square field names
	*
	* @since 20250317
	*
	* @param none uses GET
	* @returns string SQL
	*************************************************************************/
	function insertMatchSQL()
	{
               $sql = "INSERT into `" . TB_PREF . "ksf_import_external_customers`( ";
                        $sql .= " external_customer_type, ";
                        $sql .= " external_customer_id, ";
                        $sql .= " external_customer_name, ";
                        $sql .= " external_customer_reference, ";
                        $sql .= " fa_debtor_no ";
                if( isset( $_GET['fa_person_id'] ) )
                {
                        $sql .= ", fa_person_id ";
                }
                if( isset( $_GET['fa_branch_code'] ) )
                {
                        $sql .= ", fa_branch_code ";
                }
                if( isset( $_GET['fa_crm_contacts_id'] ) )
                {
                        $sql .= ", fa_crm_contacts_id ";
                }
                if( isset( $_GET['ksf_import_other_table_name'] ) )
                {
                        $sql .= ", ksf_import_other_table_name ";
                }
                if( isset( $_GET['ksf_import_other_table_id'] ) )
                {
                        $sql .= ", ksf_import_other_table_id ";
                }
                $sql .= " )";
                $sql .= " VALUES ( ";
                        $sql .= " 'SQUARE', ";
                        $sql .= " '" . $_GET['Customer_id'] . "', ";
                        $sql .= " '" . $_GET['customer_name'] . "', ";
                        $sql .= " '" . $_GET['customer_reference_id'] . "', ";
                        $sql .= " '" . $_GET['debtor_no'] . "' ";
                if( isset( $_GET['fa_person_id'] ) )
                {
                        $sql .= ", '" . $_GET['fa_person_id'] . "' ";
                }
                if( isset( $_GET['fa_branch_code'] ) )
                {
                        $sql .= ", '" . $_GET['fa_branch_code'] . "' ";
                }
                if( isset( $_GET['fa_crm_contacts_id'] ) )
                {
                        $sql .= ", '" . $_GET['fa_crm_contacts_id'] . "' ";
                }
                if( isset( $_GET['ksf_import_other_table_name'] ) )
                {
                        $sql .= ", '" . $_GET['ksf_import_other_table_name'] . "', ";
                }
                if( isset( $_GET['ksf_import_other_table_id'] ) )
                {
                        $sql .= ", '" . $_GET['ksf_import_other_table_id'] . "', ";
                }
                $sql .= " )";
		return $sql;
	}
	/**//*************************************************
	* Search for the matching customer
	*
	*	There should only be 1 match.
	*	This sends back our table's column name
	*
	* @since 20250319
	*
	* @param string|null Customer Name or use internal
	* @param string|null Customer type or use internal
	* @returns array customer data ONE ROW!
	*****************************************************/
	function searchCustomersByName( $customer_name = null, $customer_type = null )
	{
		if( null == $customer_name )
		{
			if( isset( $this->external_customer_name ) )
			{
				$customer_name = $this->external_customer_name;
			}
			else
			{
				throw new Exception( "Customer Name isn't set so we can't search!", KSF_VAR_NOT_SET );
			}
		}
		if( null == $customer_type )
		{
			if( isset( $this->external_customer_type ) )
			{
				$customer_type = $this->external_customer_type;
			}
			else
			{
				throw new Exception( "Customer Type isn't set so we can't search!", KSF_VAR_NOT_SET );
			}
		}
		$sql = "SELECT * FROM " . TB_PREF .  "ksf_import_external_customers";
		$sql .= " WHERE external_customer_name='" . $customer_name . "'";
		$sql .= " AND external_customer_type='" . $customer_type . "'";
		//unset( $res );
		$res = db_query( $sql, "Couldn't select customer" );
		$row =  db_fetch_assoc( $res );
		return $row;
	}
	/**//*************************************************
	* Search for the matching customer
	*
	* @since 20250317
	*
	* @param string  Customer Name
	* @param string  Customer Type
	* @returns array customer data
	*****************************************************/
	function findCustomer( $customer_name, $customer_type )
	{
		return $this->searchCustomersByName( $customer_name, $customer_type );
	}

}
