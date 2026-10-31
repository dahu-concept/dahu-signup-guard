# Dahu Signup Guard

Tient les inscriptions indésirables à l'écart, sans captcha.

Développé et maintenu par [Hugo Vial-Jaime — Dahu-Concept](https://dahu-concept.fr).

*La version anglaise de ce document est dans `README.md`. En cas d'écart, c'est
elle qui fait foi : c'est elle qui part sur wordpress.org.*

## L'idée

Ouvrez les inscriptions et les faux comptes arrivent. La réponse habituelle est
un captcha, qui demande à chaque visiteur réel de faire ses preuves parce que
quelques-uns ne sont pas des gens.

Cette extension ne demande rien au visiteur. Un champ qu'il ne voit jamais est
ajouté au formulaire ; une tentative qui le remplit est un robot. À côté, deux
listes de refus écartent les identifiants et les domaines d'e-mail dont vous ne
voulez pas.

## Fonctionnalités

- **Champ piège**, nommé différemment sur chaque site, pour qu'un robot écrit
  contre cette extension ne puisse pas le reconnaître et le contourner
- **Liste de refus sur l'identifiant** — texte simple, une entrée par ligne,
  recherchée n'importe où, sans tenir compte de la casse
- **Liste de refus sur l'e-mail** — pareil, sur l'adresse
- **Alias en « + »** — refuser au besoin `nom+nimportequoi@gmail.com`, seulement
  chez les fournisseurs qui les ramènent à une seule boîte
- **Journal** des 200 dernières tentatives refusées

S'applique au formulaire d'inscription de WordPress et à celui de WooCommerce
quand il est actif. WooCommerce n'est pas nécessaire.

## Prérequis

| | |
|---|---|
| WordPress | 6.0 ou plus |
| PHP | 7.4 ou plus |
| WooCommerce | facultatif |

## Adresses et vie privée

Le journal enregistre l'adresse du visiteur, **anonymisée par défaut** : le
dernier octet d'une adresse IPv4 et les 80 derniers bits d'une IPv6 sont mis à
zéro. De quoi reconnaître le réseau d'un récidiviste, pas de quoi désigner une
personne. Conserver l'adresse complète est un réglage qu'on active en
connaissance de cause.

Les en-têtes de proxy comme `X-Forwarded-For` ne sont **pas crus par défaut**.
Ils sont posés par le client, sauf si un proxy devant le site les réécrit : les
croire d'emblée, c'est permettre à n'importe qui d'écrire l'adresse de son choix
dans votre journal — pire que de n'avoir aucune adresse.

```php
// Faire confiance à un en-tête, seulement si un proxy que vous contrôlez le
// pose à chaque requête.
add_filter( 'dahu_signup_guard_trusted_ip_headers', function () {
    return array( 'HTTP_CF_CONNECTING_IP' );
} );
```

## Filtres

| Filtre | Rôle |
|---|---|
| `dahu_signup_guard_trusted_ip_headers` | Clés `$_SERVER` crues pour l'adresse du visiteur. Vide par défaut. |
| `dahu_signup_guard_plus_alias_domains` | Domaines sur lesquels un alias en `+` est refusé. Gmail et Googlemail par défaut. |

## Services externes

Aucun. Rien n'est envoyé nulle part ; tout ce qui est enregistré reste dans
votre propre base.

## Licence

MIT — voir [LICENSE](LICENSE).
