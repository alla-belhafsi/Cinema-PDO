<?php
ob_start();
?>
    <div class='listHeader'>
        <div>
            <h1 class='title'>La liste des Films</h1>
        </div>
        <div>
            <p class= 'counter'> Il y a <b><?= $requeteLF->rowCount() ?></b> Films</p>
        </div>
        <div class= 'add'>
            <p>Ajouter un film
                <a href='index.php?action=AFilm'> 
                    <ion-icon name='film-outline'></ion-icon>
                </a>
            </p>
        </div>
    </div>
    <table class='table'>
        <thead>
            <tr>
                <th>Titre</th>
                <th>Réalisateur</th>
                <th>Durée</th>
                <th>Date de Sortie</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($requeteLF->fetchAll() as $film) { ?>
                <tr>

                    <td class='column' id='selectCase'>
                        <a class='columna' href='index.php?action=casting&id=<?= $film['id_film'] ?>'>
                            <?= $film['titre'] ?>
                        </a>
                    </td>

                    <td class='column' id='selectCase'>
                        <a class='columna' href='index.php?action=listFilmographieR&id=<?= $film['id_realisateur'] ?>'>
                            <?= $film['realisateur'] ?>
                        </a>
                    </td>

                    <td class='tableCenter'>
                        <?= $film['dureeHeure'] ?>
                    </td>

                    <td class='tableCenter'>
                        <?= $film['dateSortie'] ?>
                    </td>

                    <td class='tableCenterUD'>
                        <a class='columna' href='index.php?action=formFilm&id=<?= $film['id_film'] ?>'>
                            <ion-icon name='settings-outline'></ion-icon>
                        </a>
                    </td>

                    <td class='tableCenterUD'>
                        <a class='delete-something columna' href='index.php?action=DFilm&id=<?= $film['id_film'] ?>'>
                            <ion-icon name='trash-outline'></ion-icon>
                        </a>
                    </td>

                </tr>
            <?php } ?>
        </tbody>
    </table>

<?php
$content = ob_get_clean();
$tabTitle = "Films";
$showIconMenu = false;
require_once "view/template/template.php";
?>