<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ProfileController;
use App\Http\Middleware\AdminPanelMiddleware;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
        return redirect()->route('calendar.index');
    })->name('dashboard');

    // Kalender & Termine
    Route::get('/calendar', [AppointmentController::class, 'index'])->name('calendar.index');
    Route::get('/calendar/events', [AppointmentController::class, 'events'])->name('calendar.events');
    Route::get('/calendar/company-data', [AppointmentController::class, 'getCompanyData'])->name('calendar.companyData');
    Route::post('/appointments', [AppointmentController::class, 'store'])->name('appointments.store');
    Route::get('/appointments/{appointment}', [AppointmentController::class, 'show'])->name('appointments.show');
    Route::put('/appointments/{appointment}', [AppointmentController::class, 'update'])->name('appointments.update');
    Route::delete('/appointments/{appointment}', [AppointmentController::class, 'destroy'])->name('appointments.destroy');
    Route::post('/appointments/{appointment}/upload', [AppointmentController::class, 'uploadAttachment'])->name('appointments.upload');
    Route::delete('/attachments/{attachment}', [AppointmentController::class, 'deleteAttachment'])->name('attachments.destroy');
    Route::get('/attachments/{attachment}/download', [AppointmentController::class, 'downloadAttachment'])->name('attachments.download');

    // Export Hub, Tagesplan, Wochenplan & Monatsplan
    Route::get('/calendar/export', [AppointmentController::class, 'exportIndex'])->name('calendar.export');
    Route::get('/calendar/daily-plan', [AppointmentController::class, 'dailyPlan'])->name('calendar.dailyPlan');
    Route::get('/calendar/weekly-plan', [AppointmentController::class, 'weeklyPlan'])->name('calendar.weeklyPlan');
    Route::get('/calendar/monthly-plan', [AppointmentController::class, 'monthlyPlan'])->name('calendar.monthlyPlan');

    // Abwesenheiten
    Route::post('/absences', [AppointmentController::class, 'storeAbsence'])->name('absences.store');
    Route::delete('/absences/{absence}', [AppointmentController::class, 'destroyAbsence'])->name('absences.destroy');
    Route::put('/absences/{absence}', [AppointmentController::class, 'updateAbsence'])->name('absences.update');

    // Kunden Info Popup
    Route::get('/clients/{client}', [AppointmentController::class, 'getClient'])->name('clients.get');

    // Dateien-Bereich
    Route::get('/dateien', [DocumentController::class, 'index'])->name('documents.index');
    Route::post('/dateien', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('/dateien/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::delete('/dateien/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');

    // Admin Panel
    Route::prefix('admin')->name('admin.')->middleware(AdminPanelMiddleware::class)->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('index');

        Route::post('/companies', [AdminController::class, 'storeCompany'])->name('companies.store');
        Route::put('/companies/{company}', [AdminController::class, 'updateCompany'])->name('companies.update');
        Route::delete('/companies/{company}', [AdminController::class, 'destroyCompany'])->name('companies.destroy');

        Route::post('/clients', [AdminController::class, 'storeClient'])->name('clients.store');
        Route::put('/clients/{client}', [AdminController::class, 'updateClient'])->name('clients.update');
        Route::delete('/clients/{client}', [AdminController::class, 'destroyClient'])->name('clients.destroy');

        Route::post('/roles', [AdminController::class, 'storeRole'])->name('roles.store');
        Route::put('/roles/{role}', [AdminController::class, 'updateRole'])->name('roles.update');
        Route::delete('/roles/{role}', [AdminController::class, 'destroyRole'])->name('roles.destroy');

        Route::post('/users', [AdminController::class, 'storeUser'])->name('users.store');
        Route::put('/users/{user}', [AdminController::class, 'updateUser'])->name('users.update');
        Route::delete('/users/{user}', [AdminController::class, 'destroyUser'])->name('users.destroy');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

Route::post('/appointments/{appointment}/shift-report', [AppointmentController::class, 'updateShiftReport'])->name('appointments.shift_report')->middleware('auth');

Route::get('/password/change', function () {
    return view('auth.change-password');
})->middleware('auth')->name('password.change');

Route::put('/dateien/{document}', [\App\Http\Controllers\DocumentController::class, 'update'])->name('documents.update')->middleware(['auth']);
