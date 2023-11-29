<?php

// un namespace permettant de catégoriser virtuellement (dans un espace de nom la classe en question)
namespace Controller;

// 'utilisation du "use" pour accéder à la classe Connect
use Model\Connect;

class CinemaController {
    // Lister les Réalisateurs/Réalisatrices
    public function listRealisateurs() {
        // On se connecte
        $pdo = Connect::seConnecter();

        // On exécute la requête
        $requeteLR = $pdo->query("
        SELECT DISTINCT
            realisateur.id_realisateur,
            CONCAT(personne.prenom, ' ', personne.nom) AS realisateur,
            personne.dateNaissance AS dateNaissance,
            personne.sexe
            FROM realisateur
            INNER JOIN film ON realisateur.id_realisateur = film.id_realisateur
            INNER JOIN personne ON realisateur.id_personne = personne.id_personne
            ORDER BY personne.dateNaissance DESC
        ");
            
        require "view/listRealisateurs.php";
    }

    public function casting($id) {
        // On se connecte
        $pdo = Connect::seConnecter();

        // On excécute la première requête titre ============================
        $requeteFilm = $pdo->prepare("
        SELECT 
            id_film,
            id_realisateur,
            film.titre AS titre, 
            film.note AS note,
            film.synopsis AS synopsis,
            DATE_FORMAT(film.dateParution, '%Y') AS anneeSortie,
            TIME_FORMAT(SEC_TO_TIME(film.durer*60),'%H:%i') AS dureeFilm
        FROM film
        WHERE film.id_film = :id_film
        ");// ne renvoie qu'une seule ligne

        // Liaison du paramètre :id_film et exécution de la requête
        $requeteFilm->bindParam('id_film', $id);
        $requeteFilm->execute();

        // Récupération des informations sur le film
        $film = $requeteFilm->fetch();
        
        // On exécute la deuxième requête Réalisateur =======================
        $requeteRealisateur = $pdo->prepare("
        SELECT
            film.id_film,
            film.id_realisateur,
            CONCAT(personne.prenom, ' ', personne.nom) AS realisateur
        FROM film
        INNER JOIN realisateur ON film.id_realisateur = realisateur.id_realisateur
        INNER JOIN personne ON realisateur.id_personne = personne.id_personne
        WHERE film.id_film = :id_film
        ");// ne renvoie qu'une seule ligne

        // Liaison du paramètre :id_film et exécution de la requête
        $requeteRealisateur->bindParam('id_film', $id);
        $requeteRealisateur->execute();

        // Récupération des informations sur le réalisateur
        $realisateur = $requeteRealisateur->fetch();

        // On exécute la troisième requête acteur et role ==================
        $requeteRole = $pdo->prepare("
        SELECT 
            film.id_film,
            film.id_realisateur,
            acteur.id_acteur AS id_acteur,
            CONCAT(personne.prenom, ' ', personne.nom) AS acteur,
            role.nom AS role,
            personne.sexe AS sexe
        FROM casting
        INNER JOIN film ON casting.id_film = film.id_film
        INNER JOIN acteur ON casting.id_acteur = acteur.id_acteur
        INNER JOIN role ON casting.id_role = role.id_role
        INNER JOIN personne ON acteur.id_personne = personne.id_personne
        WHERE film.id_film = :id_film
        ");// renvoie potentiellement plusieurs lignes

        // Liaison du paramètre :id_film et exécution de la requête
        $requeteRole->bindParam('id_film', $id);
        $requeteRole->execute();

        require "view/casting.php";
    }

    // Lister les filmographies
    public function listFilmographieR($id) {
        // On se connecte
        $pdo = Connect::seConnecter();

        // On exécute la requête
        $requeteFGR = $pdo->prepare("
        SELECT 
            film.id_film AS id_film,
            film.titre AS film,
            TIME_FORMAT(SEC_TO_TIME(film.durer*60),'%H:%i') AS dureeFilm,
            CONCAT(personne.prenom, ' ', personne.nom) AS realisateur,
            DATE_FORMAT(film.dateParution, '%Y') AS dateSortie,
            GROUP_CONCAT(DISTINCT genres_film.libelle SEPARATOR ' - ') AS genres
        FROM film
        INNER JOIN realisateur ON film.id_realisateur = realisateur.id_realisateur
        INNER JOIN personne ON realisateur.id_personne = personne.id_personne
        INNER JOIN (
            SELECT 
                id_film,
                genre.libelle
                FROM posseder
        INNER JOIN genre ON posseder.id_genre = genre.id_genre) AS genres_film ON film.id_film = genres_film.id_film
        WHERE realisateur.id_realisateur = :id_realisateur
        GROUP BY film.id_film
        ");// // renvoie potentiellement plusieurs lignes

        // Liaison du paramètre :id_realisateur et exécution de la requête
        $requeteFGR->bindParam('id_realisateur', $id);
        $requeteFGR->execute();

        require "view/listFilmographieR.php";
    }

    public function formRealisateur($id) {
        // On se connecte
        $pdo = Connect::seConnecter();
    
        // Récupération des données actuelles du réalisateur
        $requeteIR = $pdo->prepare("
        SELECT 
            prenom, 
            nom, 
            dateNaissance, 
            sexe,
            realisateur.id_realisateur
        FROM personne
        INNER JOIN realisateur ON personne.id_personne = realisateur.id_personne
        WHERE id_realisateur = :id_realisateur
        ");
        $requeteIR->bindParam('id_realisateur', $id);
        $requeteIR->execute();
        $IR = $requeteIR->fetch();

        // Redirection vers la page des paramètres du réalisateur
        require "view/ParamRealisateur.php";
    }
    
    // Modification ou ajout d'un réalisateur dans la BDD (UPDATE & ADD)
    public function UARealisateur($id) {
        // On se connecte
        $pdo = Connect::seConnecter();
    
        // // Récupération des données actuelles du réalisateur
        // $requeteIR = $pdo->prepare("
        // SELECT 
        //     prenom, 
        //     nom, 
        //     dateNaissance, 
        //     sexe,
        //     realisateur.id_realisateur
        // FROM personne
        // INNER JOIN realisateur ON personne.id_personne = realisateur.id_personne
        // WHERE id_realisateur = :id_realisateur
        // ");
        // $requeteIR->bindParam('id_realisateur', $id);
        // $requeteIR->execute();
        // $IR = $requeteIR->fetch();
    
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['modifier'])) {
            // Sanitize les données du formulaire avant de les utiliser
            $prenom = filter_input(INPUT_POST, 'prenom', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $nom = filter_input(INPUT_POST, 'nom', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $dateNaissance = filter_input(INPUT_POST, 'dateNaissance', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $sexe = filter_input(INPUT_POST, 'sexe', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

            // Exécution de la requête de mise à jour
            $requeteUR = $pdo->prepare("
            UPDATE personne
            INNER JOIN realisateur ON personne.id_personne = realisateur.id_personne
            SET 
              prenom = :prenom, 
              nom = :nom, 
              dateNaissance = :dateNaissance,
              sexe = :sexe
            WHERE id_realisateur = :id_realisateur
            ");

            // Liaison des paramètres pour la mise à jour 
            $requeteUR->bindParam('prenom', $prenom);
            $requeteUR->bindParam('nom', $nom);
            $requeteUR->bindParam('dateNaissance', $dateNaissance);
            $requeteUR->bindParam('sexe', $sexe);
            $requeteUR->bindParam('id_realisateur', $id);
            $requeteUR->execute();
    
            // Redirection vers la page de confirmation
            require "view/confirmation.php";
        }
        
        // Redirection vers la page des paramètres du réalisateur
        require "view/ParamRealisateur.php";
    }

    // Modification ou ajout d'un réalisateur dans la BDD (UPDATE & ADD)
    public function DRealisateur($id) {
        // On se connecte
        $pdo = Connect::seConnecter();
    
        // Récupération des données actuelles du réalisateur
        $requeteIR = $pdo->prepare("
        SELECT 
            prenom, 
            nom, 
            dateNaissance, 
            sexe,
            realisateur.id_realisateur
        FROM personne
        INNER JOIN realisateur ON personne.id_personne = realisateur.id_personne
        WHERE id_realisateur = :id_realisateur
        ");
        $requeteIR->bindParam('id_realisateur', $id);
        $requeteIR->execute();
        $IR = $requeteIR->fetch();

        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['supprimer'])) {
            // Vérifier avec le formateur, si mettre le réalisateur en default ou supprimer tout ce qui est relier au réalisateur (film, posseder:genre, casting, acteur, role et personne). Je me demandais si je ne devais pas proposer les deux options au moment du clic sur le bouton de suppression, offrant ainsi deux choix.

            // Suppression des enregistrements liés avec la clé étrangère FK_acteur_personne dans la table acteur et personne

            // Suppression des enregistrements liés avec la clé étrangère FK__acteur et FK__role dans la table casting

            // Suppression des enregistrements liés avec la clé étrangère FK_casting_film dans la table casting
            
            // Suppression des enregistrements liés avec la clé étrangère FK__film dans la table posseder
            $requeteDeletePosseder = $pdo->prepare("
            DELETE 
            FROM posseder
            WHERE id_film IN (
                SELECT id_film
                FROM film
            WHERE id_realisateur = :id_realisateur)
            ");

            // Liaison du paramètre et exécution de la requête de suppression des enregistrements liés
            $requeteDeletePosseder->bindParam('id_realisateur', $id);
            $requeteDeletePosseder->execute();

            // Suppression des enregistrements liés avec la clé étrangère FK_film_realisateur dans la table film
            $requeteDeleteFilms = $pdo->prepare("
            DELETE FROM film
            WHERE id_realisateur = :id_realisateur
            ");

            // Liaison du paramètre et exécution de la requête de suppression des films associés
            $requeteDeleteFilms->bindParam('id_realisateur', $id);
            $requeteDeleteFilms->execute();

            // Suppression du réalisateur et des informations associées
            $requeteDR = $pdo->prepare("
            DELETE 
                realisateur, 
                personne 
            FROM realisateur
            INNER JOIN personne ON realisateur.id_personne = personne.id_personne
            WHERE realisateur.id_realisateur = :id_realisateur
            ");
    
            // Liaison du paramètre et exécution de la requête de suppression
            $requeteDR->bindParam('id_realisateur', $id);
            $requeteDR->execute();
    
            // Redirection vers la page de confirmation
            require "view/confirmation.php";
        }

        // Redirection vers la page des paramètres du réalisateur
        require "view/ParamRealisateur.php";
    }
}

