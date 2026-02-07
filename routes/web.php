<?php

use App\Http\Controllers\ExperimentController;
use App\Http\Controllers\SigningWorkflowController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SigningWorkflowController::class, 'index'])->name('signing.index');
Route::post('/signing/initiate', [SigningWorkflowController::class, 'initiate'])->name('signing.initiate');
Route::post('/signing/otp/verify', [SigningWorkflowController::class, 'verifyOtp'])->name('signing.otp.verify');
Route::post('/signing/liveness/verify', [SigningWorkflowController::class, 'verifyLiveness'])->name('signing.liveness.verify');
Route::post('/signing/apply', [SigningWorkflowController::class, 'applySignature'])->name('signing.apply');
Route::post('/signing/verify', [SigningWorkflowController::class, 'verifySignature'])->name('signing.verify');

Route::get('/experiments', [ExperimentController::class, 'index'])->name('experiments.index');
Route::post('/experiments/run', [ExperimentController::class, 'run'])->name('experiments.run');
Route::post('/experiments/attempts', [ExperimentController::class, 'storeAttempt'])->name('experiments.attempts.store');
Route::get('/experiments/attempts', [ExperimentController::class, 'attempts'])->name('experiments.attempts.index');
