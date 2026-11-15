<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['title', 'content', 'language' => 'json']));

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

foreach (array_filter((['title', 'content', 'language' => 'json']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $decoded = json_decode((string) $content, true);
    $isJson = json_last_error() === JSON_ERROR_NONE;
    $formatted = $isJson
        ? json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        : (string) $content;
?>

<?php if($content): ?>
    <div class="code-box">
        <div class="code-header">
            <span><?php echo e($title); ?></span>
            <button class="copy-button" type="button" data-copy>Copy</button>
        </div>

        <pre class="code-content"><code class="language-<?php echo e($isJson ? 'json' : $language); ?>"><?php echo e($formatted); ?></code></pre>
    </div>
<?php endif; ?>
<?php /**PATH /home/masarat/Desktop/TheaterLink/resources/views/components/docs/code-box.blade.php ENDPATH**/ ?>