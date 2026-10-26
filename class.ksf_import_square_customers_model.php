<?php

require_once( 'class.ksf_import_external_customers_model.php' );

/**//*******************************************************************************
* Class for matching SQUARE customer data to FA data
*
*       +----------------------------------+-------------+------+-----+---------------------+-------------------------------+
*       | Field                            | Type        | Null | Key | Default             | Extra                         |
*       +----------------------------------+-------------+------+-----+---------------------+-------------------------------+
*       | ksf_import_external_customers_id | int(11)     | NO   | MUL | NULL                | auto_increment                |
*       | last_updated                     | datetime    | NO   |     | current_timestamp() | on update current_timestamp() |
*       | external_customer_type           | varchar(32) | NO   |     | NULL                |                               |
*       | external_customer_id             | varchar(64) | YES  |     | NULL                |                               |
*       | external_customer_name           | varchar(64) | YES  |     | NULL                |                               |
*       | FA_account_id                    | int(11)     | NO   |     | NULL                |                               |
*       | FA_person_id                     | int(11)     | NO   |     | NULL                |                               |
*       | ksf_import_other_table_name      | varchar(64) | NO   |     | NULL                |                               |
*       | ksf_import_other_table_id        | int(11)     | NO   |     | NULL                |                               |
*       +----------------------------------+-------------+------+-----+---------------------+-------------------------------+

************************************************************************************/
class ksf_import_square_customers_model extends ksf_import_external_customers_model
{
	/**//*************************************
	* Constructor with OUR specific values
	*
	* @param none
	* @returns none
	******************************************/
	function __construct()
	{
		parent::__construct();
		$this->set( 'external_customer_type', "SquareUP" );
		$this->set( 'ksf_import_other_table_name', "ksf_import_square_transactions" );
	}
}
