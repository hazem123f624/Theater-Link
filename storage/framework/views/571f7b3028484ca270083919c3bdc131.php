<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Reference | <?php echo e(config('app.name')); ?></title>

    <script>
        (function () {
            var savedTheme = null;

            try {
                savedTheme = window.localStorage.getItem('theaterlink-docs-theme');
            } catch (error) {
                savedTheme = null;
            }

            var theme = savedTheme || (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.dataset.theme = theme;
        })();
    </script>

    <link
        href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism.min.css"
        rel="stylesheet"
    />

    <link rel="stylesheet" href="<?php echo e(asset('css/docs.css')); ?>?v=<?php echo e(filemtime(public_path('css/docs.css'))); ?>">
</head>
<body>

<div class="layout">
    <?php if (isset($component)) { $__componentOriginal7e3319ae61b87c1cd98c64fc7caee933 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7e3319ae61b87c1cd98c64fc7caee933 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.docs.sidebar','data' => ['apiSections' => $apiSections]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('docs.sidebar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['apiSections' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($apiSections)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal7e3319ae61b87c1cd98c64fc7caee933)): ?>
<?php $attributes = $__attributesOriginal7e3319ae61b87c1cd98c64fc7caee933; ?>
<?php unset($__attributesOriginal7e3319ae61b87c1cd98c64fc7caee933); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal7e3319ae61b87c1cd98c64fc7caee933)): ?>
<?php $component = $__componentOriginal7e3319ae61b87c1cd98c64fc7caee933; ?>
<?php unset($__componentOriginal7e3319ae61b87c1cd98c64fc7caee933); ?>
<?php endif; ?>

    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <main class="main">
        <header class="topbar">
            <button
                class="icon-button menu-button"
                id="menuButton"
                type="button"
                aria-label="Open navigation"
                aria-controls="sidebar"
                aria-expanded="false"
            >
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>

            <div class="topbar-title">API Reference</div>

            <button
                class="icon-button theme-toggle"
                id="themeToggle"
                type="button"
                aria-label="Switch to night mode"
                aria-pressed="false"
            >
                <svg class="moon-icon" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z" />
                </svg>
                <svg class="sun-icon" viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="12" cy="12" r="4" />
                    <path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4" />
                </svg>
            </button>

            <div class="topbar-meta">
                <span><?php echo e($totalEndpoints); ?> endpoints</span>
                <span class="meta-divider"></span>
                <span><?php echo e(config('api.version')); ?></span>
            </div>
        </header>

        <div class="content" id="mainContent">
            <section id="introduction" class="hero">
                <span class="eyebrow">Getting started</span>
                <h1><?php echo e(config('app.name')); ?> API</h1>

                <p class="hero-lead">
                    TheaterLink is a multi-tenant theater management and booking platform that
                    connects theaters, theater groups, and individual audience members. It covers
                    workspace onboarding, hall scheduling, group reservations, event publishing,
                    ticket tiers, and individual ticket booking.
                </p>

                <div class="hero-grid">
                    <div class="info-card">
                        <h2>Base URL</h2>
                        <code class="inline-code"><?php echo e($baseUrl); ?></code>
                        <p>Every endpoint below is relative to this URL.</p>
                    </div>

                    <div class="info-card">
                        <h2>Authentication</h2>
                        <p>Protected routes use a Sanctum bearer token:</p>
                        <code class="inline-code">Authorization: Bearer your-token</code>
                        <p>When the security layer is enabled, send the API client header as well:</p>
                        <code class="inline-code">X-API-Token: your-api-key</code>
                    </div>
                </div>

                <div class="note">
                    <strong>Multiple API surfaces.</strong>
                    Endpoints are grouped by consumer: the individual app, the theater workspace,
                    the group workspace, the central dashboard, and public or internal flows.
                </div>
            </section>

            <p class="empty-state search-empty" id="searchEmpty" hidden>
                No endpoints match the current filters.
            </p>

            <section id="common-errors" class="section">
                <div class="section-heading">
                    <div>
                        <span class="eyebrow">Reference</span>
                        <h2>Common errors</h2>
                    </div>
                    <p>Every endpoint uses the same error response shape.</p>
                </div>

                <div class="error-list">
                    <?php $__empty_1 = true; $__currentLoopData = $commonErrors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <article class="error-card">
                            <div>
                                <h3><?php echo e($error->name); ?></h3>
                                <p><?php echo e($error->short_description); ?></p>
                            </div>

                            <?php if (isset($component)) { $__componentOriginal19e13c0d58dd7e91fb0582a388698f6d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal19e13c0d58dd7e91fb0582a388698f6d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.docs.code-box','data' => ['title' => 'Example response','content' => $error->error_response]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('docs.code-box'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Example response','content' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($error->error_response)]); ?>
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
                        </article>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <p class="empty-state">No common errors have been documented yet.</p>
                    <?php endif; ?>
                </div>
            </section>

            <?php $__currentLoopData = $apiSections; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sectionName => $modules): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <section
                    id="consumer-<?php echo e(\Illuminate\Support\Str::slug($sectionName)); ?>"
                    class="section"
                >
                    <div class="section-heading">
                        <div>
                            <span class="eyebrow">API surface</span>
                            <h2 class="consumer-title"><?php echo e($sectionName); ?></h2>
                        </div>
                        <p><?php echo e(collect($modules)->sum(fn ($module) => $module['total'])); ?> endpoints</p>
                    </div>

                    <?php $__currentLoopData = $modules; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $moduleName => $module): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="module-block">
                            <div class="module-heading">
                                <h3 id="module-<?php echo e(\Illuminate\Support\Str::slug($sectionName.' '.$moduleName)); ?>">
                                    <?php echo e($moduleName); ?>

                                </h3>
                                <span class="count-badge"><?php echo e($module['total']); ?></span>
                            </div>

                            <?php $__currentLoopData = $module['groups']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $group): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php if($group['name']): ?>
                                    <div class="endpoint-group">
                                        <div class="group-heading">
                                            <h4 id="group-<?php echo e(\Illuminate\Support\Str::slug($sectionName.' '.$moduleName.' '.$group['name'])); ?>">
                                                <?php echo e($group['name']); ?>

                                            </h4>
                                            <span class="count-badge"><?php echo e(count($group['items'])); ?></span>
                                        </div>

                                        <div class="endpoint-list">
                                            <?php $__currentLoopData = $group['items']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <?php if (isset($component)) { $__componentOriginal419130261bc52609147626609d6391dd = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal419130261bc52609147626609d6391dd = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.docs.endpoint','data' => ['item' => $item,'group' => $group['name'],'module' => $moduleName,'section' => $sectionName]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('docs.endpoint'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['item' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($item),'group' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($group['name']),'module' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($moduleName),'section' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($sectionName)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal419130261bc52609147626609d6391dd)): ?>
<?php $attributes = $__attributesOriginal419130261bc52609147626609d6391dd; ?>
<?php unset($__attributesOriginal419130261bc52609147626609d6391dd); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal419130261bc52609147626609d6391dd)): ?>
<?php $component = $__componentOriginal419130261bc52609147626609d6391dd; ?>
<?php unset($__componentOriginal419130261bc52609147626609d6391dd); ?>
<?php endif; ?>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="endpoint-list">
                                        <?php $__currentLoopData = $group['items']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <?php if (isset($component)) { $__componentOriginal419130261bc52609147626609d6391dd = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal419130261bc52609147626609d6391dd = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.docs.endpoint','data' => ['item' => $item,'group' => $group['name'],'module' => $moduleName,'section' => $sectionName]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('docs.endpoint'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['item' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($item),'group' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($group['name']),'module' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($moduleName),'section' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($sectionName)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal419130261bc52609147626609d6391dd)): ?>
<?php $attributes = $__attributesOriginal419130261bc52609147626609d6391dd; ?>
<?php unset($__attributesOriginal419130261bc52609147626609d6391dd); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal419130261bc52609147626609d6391dd)): ?>
<?php $component = $__componentOriginal419130261bc52609147626609d6391dd; ?>
<?php unset($__componentOriginal419130261bc52609147626609d6391dd); ?>
<?php endif; ?>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </section>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

            <footer class="footer">
                <p><?php echo e(config('app.name')); ?> API reference. Generated from the live documentation data.</p>
            </footer>
        </div>
    </main>
</div>

<button class="back-to-top" id="backToTop" type="button" aria-label="Back to top">
    <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M12 19V5M5 12l7-7 7 7" />
    </svg>
</button>

<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-json.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-bash.min.js"></script>
<script src="<?php echo e(asset('js/docs.js')); ?>?v=<?php echo e(filemtime(public_path('js/docs.js'))); ?>"></script>
</body>
</html>
<?php /**PATH /home/masarat/Desktop/TheaterLink/resources/views/docs-api.blade.php ENDPATH**/ ?>