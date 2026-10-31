# RESUME TECHNIQUE — Dahu Signup Guard

Document interne, en français.

---

## Portage depuis la version Annad

### Version Annad d'origine

- Dépôt source Annad : `/Dev/dahu-security-connect/` (slug Annad : `dahu-security-connect`)
- Version de référence au moment du portage : `1.2.0`
- Date du portage : 2026-10-07
- Version publique de départ : `2.0.0` — majeure assumée, le périmètre change
  et le plugin ne dépend plus de WooCommerce.

### Décision structurante : une extension Annad, deux extensions publiques

`dahu-security-connect` contient **deux produits sans rapport** :

| Partie | Volume | Devient |
|---|---|---|
| Anti-spam d'inscription | ~380 lignes | **`dahu-signup-guard`** (cette extension) |
| Sauvegarde / restauration des rôles | ~1 300 lignes | **à extraire** en `dahu-role-backup` |

Le commentaire de la version Annad le dit lui-même : « Il est indépendant de la
partie anti-spam : aucune option, aucun hook et aucune donnée ne sont partagés
entre les deux. Seuls le fichier, le menu et le cycle de vie du plugin leur sont
communs. »

Les publier ensemble serait une erreur produit : qui cherche un anti-spam
d'inscription ne veut pas d'un système de sauvegarde de rôles qui écrit des
fichiers sur disque et planifie du cron quotidien, et inversement. Les
extensions fourre-tout sont par ailleurs mal vues en revue wordpress.org.

**La partie sauvegarde des rôles reste à porter.** Elle n'est pas dans ce
dépôt.

### Correspondance des identifiants

| Annad | Public |
|---|---|
| `dahu-security-connect` | `dahu-signup-guard` |
| `DAHU_SC_*` | `DAHU_SIGNUP_GUARD_*` |
| fonctions `dahu_sc_*` (procédural) | classes `Dahu_Signup_Guard_{Settings,Log,Honeypot,Rules}` |
| `dahu_sc_patterns_bloques` | `dahu_signup_guard_settings['username_patterns']` |
| `dahu_sc_email_patterns_bloques` | `dahu_signup_guard_settings['email_patterns']` |
| `dahu_sc_honeypot_actif` | `dahu_signup_guard_settings['honeypot']` |
| `dahu_sc_bloquer_plustag` | `dahu_signup_guard_settings['block_plus_aliases']` |
| `dahu_sc_journal` | `dahu_signup_guard_log` |
| champ piège `dahu_sc_champ_piege` | nom dérivé d'un sel propre au site |

Les cinq options séparées sont regroupées en un seul tableau d'options, ce qui
réduit les requêtes et simplifie l'assainissement.

### Corrigé pendant le portage — trois défauts réels

**1. Seul WooCommerce était protégé.** L'extension ne s'accrochait qu'à
`woocommerce_register_form` et `woocommerce_registration_errors`. Le formulaire
d'inscription natif de WordPress passait au travers. Les deux sont désormais
couverts (`register_form`, `registration_errors`), ce qui **supprime la
dépendance à WooCommerce** et élargit considérablement le public.

**2. Les en-têtes de proxy étaient crues sans réserve.** `dahu_sc_get_ip()`
lisait `HTTP_CF_CONNECTING_IP` puis `HTTP_X_FORWARDED_FOR` avant
`REMOTE_ADDR`. Or ces en-têtes sont posées par le client tant qu'un proxy ne
les écrase pas : n'importe qui pouvait donc écrire l'adresse de son choix dans
le journal, ce qui est pire que de ne pas en avoir. Par défaut, seul
`REMOTE_ADDR` est lu ; un site réellement derrière un proxy déclare son en-tête
par le filtre `dahu_signup_guard_trusted_ip_headers`.

**3. L'adresse IP complète était stockée sans le dire.** C'est une donnée
personnelle. Anonymisation par défaut (dernier octet IPv4, 80 derniers bits
IPv6 mis à zéro), option explicite pour l'adresse entière, et mention dans le
`README.txt`.

### Amélioré

**Nom du champ piège propre à chaque site.** Il était `dahu_sc_champ_piege`
partout. Un nom fixe est une signature : un robot écrit contre cette extension
le reconnaîtrait partout et le laisserait simplement vide. Le nom est maintenant
dérivé d'un sel généré une fois par site, et ressemble à un champ plausible
(`contact_xxxxxxxxxx`).

**Blocage des alias « + » restreint aux fournisseurs concernés.** La version
Annad visait `@gmail.com` en dur. Gmail et Googlemail replient
`nom+quoi@` sur `nom@`, ce qui permet à une seule boîte de s'inscrire
indéfiniment — mais ailleurs une adresse avec `+` est souvent une adresse
légitime et distincte. La liste est filtrable
(`dahu_signup_guard_plus_alias_domains`).

**Listes traitées comme du texte littéral**, jamais comme des expressions
régulières : une faute de frappe dans l'écran de réglages ne peut pas produire
une règle qui refuse tout le monde.

### Généralisé

| Élément | Avant | Après |
|---|---|---|
| Langue source | Français | Anglais, i18n complète |
| Licence | GPL v2 or later | MIT |
| Structure | Fichier unique de 63 Ko, procédural | 4 classes dans `includes/` |
| Liens d'action | Version en dur dans les liens | Version dans la ligne méta seule |
| Désinstallation | Aucune | `uninstall.php`, mono-site et multisite |

### Désinstallation : tout part

Contrairement aux autres extensions du lot, ici **tout est supprimé** à la
désinstallation. Deux listes, trois interrupteurs, un journal de tentatives
refusées et un sel : rien de cela n'est du contenu que le site regretterait.

---

## Reste à faire avant soumission

- [ ] **Extraire la sauvegarde des rôles** en `dahu-role-backup` (~1 300 lignes
      encore dans `/Dev/dahu-security-connect/`).
- [ ] Vérification fonctionnelle sur un site réel : inscription légitime,
      inscription piégée, inscription sur liste noire.
- [ ] Captures d'écran `screenshot-1..2.png`.
- [ ] Vérifier la disponibilité du slug `dahu-signup-guard` sur wordpress.org.
- [ ] Traductions françaises `.po`/`.mo`, pour qu'Annad retrouve son interface.

---

## Décisions d'architecture

- **Aucune dépendance.** Le plugin se charge sans WooCommerce ; il ajoute
  simplement ses accroches WooCommerce quand elles existent. C'est ce qui le
  rend publiable au-delà des boutiques.
- **Deux callbacks de validation distincts** plutôt qu'un seul à arguments
  optionnels : WordPress passe quatre arguments à `registration_errors`,
  WooCommerce trois à `woocommerce_registration_errors`. Les câbler séparément
  évite une signature ambiguë.
- **Journal dans une option non autoloadée**, plafonné à 200 entrées. Pas de
  table personnalisée pour si peu.
- **Le piège est caché hors écran**, pas en `display:none` que certains robots
  détectent, avec `aria-hidden` et `tabindex="-1"` pour que lecteur d'écran et
  clavier l'ignorent.

## Pièges connus

- Le sel du champ piège est généré **à la première lecture**, pas à
  l'activation : une extension désactivée puis réactivée garde donc son nom de
  champ. Changer le nom suffit à vider l'option `dahu_signup_guard_field_salt`.
- `was_tripped()` lit `$_POST` sans vérifier de nonce, volontairement : c'est
  le formulaire d'inscription que WordPress valide lui-même, et la valeur n'est
  testée que pour savoir si elle est vide. L'annotation PHPCS correspondante
  est en place.
