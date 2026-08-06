<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', DashboardController::class)->name('dashboard');

// Users...
Route::get('users', [UserController::class, 'index'])->name('users.index');

Route::middleware('permission:manage-users')->group(function (): void {
    Route::post('users', [UserController::class, 'store'])->name('users.store');
    Route::patch('users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::put('users/{user}/verify', [UserController::class, 'verify'])->name('users.verify');
    Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
});

// Roles...
Route::middleware('permission:manage-roles')->group(function (): void {
    Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
    Route::post('roles', [RoleController::class, 'store'])->name('roles.store');
    Route::patch('roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    Route::delete('roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
});
