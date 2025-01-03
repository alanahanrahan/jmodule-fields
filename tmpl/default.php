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

// Get module parameters
$allowedCategories = $params->get('category_id', array());
$selectedFields = $params->get('custom_fields', array());
$wrapperClass = $params->get('wrapper_class', 'custom-fields-wrapper');
$showLabels = $params->get('show_labels', 1);

// Ensure we have selected fields
if (empty($selectedFields)) {
    return;
}

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

// If we have an article ID, proceed
if ($currentArticleId) {
    $db = Factory::getDbo();
    
    // Check if article is in allowed category
    $query = $db->getQuery(true)
        ->select('catid')
        ->from('#__content')
        ->where('id = ' . (int)$currentArticleId);
    $db->setQuery($query);
    $articleCategory = $db->loadResult();
    
    // Proceed if no category filter or article is in allowed category
    if ($articleCategory && (empty($allowedCategories) || in_array($articleCategory, $allowedCategories))) {
        // Get custom fields for this article
        $query = $db->getQuery(true)
            ->select([
                'f.id',
                'f.name',
                'f.type',
                'f.label',
                'f.params',
                'v.value'
            ])
            ->from('#__fields_values AS v')
            ->join('LEFT', '#__fields AS f ON f.id = v.field_id')
            ->where([
                'v.item_id = ' . (int)$currentArticleId,
                'f.context = ' . $db->quote('com_content.article'),
                'f.id IN (' . implode(',', array_map('intval', $selectedFields)) . ')'
            ])
            ->order('FIELD(f.id, ' . implode(',', array_map('intval', $selectedFields)) . ')');
        
        $db->setQuery($query);
        $fields = $db->loadObjectList();
        
        if (!empty($fields)) {
            $output = array();
            
            foreach ($fields as $field) {
                // Skip empty values unless specifically configured to show them
                if (empty($field->value) && $field->type !== 'checkbox') {
                    continue;
                }
                
                $value = $field->value;
                $fieldParams = json_decode($field->params);
                
                // Process value based on field type
                switch ($field->type) {
                    case 'calendar':
                        $dateFormat = isset($fieldParams->format) ? $fieldParams->format : 'DATE_FORMAT_LC3';
                        $value = HTMLHelper::_('date', $value, $dateFormat);
                        break;
                        
                    case 'media':
                        $value = '<img src="' . htmlspecialchars($value) . '" alt="" class="field-media-image" />';
                        break;
                        
                    case 'checkboxes':
                    case 'list':
                        $values = is_array($value) ? $value : explode(',', $value);
                        $value = implode(', ', array_map('htmlspecialchars', $values));
                        break;
                        
                    case 'checkbox':
                        $value = $value ? JText::_('JYES') : JText::_('JNO');
                        break;
                        
                    case 'url':
                        $value = '<a href="' . htmlspecialchars($value) . '" target="_blank">' . 
                                htmlspecialchars($value) . '</a>';
                        break;
                        
                    default:
                        $value = htmlspecialchars($value);
                }
                
                // Build field HTML
                $fieldHtml = '<div class="field-wrapper field-' . $field->id . ' field-type-' . 
                            $field->type . '">';
                
                if ($showLabels) {
                    $fieldHtml .= '<span class="field-label">' . 
                                 htmlspecialchars($field->label) . ': </span>';
                }
                
                $fieldHtml .= '<span class="field-value">' . $value . '</span>';
                $fieldHtml .= '</div>';
                
                $output[] = $fieldHtml;
            }
            
            // Output all fields if we have any
            if (!empty($output)) {
                echo '<div class="' . htmlspecialchars($wrapperClass) . '">';
                echo implode("\n", $output);
                echo '</div>';
            }
        }
    }
}
