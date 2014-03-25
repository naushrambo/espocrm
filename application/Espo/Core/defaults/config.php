<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014  Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 ************************************************************************/ 

return array (
  'configPath' => 'data/config.php',
  'cachePath' => 'data/cache',

  'database' => 
  array (
    'driver' => 'pdo_mysql',
    'host' => 'localhost',    
  ),
  'useCache' => true,
  'recordsPerPage' => 20,
  'recordsPerPageSmall' => 5,
  'applicationName' => 'EspoCRM',
  'timeZone' => 'UTC',
  'dateFormat' => 'MM/DD/YYYY',
  'timeFormat' => 'HH:mm',
  'weekStart' => 0,
  'thousandSeparator' => ',',
  'decimalMark' => '.',
  'currencyList' => 
  array (
    0 => 'USD',
    1 => 'EUR',
  ),  
  'defaultCurrency' => 'USD',
  'outboundEmailIsShared' => true,
  'outboundEmailFromName' => 'EspoCRM',
  'outboundEmailFromAddress' => '',
  'smtpServer' => '',
  'smtpPort' => 25,
  'smtpAuth' => true,
  'smtpSecurity' => '',
  'smtpUsername' => '',
  'smtpPassword' => '',  
  'languageList' => array(
    'en_US',
  ),
  'language' => 'en_US',
  'logger' => 
  array (
    'path' => 'data/logs/espo.log',    
    'level' => 'ERROR', /*DEBUG, INFO, NOTICE, WARNING, ERROR, CRITICAL, ALERT, EMERGENCY*/
    'isRotate' => true, /*rotate every day every logs files*/
    'maxRotateFiles' => 30, /*max number of rotate files*/
  ),
  'defaultPermissions' =>
  array (
    'dir' => '0775',
    'file' => '0664',
    'user' => '',
    'group' => '',
  ),
  'cron' => array(
    'maxJobNumber' => 15, /*Max number of jobs per one execution*/    
    'jobPeriod' => 7800, /*Period for jobs, ex. if cron executed at 15:35, it will execute all pending jobs for times from 14:05 to 15:35*/
    'minExecutionTime' => 50, /*to avoid too frequency execution*/
  ),
  'currency' =>
  array(
    'base' => 'USD',
    'rate' => array(
      'EUR' => 1.37,
      'GBP' => 1.67,
    ),    
  ),
  'crud' => array(
    'get' => 'read',
    'post' => 'create',
    'put' => 'update',
    'patch' => 'patch',
    'delete' => 'delete',
  ),  
  'systemUser' => array(
    'id' => 'system',
    'userName' => 'system',
    'firstName' => '',
    'lastName' => 'System',    
  ),  
  'systemItems' =>
  array (
    'systemItems',
    'adminItems',
    'configPath',
    'cachePath',
    'database',
    'crud', 
  ),
  'adminItems' =>
  array (
    'defaultPermissions',
    'logger',
    'devMode',
  ), 
  'isInstalled' => false, 
);

?>