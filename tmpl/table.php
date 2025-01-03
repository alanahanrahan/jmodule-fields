<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;

HTMLHelper::_('bootstrap.framework');

if (!empty($fields)) : ?>
<div class="<?php echo htmlspecialchars($params->get('wrapper_class', 'custom-fields-wrapper')); ?>">
    <div class="table-responsive">
        <table class="table table-striped table-bordered table-hover">
            <?php if ($params->get('show_labels', 1)) : ?>
            <thead class="thead-light">
                <tr>
                    <th scope="col">Field</th>
                    <th scope="col">Value</th>
                </tr>
            </thead>
            <?php endif; ?>
            <tbody>
                <?php foreach ($fields as $field) :
                    if (empty($field->value) && $field->type !== 'checkbox') continue;
                    $value = ModFieldsHelper::formatFieldValue($field);
                ?>
                    <tr class="field-type-<?php echo $field->type; ?> field-<?php echo $field->id; ?>">
                        <?php if ($params->get('show_labels', 1)) : ?>
                            <td class="field-label">
                                <strong><?php echo htmlspecialchars($field->label); ?></strong>
                            </td>
                        <?php endif; ?>
                        <td class="field-value">
                            <?php echo $value; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.table {
    margin-bottom: 0;
}
.table td {
    vertical-align: middle;
}
.field-label {
    width: 30%;
    background-color: #f8f9fa;
}
.field-value {
    width: 70%;
}
.field-value img {
    max-width: 200px;
    height: auto;
    margin: 0.5rem 0;
}
.field-type-url a {
    color: #007bff;
    text-decoration: none;
}
.field-type-url a:hover {
    text-decoration: underline;
}
@media (max-width: 576px) {
    .field-label,
    .field-value {
        width: auto;
    }
    .field-value img {
        max-width: 100%;
    }
}
</style>
<?php endif; ?>
