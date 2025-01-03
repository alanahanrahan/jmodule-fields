<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;


class ModFieldsHelper
{
    public static function getFields($params)
    {
        $app = JFactory::getApplication();
        $option = $app->input->get('option');
        $view = $app->input->get('view');
        $currentArticleId = null;

        // Get parameters
        $allowedCategories = $params->get('category_id', array());
        $selectedFields = $params->get('custom_fields', array());

        if (empty($selectedFields)) {
            return array();
        }

        // Get current article ID
        if ($option === 'com_content' && $view === 'article') {
            $currentArticleId = $app->input->getInt('id');
        } else {
            $menu = $app->getMenu();
            $active = $menu->getActive();
            if ($active) {
                $currentArticleId = $active->getParams()->get('article_id');
            }
        }

        if (!$currentArticleId) {
            return array();
        }

        $db = JFactory::getDbo();
        
        // Check category
        $query = $db->getQuery(true)
            ->select('catid')
            ->from('#__content')
            ->where('id = ' . (int)$currentArticleId);
        $db->setQuery($query);
        $articleCategory = $db->loadResult();

        if (!$articleCategory || (!empty($allowedCategories) && !in_array($articleCategory, $allowedCategories))) {
            return array();
        }

        // Get fields
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
        return $db->loadObjectList();
    }

    public static function formatFieldValue($field)
    {
        $value = $field->value;
        $fieldParams = json_decode($field->params);

        switch ($field->type) {
            case 'calendar':
                $dateFormat = isset($fieldParams->format) ? $fieldParams->format : 'DATE_FORMAT_LC3';
                return HTMLHelper::_('date', $value, $dateFormat);
                
            case 'media':
                return '<img src="' . htmlspecialchars($value) . '" alt="" class="img-fluid field-media-image" />';
                
            case 'checkboxes':
            case 'list':
                $values = is_array($value) ? $value : explode(',', $value);
                return implode(', ', array_map('htmlspecialchars', $values));
                
            case 'checkbox':
                return $value ? JText::_('JYES') : JText::_('JNO');
                
            case 'url':
                return '<a href="' . htmlspecialchars($value) . '" target="_blank" class="text-primary">' . 
                       htmlspecialchars($value) . '</a>';
                
            default:
                return htmlspecialchars($value);
        }
    }
}