<?php

$installer = $this;
$installer->startSetup();
$configData = Mage::getModel('core/config_data')->load('foomanspeedster/settings/enabled', 'path');
$configData->setPath('foomanspeedster/settings/enabled')
    ->setValue(1)
    ->save();
$installer->endSetup();
