<?php
// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;

// Get application
$app = Factory::getApplication();
$option = $app->input->get('option');
$view = $app->input->get('view');
$currentArticleId = null;

// Get allowed categories and custom fields from parameters
$allowedCategories = $params->get('category_id', array());
$customFields = $params->get('custom_fields', array());
$wrapperClass = $params->get('wrapper_class', 'custom-fields-wrapper');

// Check if we're in an article view
if ($option === 'com_content' && $view === 'article') {
    $currentArticleId = $app->input->getInt('id');
} else {
    // Try to get from active menu item
    $menu = $app->getMenu();
    $active = $menu->getActive();
    if ($active) {
        $currentArticleId = $active->getParams()->get('article_id');
    }
}

// If we have an article ID, get the custom fields
if ($currentArticleId && !empty($customFields)) {
    $db = Factory::getDbo();
    
    // First check if article is in allowed category
    $query = $db->getQuery(true)
        ->select('catid')
        ->from('#__content')
        ->where('id = ' . (int)$currentArticleId);
    $db->setQuery($query);
    $articleCategory = $db->loadResult();
    
    if ($articleCategory && (empty($allowedCategories) || in_array($articleCategory, $allowedCategories))) {
        // Get custom fields for this article
        $query = $db->getQuery(true)
            ->select('f.name, f.type, v.value')
            ->from('#__fields_values AS v')
            ->join('LEFT', '#__fields AS f ON f.id = v.field_id')
            ->where('v.item_id = ' . (int)$currentArticleId)
            ->where('f.context = ' . $db->quote('com_content.article'));
        $db->setQuery($query);
        $fields = $db->loadObjectList('name');
        
        if (!empty($fields)) {
            $output = array();
            
            // Process each requested field
            foreach ($customFields as $fieldConfig) {
                $fieldName = $fieldConfig->field_name;
                if (isset($fields[$fieldName])) {
                    $field = $fields[$fieldName];
                    $value = $field->value;
                    
                    // Process value based on field type
                    switch ($field->type) {
                        case 'calendar':
                            $value = HTMLHelper::_('date', $value, 'DATE_FORMAT_LC3');
                            break;
                        case 'media':
                            $value = '<img src="' . htmlspecialchars($value) . '" alt="" />';
                            break;
                        default:
                            $value = htmlspecialchars($value);
                    }
                    
                    // Add label if specified
                    if (!empty($fieldConfig->label)) {
                        $output[] = '<div class="field-wrapper">' .
                            '<span class="field-label">' . htmlspecialchars($fieldConfig->label) . ': </span>' .
                            '<span class="field-value">' . $value . '</span>' .
                            '</div>';
                    } else {
                        $output[] = '<div class="field-wrapper">' .
                            '<span class="field-value">' . $value . '</span>' .
                            '</div>';
                    }
                }
            }
            
            // Output all fields
            if (!empty($output)) {
                echo '<div class="' . htmlspecialchars($wrapperClass) . '">';
                echo implode("\n", $output);
                echo '</div>';
            }
        }
    }
}
?>
