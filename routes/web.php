<?php

use Illuminate\Support\Facades\Route;

use \SoftHouse\MonitoringService\Http\Controllers\{
    CommandsController,
    EventsController,
    ExceptionsController,
    GatesController,
    JobsController,
    RequestsController,
    SchedulesController,
};


Route::get('/monitoring/service/commands', [CommandsController::class, 'index'])->name('monitoring.service.commands.index');
Route::get('/monitoring/service/command/{id}', [CommandsController::class, 'show'])->name('monitoring.service.commands.show');

Route::get('/monitoring/service/events', [EventsController::class, 'index'])->name('monitoring.service.events.index');
Route::get('/monitoring/service/event/{id}', [EventsController::class, 'show'])->name('monitoring.service.events.show');

Route::get('/monitoring/service/exceptions', [ExceptionsController::class, 'index'])->name('monitoring.service.exceptions.index');
Route::get('/monitoring/service/exceptions/{id}', [ExceptionsController::class, 'show'])->name('monitoring.service.exceptions.show');

Route::get('/monitoring/service/gates', [GatesController::class, 'index'])->name('monitoring.service.gates.index');
Route::get('/monitoring/service/gates/{id}', [GatesController::class, 'show'])->name('monitoring.service.gates.show');

Route::get('/monitoring/service/jobs', [JobsController::class, 'index'])->name('monitoring.service.jobs.index');
Route::get('/monitoring/service/jobs/{id}', [JobsController::class, 'show'])->name('monitoring.service.jobs.show');

Route::get('/monitoring/service/request', [RequestsController::class, 'index'])->name('monitoring.service.request.index');
Route::get('/monitoring/service/request/{id}', [RequestsController::class, 'show'])->name('monitoring.service.request.show');

Route::get('/monitoring/service/schedules', [SchedulesController::class, 'index'])->name('monitoring.service.schedules.index');
Route::get('/monitoring/service/schedules/{id}', [SchedulesController::class, 'show'])->name('monitoring.service.schedules.show');


