<?php

use App\Http\Controllers\ReportExportController;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Buildings\Index as BuildingsIndex;
use App\Livewire\Buildings\Show as BuildingsShow;
use App\Livewire\Charges\Index as ChargesIndex;
use App\Livewire\Dashboard\Index as DashboardIndex;
use App\Livewire\Expenses\Index as ExpensesIndex;
use App\Livewire\Payments\Index as PaymentsIndex;
use App\Livewire\Reports\Index as ReportsIndex;
use App\Livewire\Residents\Index as ResidentsIndex;
use App\Livewire\Settings\Index as SettingsIndex;
use App\Livewire\Units\Index as UnitsIndex;
use App\Livewire\Units\Show as UnitsShow;
use App\Livewire\Users\Index as UsersIndex;
use Illuminate\Support\Facades\Route;

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
    Route::get('/register', Register::class)->name('register');
});

// Logout
Route::post('/logout', function () {
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('login');
})->name('logout');

// Auth routes
Route::middleware('auth')->group(function () {
    Route::get('/', fn () => redirect()->route('dashboard'));
    Route::get('/dashboard', DashboardIndex::class)->name('dashboard');

    Route::get('/buildings', BuildingsIndex::class)->name('buildings.index');
    Route::get('/buildings/{building}', BuildingsShow::class)->name('buildings.show');

    Route::get('/units', UnitsIndex::class)->name('units.index');
    Route::get('/units/{unit}', UnitsShow::class)->name('units.show');

    Route::get('/residents', ResidentsIndex::class)->name('residents.index');

    Route::get('/charges', ChargesIndex::class)->name('charges.index');

    Route::get('/expenses', ExpensesIndex::class)->name('expenses.index');

    Route::get('/payments', PaymentsIndex::class)->name('payments.index');

    Route::get('/reports', ReportsIndex::class)->name('reports.index');
    Route::get('/reports/export/excel', [ReportExportController::class, 'excel'])->name('reports.export.excel');
    Route::get('/reports/export/pdf', [ReportExportController::class, 'pdf'])->name('reports.export.pdf');

    Route::get('/users', UsersIndex::class)->name('users.index');

    Route::get('/settings', SettingsIndex::class)->name('settings.index');
});
