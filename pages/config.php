<?php
$page_security = 'SA_ksf_FA_ImportStagingProcessing_UI';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");
add_access_extensions();

include_once($path_to_root . "/modules/ksf_modules_common/src/ModuleConfig.php");

use Ksfraser\ModulesCommon\ModuleConfig;

$help_context = 'ISU Configuration';

$config = new ModuleConfig(
    TB_PREF . 'ksf_isu_config',
    'ISU',
    [
        ['name' => 'default_customer',   'label' => 'Default Customer',                 'type' => 'customer_list'],
        ['name' => 'days_before',        'label' => 'Days Before (invoice matching)',   'type' => 'integer', 'default' => 2],
        ['name' => 'days_after',         'label' => 'Days After (invoice matching)',    'type' => 'integer', 'default' => 5],
        ['name' => 'rounding_amount',    'label' => 'Rounding Tolerance',               'type' => 'integer', 'default' => 1],
        ['name' => 'default_location',   'label' => 'Default Location',                 'type' => 'locations_list'],
        ['name' => 'default_dimension1', 'label' => 'Default Dimension 1',              'type' => 'dimensions_list'],
        ['name' => 'default_dimension2', 'label' => 'Default Dimension 2',              'type' => 'dimensions_list'],
    ]
);

$config
    ->addDDLRenderer('customer_list',    'customer_list_row')
    ->addDDLRenderer('locations_list',   'locations_list_row')
    ->addDDLRenderer('dimensions_list',  'dimensions_list_row');

$config->load();
$config->handlePost('update_config');

start_page($help_context);
$config->renderForm('update_config');
end_page();
