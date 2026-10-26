<?php

/**//*********************************************************************
* Model class for the ?_ksf_import_square_transactions table
*
*	+-------------------------+-------------+------+-----+---------------------+----------------+
*	| Field                   | Type        | Null | Key | Default             | Extra          |
*	+-------------------------+-------------+------+-----+---------------------+----------------+
*	| id                      | int(11)     | NO   | PRI | NULL                | auto_increment |
*	| Date                    | date        | NO   |     | NULL                |                |
*	| Time                    | varchar(8)  | NO   |     | NULL                |                |
*	| Timezone                | varchar(64) | NO   |     | NULL                |                |
*	| gross_sales             | float       | NO   |     | NULL                |                |
*	| discounts               | float       | NO   |     | NULL                |                |
*	| service_charges         | float       | NO   |     | NULL                |                |
*	| gift_card_sales         | float       | NO   |     | NULL                |                |
*	| net_sales               | float       | NO   |     | NULL                |                |
*	| tax                     | float       | NO   |     | NULL                |                |
*	| tip                     | float       | NO   |     | NULL                |                |
*	| partial_refunds         | float       | NO   |     | NULL                |                |
*	| total_collected         | float       | NO   |     | NULL                |                |
*	| source                  | varchar(16) | NO   |     | NULL                |                |
*	| card                    | float       | NO   |     | NULL                |                |
*	| card_entry_methods      | varchar(16) | NO   |     | NULL                |                |
*	| cash                    | float       | NO   |     | NULL                |                |
*	| square_gift_card        | float       | NO   |     | NULL                |                |
*	| other_tender            | float       | NO   |     | NULL                |                |
*	| other_tender_type       | varchar(16) | NO   |     | NULL                |                |
*	| other_tender_note       | varchar(32) | NO   |     | NULL                |                |
*	| fees                    | float       | NO   |     | NULL                |                |
*	| net_total               | float       | NO   |     | NULL                |                |
*	| transaction_id          | varchar(32) | NO   |     | NULL                |                |
*	| payment_id              | varchar(32) | NO   |     | NULL                |                |
*	| card_brand              | varchar(16) | NO   |     | NULL                |                |
*	| PAN_suffix              | int(11)     | NO   |     | NULL                |                |
*	| device_name             | varchar(32) | NO   |     | NULL                |                |
*	| staff_name              | varchar(16) | NO   |     | NULL                |                |
*	| staff_id                | varchar(16) | NO   |     | NULL                |                |
*	| description             | varchar(64) | NO   |     | NULL                |                |
*	| details                 | varchar(64) | NO   |     | NULL                |                |
*	| event_type              | varchar(32) | NO   |     | NULL                |                |
*	| location                | varchar(32) | NO   |     | NULL                |                |
*	| Dining_option           | varchar(16) | NO   |     | NULL                |                |
*	| Customer_id             | int(11)     | NO   |     | NULL                |                |
*	| customer_name           | varchar(64) | NO   |     | NULL                |                |
*	| customer_reference_id   | varchar(16) | NO   |     | NULL                |                |
*	| device_nickname         | varchar(16) | NO   |     | NULL                |                |
*	| third_party_fees        | float       | NO   |     | NULL                |                |
*	| deposit_id              | varchar(32) | NO   |     | NULL                |                |
*	| deposit_date            | date        | NO   |     | NULL                |                |
*	| deposit_details         | varchar(64) | NO   |     | NULL                |                |
*	| fee_percentage_rate     | float       | NO   |     | NULL                |                |
*	| fee_fixed_rate          | float       | NO   |     | NULL                |                |
*	| refund_reason           | varchar(64) | NO   |     | NULL                |                |
*	| discount_name           | varchar(16) | NO   |     | NULL                |                |
*	| transaction_status      | varchar(16) | NO   |     | NULL                |                |
*	| order_reference_id      | varchar(16) | NO   |     | NULL                |                |
*	| fulfillment_note        | varchar(32) | NO   |     | NULL                |                |
*	| free_processing_applied | float       | NO   |     | NULL                |                |
*	| last_updated            | timestamp   | NO   |     | current_timestamp() |                |
*	+-------------------------+-------------+------+-----+---------------------+----------------+
*
**********************************************************************************************************/
class ro_square_trans_csv_model extends generic_fa_interface_model
{
		protected $Date;
		protected $Time;
		protected $Timezone;
		protected $gross_sales;
		protected $discounts;
		protected $service_charges;
		protected $net_sales;
		protected $gift_card_sales;
		protected $tax;
		protected $tip;
		protected $partial_refunds;
		protected $total_collected;
		protected $source;
		protected $card;
		protected $card_entry_methods;
		protected $cash;
		protected $square_gift_card;
		protected $other_tender;
		protected $other_tender_type;
		protected $other_tender_note;
		protected $fees;
		protected $net_total;
		protected $transaction_id;
		protected $payment_id;
		protected $card_brand;
		protected $PAN_suffix;
		protected $device_name;
		protected $staff_name;
		protected $staff_id;
		protected $details;
		protected $description;
		protected $event_type;
		protected $location;
		protected $Dining_option;
		protected $Customer_id;
		protected $customer_name;
		protected $customer_reference_id;
		protected $device_nickname;
		protected $third_party_fees;
		protected $deposit_id;
		protected $deposit_date;
		protected $deposit_details;
		protected $fee_percentage_rate;
		protected $fee_fixed_rate;
		protected $refund_reason;
		protected $discount_name;
		protected $transaction_status;
		protected $order_reference_id;
		protected $fulfillment_note;
		protected $free_processing_applied;
		protected $channel;
		protected $unattributed_tips;
		protected $arr2obj_ran;		//!<bool have we set our fields?

