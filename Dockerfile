# 1. On passe sur la version 8.2 (ou 8.4 pour être au top)
FROM php:8.2-fpm-bookworm

# 2. Installation des dépendances système
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libxml2-dev \
    libicu-dev \
    zip \
    unzip \
    git \
    curl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# 3. Installation des extensions PHP indispensables pour Laravel
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
    gd \
    pdo \
    pdo_mysql \
    zip \
    dom \
    bcmath \
    intl \
    opcache

# Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Répertoire de travail
WORKDIR /var/www/application
{
"emptyTable": "Aucune donnée disponible dans le tableau",
"loadingRecords": "Chargement...",
"processing": "Traitement...",
"select": {
"rows": {
"_": "%d lignes sélectionnées",
"1": "1 ligne sélectionnée"
}
},
"zeroRecords": "Aucun élément correspondant trouvé",
"paginate": {
"first": "Premier",
"last": "Dernier",
"next": "Suivant",
"previous": "Précédent"
},
"aria": {
"sortAscending": ": activer pour trier la colonne par ordre croissant",
"sortDescending": ": activer pour trier la colonne par ordre décroissant"
},
"info": "Affichage de _START_ à _END_ sur _TOTAL_ entrées",
"infoEmpty": "Affichage de 0 à 0 sur 0 entrées",
"infoFiltered": "(filtré de _MAX_ entrées au total)",
"search": "Rechercher :",
"lengthMenu": "Afficher _MENU_ entrées"
}