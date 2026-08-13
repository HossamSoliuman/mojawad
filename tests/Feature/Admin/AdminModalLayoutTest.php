<?php

it('uses wider responsive modal widths throughout the admin area', function () {
    $adminStyles = file_get_contents(resource_path('css/admin.css'));
    $productionQueue = file_get_contents(resource_path('views/livewire/admin/production-queue.blade.php'));
    $factoryQueue = file_get_contents(resource_path('views/livewire/admin/factory-queue.blade.php'));
    $tilawaIndex = file_get_contents(resource_path('views/admin/tilawat/index.blade.php'));

    expect($adminStyles)
        ->toContain('width: min(720px, calc(100vw - 2rem));')
        ->toContain('body.adm .fbr-form-modal { width: min(1040px, 96vw);')
        ->toContain('body.adm .fbr-campaign-modal { width: min(840px, 96vw);')
        ->toContain('body.adm .fbr-confirm-modal { width: min(520px, 94vw);')
        ->toContain('body.adm .fbr-instructions-modal { width: min(1040px, 96vw);')
        ->toContain('body.adm .fbr-publish-modal { width: min(640px, 94vw);')
        ->and($productionQueue)
        ->toContain('max-width:1280px;width:96vw')
        ->toContain('max-width:760px;width:96vw')
        ->toContain('max-width:560px')
        ->and($factoryQueue)
        ->toContain('max-width:540px')
        ->and($tilawaIndex)
        ->toContain('max-width:560px');
});