	function __construct()
	{
		display_notification( __FILE__ . "::" . __LINE__ . "::" );
		parent::__construct( null, null, null, null, null);

		$this->matched = 0;
		$this->created = 0;
		$this->iam = "ksf_import_square_transactions";
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
		$ind = "id";
		//$ind = "id_" . $this->iam;
		$this->fields_array[] = array('name' => $ind, 'type' => 'int(11)', 'auto_increment' => 'yes', 'readwrite' => 'read' );
		$this->fields_array[] = array('name' => 'last_updated', 'type' => 'timestamp', 'null' => 'NOT NULL', 'default' => 'CURRENT_TIMESTAMP', 'readwrite' => 'read' );
		$this->table_details['tablename'] = $this->company_prefix . $this->iam;
		$this->set( 'tablename', $this->company_prefix . $this->iam );	//This should set table_interface too
		$this->table_details['primarykey'] = $ind;
		$this->table_details['orderby'] = 'transaction_date, transaction_id';
		$this->table_details['index'][0]['type'] = 'unique';
		$this->table_details['index'][0]['columns'] = "transaction_id";
		$this->table_details['index'][0]['keyname'] = "trans_id";
		$this->table_details['index'][1]['type'] = 'unique';
		$this->table_details['index'][1]['columns'] = "payment_id";
		$this->table_details['index'][1]['keyname'] = "pay_id";
		$this->table_details['index'][2]['type'] = 'unique';
		$this->table_details['index'][2]['columns'] = "deposit_id";
		$this->table_details['index'][2]['keyname'] = "deposit_id";


		//$this->fields_array[] = array('name' => 'stock_id', 'label' => 'SKU', 'type' => 'varchar(256)', 'null' => 'NOT NULL',  'readwrite' => 'readwrite');
		//$sidl = 'varchar(' . STOCK_ID_LENGTH . ')';
		//$descl = 'varchar(' . DESCRIPTION_LENGTH . ')';

		//$this->fields_array[] = array('name' => 'inserted_fa', 'label' => 'Inserted into FA', 'type' => 'bool', 'null' => 'NOT NULL',  'readwrite' => 'readwrite', 'default' => '0' );
		$this->fields_array[] = array('name' => 'Date', 'type' => 'date', 'null' => 'NOT NULL', 'default' => '', 'readwrite' => 'readwrite');
		$this->fields_array[] = array('name' => 'Time', 'type' => 'varchar(8)', 'null' => 'NOT NULL', 'default' => '', 'readwrite' => 'readwrite');
		$this->fields_array[] = array('name' => 'Timezone', 'type' => 'varchar(64)', 'null' => 'NOT NULL', 'default' => '', 'readwrite' => 'readwrite');
		$this->fields_array[] = array('name' => 'gross_sales', 'type' => 'float', 'null' => 'NOT NULL', 'default' => '', 'readwrite' => 'readwrite');
		$this->fields_array[] = array('name' => 'discounts', 'type' => 'float', 'null' => 'NOT NULL', 'default' => '', 'readwrite' => 'readwrite');
		$this->fields_array[] = array('name' => 'service_charges', 'type' => 'float', 'null' => 'NOT NULL', 'default' => '', 'readwrite' => 'readwrite');
		$this->fields_array[] = array('name' => 'gift_card_sales', 'type' => 'float', 'null' => 'NOT NULL', 'default' => '', 'readwrite' => 'readwrite');
		$this->fields_array[] = array('name' => 'net_sales', 'type' => 'float', 'null' => 'NOT NULL', 'default' => '', 'readwrite' => 'readwrite');
		$this->fields_array[] = array('name' => 'tax', 'type' => 'float', 'null' => 'NOT NULL', 'default' => '', 'readwrite' => 'readwrite');
		$this->fields_array[] = array('name' => 'tip', 'type' => 'float', 'null' => 'NOT NULL', 'default' => '', 'readwrite' => 'readwrite');
		$this->fields_array[] = array('name' => 'partial_refunds', 'type' => 'float', 'null' => 'NOT NULL', 'default' => '', 'readwrite' => 'readwrite');
		$this->fields_array[] = array('name' => 'total_collected', 'type' => 'float', 'null' => 'NOT NULL', 'default' => '', 'readwrite' => 'readwrite');
		$this->fields_array[] = array('name' => 'source', 'type' => 'varchar(16)', 'null' => 'NOT NULL', 'default' => '', 'readwrite' => 'readwrite');
		$this->fields_array[] = array('name' => 'card', 'type' => 'float', 'null' => 'NOT NULL', 'default' => '', 'readwrite' => 'readwrite');
		$this->fields_array[] = array('name' => 'card_entry_methods', 'type' => 'varchar(16)', 'null' => 'NOT NULL', 'default' => '', 'readwrite' => 'readwrite');
		$this->fields_array[] = array('name' => 'cash', 'type' => 'float', 'null' => 'NOT NULL', 'default' => '', 'readwrite' => 'readwrite');
		$this->fields_array[] = array('name' => 'square_gift_card', 'type' => 'float', 'null' => 'NOT NULL', 'default' => '', 'readwrite' => 'readwrite');
		$this->fields_array[] = array('name' => 'other_tender', 'type' => 'float', 'null' => 'NOT NULL', 'default' => '', 'readwrite' => 'readwrite');
		$this->fields_array[] = array('name' => 'other_tender_type', 'type' => 'varchar(16)', 'null' => 'NOT NULL', 'default' => '', 'readwrite' => 'readwrite');
		$this->fields_array[] = array('name' => 'other_tender_note', 'type' => 'varchar(32)', 'null' => 'NOT NULL', 'default' => '', 'readwrite' => 'readwrite');
		$this->fields_array[] = array('name' => 'fees', 'type' => 'float', 'null' => 'NOT NULL', 'default' => '', 'readwrite' => 'readwrite');
		$this->fields_array[] = array('name' => 'net_total', 'type' => 'float', 'null' => 'NOT NULL', 'default' => '', 'readwrite' => 'readwrite');
		$this->fields_array[] = array('name' => 'transaction_id', 'type' => 'varchar(32)', 'null' => 'NOT NULL', 'default' => '', 'readwrite' => 'readwrite');
		$this->fields_array[] = array('name' => 'payment_id', 'type' => 'varchar(32)', 'null' => 'NOT NULL', 'default' => '', 'readwrite' => 'readwrite');
		$this->fields_array[] = array('name' => 'card_brand', 'type' => 'varchar(16)', 'null' => 'NOT NULL', 'default' => '', 'readwrite' => 'readwrite');
		$this->fields_array[] = array('name' => 'PAN_suffix', 'type' => 'int(11)', 'null' => 'NOT NULL', 'default' => '', 'readwrite' => 'readwrite');
		$this->fields_array[] = array('name' => 'device_name', 'type' => 'varchar(32)', 'null' => 'NOT NULL', 'default' => '', 'readwrite' => 'readwrite');
		$this->fields_array[] = array('name' => 'staff_name', 'type' => 'varchar(16)', 'null' => 'NOT NULL', 'default' => '', 'readwrite' => 'readwrite');
		$this->fields_array[] = array('name' => 'staff_id', 'type' => 'varchar(16)', 'null' => 'NOT NULL', 'default' => '', 'readwrite' => 'readwrite');
		$this->fields_array[] = array('name' => 'description', 'type' => 'varchar(64)', 'null' => 'NOT NULL', 'default' => '', 'readwrite' => 'readwrite');
		$this->fields_array[] = array('name' => 'details', 'type' => 'varchar(64)', 'null' => 'NOT NULL', 'default' => '', 'readwrite' => 'readwrite');
		$this->fields_array[] = array('name' => 'event_type', 'type' => 'varchar(32)', 'null' => 'NOT NULL', 'default' => '', 'readwrite' => 'readwrite');
		$this->fields_array[] = array('name' => 'location', 'type' => 'varchar(32)', 'null' => 'NOT NULL', 'default' => '', 'readwrite' => 'readwrite');
		$this->fields_array[] = array('name' => 'Dining_option', 'type' => 'varchar(16)', 'null' => 'NOT NULL', 'default' => '', 'readwrite' => 'readwrite');
		$this->fields_array[] = array('name' => 'Customer_id', 'type' => 'int(11)', 'null' => 'NOT NULL', 'default' => '', 'readwrite' => 'readwrite');
		$this->fields_array[] = array('name' => 'customer_name', 'type' => 'varchar(64)', 'null' => 'NOT NULL', 'default' => '', 'readwrite' => 'readwrite');
		$this->fields_array[] = array('name' => 'customer_reference_id', 'type' => 'varchar(16)', 'null' => 'NOT NULL', 'default' => '', 'readwrite' => 'readwrite');
		$this->fields_array[] = array('name' => 'device_nickname', 'type' => 'varchar(16)', 'null' => 'NOT NULL', 'default' => '', 'readwrite' => 'readwrite');
		$this->fields_array[] = array('name' => 'third_party_fees', 'type' => 'float', 'null' => 'NOT NULL', 'default' => '', 'readwrite' => 'readwrite');
		$this->fields_array[] = array('name' => 'deposit_id', 'type' => 'varchar(32)', 'null' => 'NOT NULL', 'default' => '', 'readwrite' => 'readwrite');
		$this->fields_array[] = array('name' => 'deposit_date', 'type' => 'date', 'null' => 'NOT NULL', 'default' => '', 'readwrite' => 'readwrite');
		$this->fields_array[] = array('name' => 'deposit_details', 'type' => 'varchar(64)', 'null' => 'NOT NULL', 'default' => '', 'readwrite' => 'readwrite');
		$this->fields_array[] = array('name' => 'fee_percentage_rate', 'type' => 'float', 'null' => 'NOT NULL', 'default' => '', 'readwrite' => 'readwrite');
		$this->fields_array[] = array('name' => 'fee_fixed_rate', 'type' => 'float', 'null' => 'NOT NULL', 'default' => '', 'readwrite' => 'readwrite');
		$this->fields_array[] = array('name' => 'refund_reason', 'type' => 'varchar(64)', 'null' => 'NOT NULL', 'default' => '', 'readwrite' => 'readwrite');
		$this->fields_array[] = array('name' => 'discount_name', 'type' => 'varchar(16)', 'null' => 'NOT NULL', 'default' => '', 'readwrite' => 'readwrite');
		$this->fields_array[] = array('name' => 'transaction_status', 'type' => 'varchar(16)', 'null' => 'NOT NULL', 'default' => '', 'readwrite' => 'readwrite');
		$this->fields_array[] = array('name' => 'order_reference_id', 'type' => 'varchar(16)', 'null' => 'NOT NULL', 'default' => '', 'readwrite' => 'readwrite');
		$this->fields_array[] = array('name' => 'fulfillment_note', 'type' => 'varchar(32)', 'null' => 'NOT NULL', 'default' => '', 'readwrite' => 'readwrite');
		$this->fields_array[] = array('name' => 'free_processing_applied', 'type' => 'float', 'null' => 'NOT NULL', 'default' => '', 'readwrite' => 'readwrite');
		
		$this->table_interface->set( "table_details", $this->table_details );
		$this->table_interface->set( "fields_array", $this->fields_array );

	}
	/**//***************************************************************************
	* Take an instance of this object, and insert its values into the table.
	*
	* @param none uses internal
	* @returns none
	********************************************************************************/
	function insert_transaction()
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
}
