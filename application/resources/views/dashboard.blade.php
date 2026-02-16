<div class="p-6">
    <h1 class="text-2xl font-bold">Dashboard de Pointage</h1>
    
    <div class="grid grid-cols-3 gap-4 my-4">
        <div class="p-4 bg-blue-100 rounded">Taux : {{ $tauxCouverture }}%</div>
        <div class="p-4 bg-red-100 rounded">Retards : {{ $retards }}</div>
    </div>

    <select wire:model="site_id" class="border p-2">
        <option value="">Tous les sites</option>
        @foreach($sites as $site)
            <option value="{{ $site->id }}">{{ $site->nom }}</option>
        @endforeach
    </select>
</div>