document.getElementById('searchButton').addEventListener('click', function() {
    const query = document.getElementById('search').value.trim(); // Enlève les espaces superflus
    const urlSearch = document.getElementById('urlSearch').value;
    
    // Vérifiez que la requête n'est pas vide
    if (query === '') {
        alert('Veuillez entrer un terme de recherche.');
        return;
    }

    fetch(`${urlSearch}?search=${encodeURIComponent(query)}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur réseau lors de la recherche.');
            }
            return response.json();
        })
        .then(data => {
            const tableBody = document.getElementById('agentTable');
            tableBody.innerHTML = ''; // Vide le tableau
            
            if (data.error) {
                tableBody.innerHTML = `<tr><td colspan="8">${data.error}</td></tr>`;
                return;
            }
            
            if (data.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="8">Aucun résultat trouvé.</td></tr>';
            } else {
                data.forEach(agent => {
                    const row = document.createElement('tr');
                    
                    row.innerHTML = `
                        <td>${agent.iris}</td>
                        <td>${agent.nom}</td>
                        <td>${agent.prenom}</td>
                        <td>${agent.matricule}</td>
                        <td>${agent.date_embauche}</td>
                        <td>${agent.projet}</td>
                        <td>${agent.fonction}</td>
                        <td>
                            <a href="${document.getElementById('urlReception').value}/${agent.id}" class="btn btn-primary">Réception justificatif</a>
                            <a href="${document.getElementById('urlConsultation').value}/${agent.id}" class="btn btn-success">Consultation</a>
                        </td>
                    `;
                    
                    tableBody.appendChild(row);
                });
            }
        })
        .catch(error => {
            const tableBody = document.getElementById('agentTable');
            tableBody.innerHTML = '<tr><td colspan="8">Erreur lors de la recherche. Veuillez réessayer plus tard.</td></tr>';
            console.error('Erreur:', error);
        });
});
