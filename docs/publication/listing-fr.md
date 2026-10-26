# Présentation du module (français)

## Nom

WeAdU Shopping feed check

## Résumé

Module gratuit qui vérifie, dans le back-office, les champs du catalogue utilisés par Google Shopping et liste, produit par produit, ce qu'il faut corriger avant d'envoyer un flux à Merchant Center.

## Présentation

- Lit les produits actifs de la boutique courante, dans la langue du back-office (200 articles par défaut, 500 au maximum, déclinaisons comprises). Si la limite coupe le catalogue, la page dit combien d'articles n'ont pas été lus.
- Signale pour chaque produit les erreurs (titre, description, lien, image, disponibilité ou prix vides, titre de plus de 150 caractères, prix nul, GTIN dont la longueur ou la clé de contrôle est fausse, référence en double) et les avertissements (titre court, tout en capitales ou avec une formule promotionnelle, prix sans devise, marque vide, GTIN et MPN vides, état vide ou inconnu).
- Propose un titre qui commence par la marque quand celle-ci n'apparaît pas dans le titre.
- Contrôle chaque déclinaison, avec le prix catalogue et le prix soldé s'il est plus bas (y compris un prix spécifique fixe), et un lien de modification par produit. Les prix sont TTC par défaut. Pour un flux États-Unis ou Canada, choisir le mode hors taxe.
- Exporte le résultat en CSV.
- Interface en français et en anglais.

Le module ne modifie pas vos produits, n'appelle ni Google ni aucun autre service, et n'envoie aucune donnée. Un produit sans constat n'est pas validé pour autant par Google.

Compatibilité déclarée : PrestaShop 1.7.8 et versions suivantes. Licence AFL-3.0. Conçu par WeAdU.

## Limites à connaître

- Le prix vérifié est le prix catalogue de chaque déclinaison (TTC par défaut, HT si ce mode est choisi), avec le prix soldé quand le client paie moins. Le calcul vaut pour le groupe client par défaut. Le mode HT sert à un flux États-Unis ou Canada. Le module ne calcule pas la sales tax de ces pays.
- La disponibilité suit la quantité en stock de la boutique courante.
- Le GTIN est lu dans l'EAN-13, puis l'UPC, puis l'ISBN.
