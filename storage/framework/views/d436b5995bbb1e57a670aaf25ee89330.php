<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['apiSections']));

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

foreach (array_filter((['apiSections']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $methodOrder = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];
    $methods = collect($apiSections)
        ->flatMap(fn ($modules) => collect($modules)->flatMap(
            fn ($module) => collect($module['groups'])->flatMap(fn ($group) => $group['items'])
        ))
        ->pluck('method')
        ->map(fn ($method) => strtoupper($method))
        ->unique()
        ->sortBy(fn ($method) => array_search($method, $methodOrder, true) !== false ? array_search($method, $methodOrder, true) : 99)
        ->values();
?>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="#introduction" class="brand">
            <span class="brand-mark">T</span>
            <span>
                <strong><?php echo e(config('app.name')); ?></strong>
                <small>API documentation</small>
            </span>
        </a>

        <button
            class="icon-button sidebar-close"
            id="sidebarClose"
            type="button"
            aria-label="Close navigation"
        >
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M6 6l12 12M18 6L6 18" />
            </svg>
        </button>
    </div>

    <div class="search-box">
        <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z" />
        </svg>
        <input
            type="search"
            id="apiSearch"
            placeholder="Search endpoints..."
            autocomplete="off"
            aria-label="Search endpoints"
        >
    </div>

    <div class="method-filters" role="group" aria-label="Filter by method">
        <button class="method-filter method-filter-all active" type="button" data-method-filter="all" aria-pressed="true">All</button>

        <?php $__currentLoopData = $methods; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $method): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <button
                class="method-filter method-filter-<?php echo e(strtolower($method)); ?>"
                type="button"
                data-method-filter="<?php echo e(strtolower($method)); ?>"
                aria-pressed="false"
            >
                <?php echo e($method); ?>

            </button>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    <nav class="sidebar-nav" aria-label="API navigation">
        <div class="nav-group">
            <p class="nav-label">Getting started</p>
            <a href="#introduction" class="nav-link" data-search="introduction getting started">
                Introduction
            </a>
            <a href="#common-errors" class="nav-link" data-search="common errors responses">
                Common errors
            </a>
        </div>

        <?php $__currentLoopData = $apiSections; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sectionName => $modules): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <details class="nav-section">
                <summary>
                    <span><?php echo e($sectionName); ?></span>
                    <span class="nav-count"><?php echo e(collect($modules)->sum('total')); ?></span>
                </summary>

                <div class="nav-section-body">
                <?php $__currentLoopData = $modules; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $moduleName => $module): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <details class="nav-module">
                        <summary>
                            <span><?php echo e($moduleName); ?></span>
                            <span class="nav-count"><?php echo e($module['total']); ?></span>
                        </summary>

                        <div class="nav-links">
                            <?php $__currentLoopData = $module['groups']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $group): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php if($group['name']): ?>
                                    <details class="nav-submodule">
                                        <summary>
                                            <span><?php echo e($group['name']); ?></span>
                                            <span class="nav-count"><?php echo e(count($group['items'])); ?></span>
                                        </summary>

                                        <div class="nav-links nav-sub-links">
                                            <?php $__currentLoopData = $group['items']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <?php if (isset($component)) { $__componentOriginal7f9790a402a07b714954392d2f2ed472 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7f9790a402a07b714954392d2f2ed472 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.docs.nav-endpoint','data' => ['item' => $item,'group' => $group['name'],'module' => $moduleName,'section' => $sectionName]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('docs.nav-endpoint'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['item' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($item),'group' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($group['name']),'module' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($moduleName),'section' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($sectionName)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal7f9790a402a07b714954392d2f2ed472)): ?>
<?php $attributes = $__attributesOriginal7f9790a402a07b714954392d2f2ed472; ?>
<?php unset($__attributesOriginal7f9790a402a07b714954392d2f2ed472); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal7f9790a402a07b714954392d2f2ed472)): ?>
<?php $component = $__componentOriginal7f9790a402a07b714954392d2f2ed472; ?>
<?php unset($__componentOriginal7f9790a402a07b714954392d2f2ed472); ?>
<?php endif; ?>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </div>
                                    </details>
                                <?php else: ?>
                                    <div class="nav-links nav-sub-links">
                                        <?php $__currentLoopData = $group['items']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <?php if (isset($component)) { $__componentOriginal7f9790a402a07b714954392d2f2ed472 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7f9790a402a07b714954392d2f2ed472 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.docs.nav-endpoint','data' => ['item' => $item,'group' => $group['name'],'module' => $moduleName,'section' => $sectionName]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('docs.nav-endpoint'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['item' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($item),'group' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($group['name']),'module' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($moduleName),'section' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($sectionName)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal7f9790a402a07b714954392d2f2ed472)): ?>
<?php $attributes = $__attributesOriginal7f9790a402a07b714954392d2f2ed472; ?>
<?php unset($__attributesOriginal7f9790a402a07b714954392d2f2ed472); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal7f9790a402a07b714954392d2f2ed472)): ?>
<?php $component = $__componentOriginal7f9790a402a07b714954392d2f2ed472; ?>
<?php unset($__componentOriginal7f9790a402a07b714954392d2f2ed472); ?>
<?php endif; ?>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </details>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </details>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </nav>

    <p class="sidebar-empty" id="sidebarEmpty" hidden>No matching endpoints.</p>

    <div
        class="sidebar-resizer"
        id="sidebarResizer"
        role="separator"
        aria-orientation="vertical"
        aria-label="Resize navigation"
        aria-valuemin="220"
        aria-valuemax="560"
        tabindex="0"
        title="Drag to resize"
    ></div>
</aside>
<?php /**PATH /home/masarat/Desktop/TheaterLink/resources/views/components/docs/sidebar.blade.php ENDPATH**/ ?>