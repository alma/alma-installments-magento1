<?php
$installer = $this;
$installer->startSetup();
$pathToDelete = [
    'payment/alma_installments/p2x_enabled',
    'payment/alma_installments/p2x_min_amount',
    'payment/alma_installments/p2x_max_amount',
    'payment/alma_installments/p3x_enabled',
    'payment/alma_installments/p3x_min_amount',
    'payment/alma_installments/p3x_max_amount',
    'payment/alma_installments/p4x_enabled',
    'payment/alma_installments/p4x_min_amount',
    'payment/alma_installments/p4x_max_amount',
    'payment/alma_installments/eligibility_message',
    'payment/alma_installments/non_eligibility_message',
    'payment/alma_installments/show_eligibility_message',
];
$table = $installer->getConnection()->getTableName("core_config_data");
foreach ($pathToDelete as $path) {
$installer->getConnection()->delete($table,array('path = ?' => $path));
}
$installer->endSetup();
