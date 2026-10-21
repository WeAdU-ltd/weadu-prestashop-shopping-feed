# Présentation du module (français)

## Nom

WeAdU Shopping feed check

## Résumé

Module gratuit qui vérifie, dans le back-office, les champs du catalogue utilisés par Google Shopping et liste, produit par produit, ce qu'il faut corriger avant d'envoyer un flux à Merchant Center.

## Présentation

- Lit les produits actifs de la boutique courante, dans la langue du back-office (200 produits par défaut, 500 au maximum).
- Signale pour chaque produit les erreurs (titre, description, lien, image, disponibilité ou prix vides, titre de plus de 150 caractères, prix nul, GTIN dont la longueur ou la clé de contrôle est fausse, référence en double) et les avertissements (titre court, tout en capitales ou avec une formule promotionnelle, prix sans devise, marque vide, GTIN et MPN vides, état vide ou inconnu).
- Propose un titre qui commence par la marque quand celle-ci n'apparaît pas dans le titre.
- Contrôle chaque déclinaison, avec prix TTC et prix soldés, et un lien de modification par produit.
- Exporte le résultat en CSV.
- Interface en français et en anglais.

Le module ne modifie pas vos produits, n'appelle ni Google ni aucun autre service, et n'envoie aucune donnée. Un produit sans constat n'est pas validé pour autant par Google.

Compatibilité déclarée : PrestaShop 1.7.8 et versions suivantes. Licence AFL-3.0. Conçu par WeAdU.

## Limites à connaître

- Le prix vérifié est le prix de base hors taxes, sans déclinaison ni prix spécifique.
- La disponibilité suit la quantité en stock de la boutique courante.
- Le GTIN est lu dans l'EAN-13, puis l'UPC, puis l'ISBN.
