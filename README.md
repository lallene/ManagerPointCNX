//////:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::://///

                                                  🚀 Fonctionnalités Clés
🔐 Administration & Sécurité (RBAC)

    Gestion des Rôles : Système basé sur Spatie Permissions (IT, RH, Responsables d'équipe, Manager).

    Contrôle d'Accès : Interface dynamique s'adaptant aux droits de l'utilisateur.

    Gestion des Profils : Administration centralisée des utilisateurs et de leurs permissions.

📅 Planification & Effectifs

    Grille de Planning Journalière : Visualisation horaire (06h - 21h) des créneaux de travail par agent.

    Gestion des Projets : Assignation des effectifs par projet et par site.

    Sélecteur Hebdomadaire : Navigation fluide entre les semaines de l'année.

⏱️ Suivi de Présence (Pointage)

    Suivi en Temps Réel : Comparaison entre le planning prévisionnel et les heures réellement travaillées.

    Indicateurs d'Absences : Calcul automatique des heures d'absence et des temps de pause.

📈 Reporting & Export

    Tableau de Bord Interactif : Filtres multi-critères (Fonction, Semaine, Période).

    Exports Multi-formats : Extraction des données en PDF, Excel (.xlsx) et JPG via des librairies client-side (html2canvas, SheetJS).


//////:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::://///

🛠 Stack Technique

    Framework : Laravel 10+

    Frontend : Blade, Bootstrap 5, jQuery

    Base de données : MySQL

    Librairies JS Clés :

        DataTables : Pour la gestion et le filtrage des grands volumes de données.

        Select2 : Pour des menus de sélection ergonomiques.

        html2canvas & jsPDF : Pour la génération de rapports visuels.

        SheetJS (XLSX) : Pour l'export de données brutes vers Excel.
    
//////:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::://///


📦 Installation

    Cloner le projet :
    Bash

    git clone https://github.com/votre-compte/ManagerPointCnx.git
    cd ManagerPointCnx

    Installer les dépendances PHP :
    Bash

    composer install

    Configurer l'environnement :
    Bash

    cp .env.example .env
    php artisan key:generate

    Configurez vos accès base de données dans le fichier .env.

    Migration et Seeders (Rôles & Permissions) :
    Bash

    php artisan migrate --seed

    Lancer le serveur :
    Bash

    php artisan serve


//////:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::://///

🛠 Maintenance & Évolutions

    [ ] Ajout de graphiques statistiques (Chart.js).

    [ ] Système de notification par email pour les retards.

    [ ] Import de masse via fichier CSV/Excel.

    Développé avec ❤️ pour Concentrix.
