<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['item', 'group' => null, 'module' => null, 'section' => null]));

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

foreach (array_filter((['item', 'group' => null, 'module' => null, 'section' => null]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $searchText = strtolower(trim(implode(' ', array_filter([
        $section,
        $module,
        $group,
        $item->name,
        $item->method,
        $item->route,
    ]))));
?>

<a
    href="#id-<?php echo e($item->id); ?>"
    class="nav-link nav-endpoint"
    data-search="<?php echo e($searchText); ?>"
    data-method="<?php echo e(strtolower($item->method)); ?>"
>
    <span class="nav-method nav-method-<?php echo e(strtolower($item->method)); ?>">
        <?php echo e(strtoupper($item->method)); ?>

    </span>
    <span><?php echo e($item->name); ?></span>
</a>
<?php /**PATH /home/masarat/Desktop/TheaterLink/resources/views/components/docs/nav-endpoint.blade.php ENDPATH**/ ?>