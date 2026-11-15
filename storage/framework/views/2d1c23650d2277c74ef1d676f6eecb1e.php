<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['type', 'title', 'data']));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['type', 'title', 'data']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $rows = json_decode($data, true) ?: [];

    $valueType = static function ($value): string {
        return match (true) {
            is_bool($value) => 'boolean',
            is_int($value), is_float($value) => 'number',
            is_array($value) => 'array',
            is_null($value) => 'null',
            default => 'string',
        };
    };

    $displayValue = static function ($value): string {
        return match (true) {
            is_array($value) => (string) json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            is_bool($value) => $value ? 'true' : 'false',
            is_null($value) => 'null',
            default => (string) $value,
        };
    };
?>

<?php if(! empty($rows)): ?>
    <div class="param-block">
        <h5 class="param-title"><?php echo e($title); ?></h5>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Field</th>
                        <?php if($type !== 'notes'): ?>
                            <th>Type</th>
                        <?php endif; ?>
                        <th><?php echo e($type === 'notes' ? 'Rules' : 'Example'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php ($name = (string) ($type === 'notes' ? ($value['key'] ?? $key) : $key)); ?>
                        <tr>
                            <td>
                                <code class="param-name"><?php echo str_replace('_', '_<wbr>', e($name)); ?></code>
                            </td>

                            <?php if($type !== 'notes'): ?>
                                <td>
                                    <span class="type-label"><?php echo e($valueType($value)); ?></span>
                                </td>
                            <?php endif; ?>

                            <td class="param-example">
                                <?php echo e($type === 'notes' ? ($value['value'] ?? '') : $displayValue($value)); ?>

                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
<?php /**PATH /home/masarat/Desktop/TheaterLink/resources/views/components/docs/param-table.blade.php ENDPATH**/ ?>