<?php
defined('_JEXEC') or die;

require_once __DIR__ . '/helper.php';

$fields = ModFieldsHelper::getFields($params);
$layout = $params->get('layout', 'default');

require JModuleHelper::getLayoutPath('mod_fields', $layout);
