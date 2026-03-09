<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController; 
use App\Http\Controllers\AgentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\PlanningController;
use App\Http\Controllers\PointageController;
use App\Http\Controllers\ProjetController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\UtilisateurController;


Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');


Route::group(['prefix' => 'configuration/projet', 'as' => 'projet.'], function () {
    
    Route::get('/ajax', [ProjetController::class, 'ajax'])->name('ajax');
    Route::get('/', [ProjetController::class, 'index'])->name('index');
    Route::get('/create', [ProjetController::class, 'create'])->name('create');
    Route::post('/store', [ProjetController::class, 'store'])->name('store');

    Route::get('/{projet}/edit', [ProjetController::class, 'edit'])->name('edit');
    Route::put('/{projet}', [ProjetController::class, 'update'])->name('update');
    Route::delete('/{projet}', [ProjetController::class, 'destroy'])->name('destroy');
});

Route::middleware(['auth'])->group(function () {

    Route::get('/change-password', [LoginController::class, 'showChangePasswordForm'])->name('changePassword');
    Route::post('/update-password', [LoginController::class, 'updatePassword'])->name('updatePassword');

    Route::get('/home', [DashboardController::class, 'index'])->name('home');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('pointage')->group(function () {
        Route::get('/', [PointageController::class, 'index'])->name('pointage.index');
        Route::get('/create', [PointageController::class, 'create'])->name('pointage.create');
        Route::post('/store', [PointageController::class, 'store'])->name('pointage.store');
        Route::delete('/{pointage}', [PointageController::class, 'destroy'])->name('pointage.destroy');
        Route::get('/api/data', [PointageController::class, 'getPointageData'])->name('pointage.api.data');
        Route::get('/api/projets-by-site', [PointageController::class, 'getProjetsBySite'])->name('api.projets.by.site');
        Route::get('/group', [PointageController::class, 'index'])->name('index');
        Route::get('/data', [PointageController::class, 'apiData'])->name('api.data');
Route::get('/pointage/export/excel', [PointageController::class, 'exportExcel'])->name('pointage.export.excel');

    });

    Route::prefix('planning')->group(function () {
        Route::get('/', [PlanningController::class, 'index'])->name('planification');
        Route::get('/global', [PlanningController::class, 'PlanningGlobal'])->name('planning.global');
        Route::get('/graphique', [PlanningController::class, 'planningJourneeGraphique'])->name('planning.graph');
        Route::post('/import', [PlanningController::class, 'import'])->name('planning.import');
        Route::get('/projet', [PlanningController::class, 'showGroupPlanningView'])->name('planning.projet');
        Route::get('/api/data', [PlanningController::class, 'getPlanningData'])->name('getPlanningData');
    });
});

Route::middleware(['auth', 'role:RH|IT|Manager|Top Manager|Directeur'])->group(function () {
    Route::prefix('effectif')->group(function () {
        Route::get('/liste', [AgentController::class, 'index'])->name('effectifs'); 
        Route::get('/create', [AgentController::class, 'create'])->name('effectif.create');
        Route::post('/store', [AgentController::class, 'store'])->name('effectif.store');
        Route::get('/{id}/edit', [AgentController::class, 'edit'])->name('effectif.edit');
        Route::put('/{id}/update', [AgentController::class, 'update'])->name('effectif.update');
        Route::delete('/{id}/destroy', [AgentController::class, 'destroy'])->name('effectif.destroy');
        Route::post('/import', [AgentController::class, 'import'])->name('effectif.import');
        Route::get('/ajax', [AgentController::class, 'ajax'])->name('effectif.ajax');
    });
});

Route::middleware(['auth', 'role:IT'])->prefix('configuration')->group(function () {
    
    Route::resource('permissions', PermissionController::class)->names([
        'index' => 'permission.index',
        'create' => 'permission.create',
        'store' => 'permission.store',
        'edit' => 'permission.edit',
        'update' => 'permission.update',
        'destroy' => 'permission.destroy',
    ]);
    
  //  Route::resource('projet', ProjetController::class)->except(['show']);
    Route::resource('site', SiteController::class)->except(['show']);
    Route::post('/projet/import', [ProjetController::class, 'import'])->name('projet.import');
    
    Route::get('/users/ajax', [UtilisateurController::class, 'ajax'])->name('users.ajax');
    Route::resource('users', UtilisateurController::class);
});

Route::get('/forgot-password', function () {
    return "Veuillez contacter l'administrateur IT pour réinitialiser votre mot de passe.";
})->name('password.request');

Route::get('/planning/global', [PlanningController::class, 'PlanningGlobal'])->name('planning.global');
Route::post('/planning/import-grid', [PlanningController::class, 'importGrid']);

Route::middleware(['auth'])->group(function () {

    Route::get('/planning/journalier', [PlanningController::class, 'dailyView'])
        ->name('planning.daily');

    Route::get('/api/planning/daily-data', [PlanningController::class, 'getDailyPlanningData'])
        ->name('getDailyPlanningData');

        Route::post('/planning/import', [PlanningController::class, 'import'])->name('plannings.import');
        Route::post('/planning/store', [PlanningController::class, 'store'])->name('planning.store');

});


Route::post('/plannings/paste-import', [PlanningController::class, 'pasteImport'])->name('plannings.paste-import');
