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
    $headers = $item->headers ? json_decode($item->headers, true) : [];
    $queryParams = $item->query_params ? json_decode($item->query_params, true) : [];
    $body = $item->body ? json_decode($item->body, true) : [];
    $notes = $item->notes ? json_decode($item->notes, true) : [];

    $hasRequestData = ! empty($headers) || ! empty($queryParams) || ! empty($body) || ! empty($notes);
    $hasResponses = ! empty($item->success_response) || ! empty($item->error_response);
    $searchText = strtolower(implode(' ', array_filter([
        $section,
        $module,
        $group,
        $item->name,
        $item->method,
        $item->route,
        $item->short_description,
    ])));
?>

<article
    id="id-<?php echo e($item->id); ?>"
    class="endpoint-card"
    data-search="<?php echo e($searchText); ?>"
    data-method="<?php echo e(strtolower($item->method)); ?>"
>
    <header class="endpoint-header">
        <div class="endpoint-line">
            <span class="method method-<?php echo e(strtolower($item->method)); ?>">
                <?php echo e(strtoupper($item->method)); ?>

            </span>
            <code class="endpoint-path"><?php echo e($item->route); ?></code>
        </div>

        <h4 class="endpoint-title"><?php echo e($item->name); ?></h4>

        <?php if($item->short_description): ?>
            <p class="endpoint-summary"><?php echo e($item->short_description); ?></p>
        <?php endif; ?>
    </header>

    <?php if($item->description): ?>
        <div class="endpoint-description">
            <?php echo nl2br(e($item->description)); ?>

        </div>
    <?php endif; ?>

    <div class="endpoint-body">
        <div class="endpoint-request">
            <?php if($hasRequestData): ?>
                <?php if (isset($component)) { $__componentOriginal300c573f0df53087578a435f9a309fbc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal300c573f0df53087578a435f9a309fbc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.docs.param-table','data' => ['type' => 'headers','title' => 'Headers','data' => $item->headers]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('docs.param-table'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'headers','title' => 'Headers','data' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($item->headers)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal300c573f0df53087578a435f9a309fbc)): ?>
<?php $attributes = $__attributesOriginal300c573f0df53087578a435f9a309fbc; ?>
<?php unset($__attributesOriginal300c573f0df53087578a435f9a309fbc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal300c573f0df53087578a435f9a309fbc)): ?>
<?php $component = $__componentOriginal300c573f0df53087578a435f9a309fbc; ?>
<?php unset($__componentOriginal300c573f0df53087578a435f9a309fbc); ?>
<?php endif; ?>
                <?php if (isset($component)) { $__componentOriginal300c573f0df53087578a435f9a309fbc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal300c573f0df53087578a435f9a309fbc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.docs.param-table','data' => ['type' => 'query','title' => 'Query parameters','data' => $item->query_params]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('docs.param-table'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'query','title' => 'Query parameters','data' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($item->query_params)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal300c573f0df53087578a435f9a309fbc)): ?>
<?php $attributes = $__attributesOriginal300c573f0df53087578a435f9a309fbc; ?>
<?php unset($__attributesOriginal300c573f0df53087578a435f9a309fbc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal300c573f0df53087578a435f9a309fbc)): ?>
<?php $component = $__componentOriginal300c573f0df53087578a435f9a309fbc; ?>
<?php unset($__componentOriginal300c573f0df53087578a435f9a309fbc); ?>
<?php endif; ?>
                <?php if (isset($component)) { $__componentOriginal300c573f0df53087578a435f9a309fbc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal300c573f0df53087578a435f9a309fbc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.docs.param-table','data' => ['type' => 'body','title' => 'Request body','data' => $item->body]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('docs.param-table'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'body','title' => 'Request body','data' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($item->body)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal300c573f0df53087578a435f9a309fbc)): ?>
<?php $attributes = $__attributesOriginal300c573f0df53087578a435f9a309fbc; ?>
<?php unset($__attributesOriginal300c573f0df53087578a435f9a309fbc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal300c573f0df53087578a435f9a309fbc)): ?>
<?php $component = $__componentOriginal300c573f0df53087578a435f9a309fbc; ?>
<?php unset($__componentOriginal300c573f0df53087578a435f9a309fbc); ?>
<?php endif; ?>
                <?php if (isset($component)) { $__componentOriginal300c573f0df53087578a435f9a309fbc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal300c573f0df53087578a435f9a309fbc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.docs.param-table','data' => ['type' => 'notes','title' => 'Validation rules','data' => $item->notes]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('docs.param-table'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'notes','title' => 'Validation rules','data' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($item->notes)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal300c573f0df53087578a435f9a309fbc)): ?>
<?php $attributes = $__attributesOriginal300c573f0df53087578a435f9a309fbc; ?>
<?php unset($__attributesOriginal300c573f0df53087578a435f9a309fbc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal300c573f0df53087578a435f9a309fbc)): ?>
<?php $component = $__componentOriginal300c573f0df53087578a435f9a309fbc; ?>
<?php unset($__componentOriginal300c573f0df53087578a435f9a309fbc); ?>
<?php endif; ?>
            <?php else: ?>
                <p class="empty-state">No request parameters.</p>
            <?php endif; ?>
        </div>

        <div class="endpoint-responses">
            <?php if($hasResponses): ?>
                <?php if (isset($component)) { $__componentOriginal19e13c0d58dd7e91fb0582a388698f6d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal19e13c0d58dd7e91fb0582a388698f6d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.docs.code-box','data' => ['title' => 'Success response','content' => $item->success_response]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('docs.code-box'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Success response','content' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($item->success_response)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal19e13c0d58dd7e91fb0582a388698f6d)): ?>
<?php $attributes = $__attributesOriginal19e13c0d58dd7e91fb0582a388698f6d; ?>
<?php unset($__attributesOriginal19e13c0d58dd7e91fb0582a388698f6d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal19e13c0d58dd7e91fb0582a388698f6d)): ?>
<?php $component = $__componentOriginal19e13c0d58dd7e91fb0582a388698f6d; ?>
<?php unset($__componentOriginal19e13c0d58dd7e91fb0582a388698f6d); ?>
<?php endif; ?>

                <?php if (isset($component)) { $__componentOriginal19e13c0d58dd7e91fb0582a388698f6d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal19e13c0d58dd7e91fb0582a388698f6d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.docs.code-box','data' => ['title' => 'Error response','content' => $item->error_response]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('docs.code-box'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Error response','content' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($item->error_response)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal19e13c0d58dd7e91fb0582a388698f6d)): ?>
<?php $attributes = $__attributesOriginal19e13c0d58dd7e91fb0582a388698f6d; ?>
<?php unset($__attributesOriginal19e13c0d58dd7e91fb0582a388698f6d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal19e13c0d58dd7e91fb0582a388698f6d)): ?>
<?php $component = $__componentOriginal19e13c0d58dd7e91fb0582a388698f6d; ?>
<?php unset($__componentOriginal19e13c0d58dd7e91fb0582a388698f6d); ?>
<?php endif; ?>
            <?php else: ?>
                <p class="empty-state">No response example.</p>
            <?php endif; ?>
        </div>
    </div>
</article>
<?php /**PATH /home/masarat/Desktop/TheaterLink/resources/views/components/docs/endpoint.blade.php ENDPATH**/ ?>