<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;

HTMLHelper::_('bootstrap.framework');

if (!empty($fields)) : ?>
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="card-title mb-0">Details</h5>
        </div>
        <ul class="list-group list-group-flush">
            <?php foreach ($fields as $field) :
                if (empty($field->value) && $field->type !== 'checkbox') continue;
                $value = ModFieldsHelper::formatFieldValue($field);
            ?>
                <li class="list-group-item d-flex justify-content-between align-items-center field-type-<?php echo $field->type; ?> field-<?php echo $field->id; ?>">
                    <?php if ($params->get('show_labels', 1)) : ?>
                        <strong class="field-label"><?php echo htmlspecialchars($field->label); ?>:</strong>
                    <?php endif; ?>
                    <span class="field-value"><?php echo $value; ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

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
<?php endif; ?>
