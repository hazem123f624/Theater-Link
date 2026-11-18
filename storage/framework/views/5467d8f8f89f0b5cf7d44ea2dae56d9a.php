<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#6d1f2b">
    <title>TheaterLink</title>
    <?php echo app('Illuminate\Foundation\Vite')->reactRefresh(); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/main.tsx']); ?>
</head>
<body>
    <div id="root"></div>
</body>
</html>
<?php /**PATH /home/masarat/Desktop/TheaterLink/resources/views/app.blade.php ENDPATH**/ ?>