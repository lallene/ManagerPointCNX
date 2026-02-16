<div class="bg-white p-6 rounded-xl shadow">
    <h3 class="text-lg font-bold">Charge de travail (Top 5)</h3>

    <div wire:ignore id="workloadChart"></div>
</div>

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        var options = {
            chart: {
                type: 'bar',
                height: 350
            },
            series: [{
                name: 'Heures',
                data: @json($top5->pluck('total'))
            }],
            xaxis: {
                categories: @json($top5->pluck('user.name'))
            }
        };

        var chart = new ApexCharts(document.querySelector("#workloadChart"), options);
        chart.render();

        // L'astuce du Lead : On écoute le rafraîchissement de Livewire pour mettre à jour les données
        document.addEventListener('livewire:load', function() {
            @this.on('dataUpdated', (newData, newLabels) => {
                chart.updateOptions({
                    series: [{
                        data: newData
                    }],
                    xaxis: {
                        categories: newLabels
                    }
                });
            });
        });
    </script>
    @push('scripts')
