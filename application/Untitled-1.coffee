now()->greaterThan($match->match_date) 



@if (isset($match->resultats->first()->resultat))
                        {{ $match->resultats->first()->resultat == isset($match->predictions->first()->prediction) && isset($prediction->user_id )== $user_id ? 'table-success' : 'table-danger' }}
                    @endif">