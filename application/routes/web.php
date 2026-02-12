<?php

use App\Http\Controllers\AgentController;
use App\Http\Controllers\UtilisateurController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProjetController;
use App\Http\Controllers\PlanningController;
use App\Http\Controllers\PointageController;
use App\Http\Controllers\RapportControlleur;



use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/home', [HomeController::class, 'index'])->name('home');
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/home/data', [HomeController::class, 'ajaxData'])->name('home.data'); // flux JSON
Route::get('/home/weeklyReport', [HomeController::class, 'weeklyReport'])->name('home.weeklyReport'); // flux JSON
Route::get('/manager/dashboard', [HomeController::class, 'dashboard'])->name('manager.dashboard');
Route::get('/manager/export/excel', [HomeController::class, 'exportExcel'])->name('manager.export.excel');
Route::get('/manager/export/pdf', [HomeController::class, 'exportPdf'])->name('manager.export.pdf');
Route::get('/findProjet/{id}', [HomeController::class, 'findProjet'])->name('findProjet');

Auth::routes();



Route::group(['middleware' => ['auth']], function() {
    Route::resource('profil', \App\Http\Controllers\RoleController::class);
    Route::resource('permission', \App\Http\Controllers\PermissionController::class);
    Route::resource('projet', \App\Http\Controllers\ProjetController::class);
    Route::resource('effectif', \App\Http\Controllers\AgentController::class);
});




Route::prefix('utilisateurs')->group(function() {
    Route::get('/',                     [UtilisateurController::class, 'index'])->name('users.index');
    Route::get('/create',               [UtilisateurController::class, 'create'])->name('users.create');
    Route::post('/',                    [UtilisateurController::class, 'store'])->name('users.store');
    Route::get('/ajax',                 [UtilisateurController::class, 'ajax'])->name('users.ajax');
    Route::get('{id}',                  [UtilisateurController::class, 'show'])->name('users.show');
    Route::get('{id}/edit',             [UtilisateurController::class, 'edit'])->name('users.edit');
    Route::put('{id}',                  [UtilisateurController::class, 'update'])->name('users.update');
    Route::delete('{id}',               [UtilisateurController::class, 'destroy'])->name('users.destroy');
});


Route::get('change-password', [UtilisateurController::class, 'showChangePasswordForm'])->name('changePassword');
Route::post('change-password', [UtilisateurController::class, 'updatePassword'])->name('updatePassword');
Route::post('/effectif/import', [App\Http\Controllers\AgentController::class, 'import'])->name('import_agent');



Route::get('/agents', [AgentController::class, 'index'])->name('agents.index');
Route::get('/agents/create', [AgentController::class, 'create'])->name('agents.create');
Route::post('/agents/store', [AgentController::class, 'store'])->name('agents.store');
Route::get('/agents/ajax', [AgentController::class, 'ajax'])->name('agents.ajax');
Route::get('/projets/ajax', [ProjetController::class, 'ajax'])->name('projets.ajax');



Route::get('api/agents', [AgentController::class, 'index']);
Route::get('/effectifs',[App\Http\Controllers\AgentController::class, 'liste'])->name('effectifs');
Route::get('/planification',[App\Http\Controllers\PlanningController::class, 'index'])->name('planification');
Route::post('/planning', [PlanningController::class, 'store'])->name('planning.store');
Route::get('/planning/group', [PlanningController::class, 'showGroupPlanning'])->name('planning.group');
Route::get('/planning/group-journee', [PlanningController::class, 'showGroupPlanningDay'])->name('planning.group.journee')->middleware('role:Manager|Responsables d’équipe');
Route::get('/planning/global', [PlanningController::class, 'PlanningGlobal'])->name('planning.PlanningGlobal');
Route::get('/projets-par-site/{site}', [HomeController::class, 'getProjetsParSite'])->name('projets.par.site');
Route::post('/plannings/import', [PlanningController::class, 'import'])->name('plannings.import');
Route::get('/planning/journee-graph', [PlanningController::class, 'planningJourneeGraphique'])->name('planning.journee.graphique')->middleware('role:Manager');


Route::prefix('pointages')->middleware('auth')->group(function () {
    Route::get('/', [PointageController::class, 'PointageGlobal'])->name('pointages.global');
    Route::get('/test', [PointageController::class, 'index'])->name('pointages.test');
    Route::get('/creer', [PointageController::class, 'create'])->name('pointages.create')->middleware('role:Manager');
    Route::get('/group', [PointageController::class, 'group'])->name('pointages.group');
    Route::post('/', [PointageController::class, 'store'])->name('pointages.store');
    Route::put('/{pointage}', [PointageController::class, 'update'])->name('pointages.update');
    Route::delete('/{pointage}', [PointageController::class, 'destroy'])->name('pointages.destroy');
});



Route::get('/rapport/pointages', [RapportControlleur::class, 'index'])->name('rapport.pointages');
Route::get('/rapport/pointages/json', [RapportControlleur::class, 'json'])->name('rapport.pointages.json');
Route::get('/rapport/pointages/export', [RapportControlleur::class, 'exportExcel'])->name('rapport.pointages.export');
Route::post('/importprojet', [App\Http\Controllers\ProjetController::class, 'import']);
Route::post('/importemploi', [App\Http\Controllers\EmploiController::class, 'import']);
Route::post('/importsubfonction', [App\Http\Controllers\Sub_FonctionController::class, 'import']);
Route::post('/importmotif', [App\Http\Controllers\Motif_consultationController::class, 'import']);
Route::post('/importmatricule', [App\Http\Controllers\MatriculeControlleur::class, 'import']);






Route::get('/rapport', [App\Http\Controllers\RapportController::class, 'index'])->name('rapport');
Route::get('/rapport/search', [App\Http\Controllers\RapportController::class, 'rapport'])->name('rapport_search');
Route::get('/rapport/envoi}', [App\Http\Controllers\RapportController::class, 'export'])->name('rapportsend');
Route::get('/rapport/envoi/', [App\Http\Controllers\SearchController::class, 'export'])->name('rapportsearch');
Route::get('/home/preview', [App\Http\Controllers\HomeController::class, 'preview'])->name('dashboardpreview');
Route::get('/home/download', [App\Http\Controllers\HomeController::class, 'download'])->name('dashboarddownload');
Route::get('/send-rapport', [App\Http\Controllers\RapportController::class, 'sendMailWitchExecel'])->name('sendMailWitchExecel');
Route::get('/rapport/taux-absence', [HomeController::class, 'tauxAbsence'])->name('rapport.taux.absence');



Route::any('{url}', function(){
    return redirect()->route('login');
})->where('url', '.*');
