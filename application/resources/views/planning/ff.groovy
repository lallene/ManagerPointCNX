  $superviseurs = DB::table('agents as superviseurs')
        ->join('projets', 'superviseurs.projet_id', '=', 'projets.id')
        ->leftJoin('agents as managers', 'superviseurs.manager', '=', 'managers.workday_id') // jointure sur workday_id
        ->where('superviseurs.fonction', 'SUPERVISEUR')
        ->select(
            'superviseurs.*',
            'projets.designation as nom_projet',
            'superviseurs.manager',
            DB::raw("CONCAT(managers.nom, ' ', managers.prenom) as nom_manager")
        )
        ->get()
        ->groupBy(['nom_projet', 'nom_manager']);