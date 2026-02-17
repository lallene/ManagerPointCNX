<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController; // IMPORT MANQUANT
use App\Http\Controllers\AgentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\PlanningController;
use App\Http\Controllers\PointageController;
use App\Http\Controllers\ProjetController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\UtilisateurController;


// --- ROUTES PUBLIQUES ---
Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// --- ROUTES SOUS AUTHENTIFICATION ---
Route::middleware(['auth'])->group(function () {

    // Gestion du changement de mot de passe (Lié à ton LoginController)
    Route::get('/change-password', [LoginController::class, 'showChangePasswordForm'])->name('changePassword');
    Route::post('/update-password', [LoginController::class, 'updatePassword'])->name('updatePassword');

    // Dashboard & Accueil
    Route::get('/home', [DashboardController::class, 'index'])->name('home');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Pointages
    Route::prefix('pointage')->group(function () {
        Route::get('/', [PointageController::class, 'index'])->name('pointage.index');
        Route::get('/create', [PointageController::class, 'create'])->name('pointage.create');
        Route::post('/store', [PointageController::class, 'store'])->name('pointage.store');
        Route::delete('/{pointage}', [PointageController::class, 'destroy'])->name('pointage.destroy');
        Route::get('/pointages/api/data', [PointageController::class, 'getPointageData'])->name('pointages.global');
        Route::get('/api/projets-by-site', [PointageController::class, 'getProjetsBySite'])->name('api.projets.by.site');
    });

    // Plannings
    Route::prefix('planning')->group(function () {
        Route::get('/', [PlanningController::class, 'index'])->name('planification');
        Route::get('/global', [PlanningController::class, 'PlanningGlobal'])->name('planning.global');
        Route::get('/graphique', [PlanningController::class, 'planningJourneeGraphique'])->name('planning.graph');
        Route::post('/import', [PlanningController::class, 'import'])->name('planning.import');
        Route::get('/projet', [PlanningController::class, 'showGroupPlanningView'])->name('planning.projet');
        Route::get('/api/data', [PlanningController::class, 'getPlanningData'])->name('getPlanningData');
    });
});

// --- ROUTES RH / IT / MANAGERS ---
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

// --- ADMINISTRATION (IT SEULEMENT) ---
Route::middleware(['auth', 'role:IT'])->prefix('configuration')->group(function () {
    
        // Dans web.php, à l'intérieur du groupe IT
    Route::resource('permissions', PermissionController::class)->names([
        'index' => 'permission.index',
        'create' => 'permission.create',
        'store' => 'permission.store',
        'edit' => 'permission.edit',
        'update' => 'permission.update',
        'destroy' => 'permission.destroy',
    ]);
    
    // Projets & Sites
    Route::resource('projet', ProjetController::class)->except(['show']);
    Route::get('projet-ajax', [ProjetController::class, 'ajax'])->name('projets.ajax');
    Route::resource('site', SiteController::class)->except(['show']);
    
    // Utilisateurs
    Route::get('/users/ajax', [UtilisateurController::class, 'ajax'])->name('users.ajax');
    Route::resource('users', UtilisateurController::class);
});

Route::get('/forgot-password', function () {
    return "Veuillez contacter l'administrateur IT pour réinitialiser votre mot de passe.";
})->name('password.request');

Route::get('/planning/global', [PlanningController::class, 'PlanningGlobal'])->name('planning.global');



Route::middleware(['auth'])->group(function () {

    Route::get('/planning/journalier', [PlanningController::class, 'dailyView'])
        ->name('planning.daily');

    Route::get('/api/planning/daily-data', [PlanningController::class, 'getDailyPlanningData'])
        ->name('getDailyPlanningData');

});