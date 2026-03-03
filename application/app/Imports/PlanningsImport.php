<?php

namespace App\Imports;

use App\Models\Agent;
use App\Models\Planning;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class PlanningsImport implements ToCollection, WithHeadingRow
{
    public $successCount = 0;
    public $errorMessage = null;
    protected $week;

    // Ajoute le constructeur pour recevoir la semaine
    public function __construct($week = null)
    {
        $this->week = $week;
    }

    public function collection(Collection $rows)
    {
        $user = Auth::user();
        
        // 1. 🔐 Vérification des Rôles
        $isAdmin = $user->hasRole('Admin IT');
        $isTopManager = $user->hasRole('Top Manager');

        if (!$isAdmin && !$isTopManager) {
            $this->errorMessage = "Accès refusé : Votre rôle ne permet pas l'importation.";
            return;
        }

        // 2. 🌍 Portée (Scope) pour le Top Manager
        $allowedProjetIds = [];
        if ($isTopManager && !$isAdmin) {
            // FIX CRITIQUE : Utilisation de 'work_email' (selon ton Schema users)
            $currentAgent = Agent::with('projets')
                ->where('work_email', $user->work_email) 
                ->first();

            if (!$currentAgent) {
                $this->errorMessage = "Profil agent introuvable pour l'email : {$user->work_email}";
                return;
            }
            
            // On récupère la liste des IDs de tous les projets du Top Manager
            $allowedProjetIds = $currentAgent->projets->pluck('id')->toArray();
        }

        $today = now()->startOfDay();

        // 3. 🔄 Boucle de traitement des lignes Excel
        foreach ($rows as $row) {
            $workdayId = $row['workday_id'] ?? null;
            if (!$workdayId) continue;

            // Eager loading pour éviter le N+1 sur les rôles et projets
            $targetAgent = Agent::with(['projets', 'user.roles'])
                ->where('workday_id', $workdayId)
                ->first();

            // 🚨 Règle 1 : Seuls les plannings des MANAGERS peuvent être créés
            if (!$targetAgent || !$targetAgent->user || !$targetAgent->user->hasRole('Manager')) {
                continue; 
            }

            // 🚨 Règle 2 : Le Top Manager est restreint à ses projets
            if ($isTopManager && !$isAdmin) {
                $targetProjetIds = $targetAgent->projets->pluck('id')->toArray();
                // Si aucune intersection entre les projets du Top Manager et ceux du Manager cible
                if (empty(array_intersect($allowedProjetIds, $targetProjetIds))) {
                    continue; 
                }
            }

            // 4. 📅 Parsing et Sauvegarde
            try {
                $dateRaw = $row['date'] ?? $row['Date'] ?? null;
                if (!$dateRaw) continue;

                $datePlanning = is_numeric($dateRaw)
                    ? Carbon::instance(ExcelDate::excelToDateTimeObject($dateRaw))
                    : Carbon::createFromFormat('d/m/Y', $dateRaw);

                // Sécurité : Pas de modifications dans le passé
                if ($datePlanning->startOfDay()->isBefore($today)) continue;

                Planning::updateOrCreate(
                    [
                        'agent_id' => $targetAgent->id,
                        'jour'     => $datePlanning->format('Y-m-d')
                    ],
                    [
                        'entree'  => $this->formatTime($row['entree'] ?? $row['Entrée'] ?? null),
                        'sortie'  => $this->formatTime($row['sortie'] ?? $row['Sortie'] ?? null),
                        'semaine' => $datePlanning->format('o-W'),
                        'user_id' => $user->id,
                    ]
                );

                $this->successCount++;
            } catch (\Exception $e) {
                \Log::error("Erreur ligne Manager {$workdayId}: " . $e->getMessage());
                continue;
            }
        }
    }

    /**
     * Helper pour formater l'heure de manière robuste (Excel ou String)
     */
    private function formatTime($value)
    {
        if (!$value) return null;
        try {
            if (is_numeric($value)) {
                return Carbon::instance(ExcelDate::excelToDateTimeObject($value))->format('H:i');
            }
            return Carbon::parse($value)->format('H:i');
        } catch (\Exception $e) {
            return null;
        }
    }
}