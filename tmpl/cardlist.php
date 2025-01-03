<?php
// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;

// Load Bootstrap
HTMLHelper::_('bootstrap.framework');

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
            // Start the card container
            echo '<div class="card mb-4">';
            echo '<div class="card-header bg-primary text-white">';
            echo '<h5 class="card-title mb-0">Details</h5>';
            echo '</div>';
            echo '<ul class="list-group list-group-flush">';
            
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
                        $value = '<img src="' . htmlspecialchars($value) . '" alt="" class="img-fluid" />';
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
                        $value = '<a href="' . htmlspecialchars($value) . '" target="_blank" class="text-primary">' . 
                                htmlspecialchars($value) . '</a>';
                        break;
                        
                    default:
                        $value = htmlspecialchars($value);
                }
                
                // Output each field as a list group item
                echo '<li class="list-group-item d-flex justify-content-between align-items-center field-type-' . 
                     $field->type . ' field-' . $field->id . '">';
                
                if ($showLabels) {
                    echo '<strong class="field-label">' . htmlspecialchars($field->label) . ':</strong>';
                }
                
                echo '<span class="field-value">' . $value . '</span>';
                echo '</li>';
            }
            
            // Close the card
            echo '</ul>';
            echo '</div>';
        }
    }
}
?>

<style>
.field-value img {
    max-width: 200px;
    height: auto;
}
.list-group-item {
    background-color: #ffffff;
    border: 1px solid rgba(0,0,0,.125);
    padding: .75rem 1.25rem;
}
.field-label {
    color: #495057;
    margin-right: 1rem;
}
.card-header {
    padding: .75rem 1.25rem;
}
</style>