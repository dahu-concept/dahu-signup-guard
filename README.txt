=== DahuConcept Signup Guard ===
Contributors: dahuconcept
Tags: registration spam, fake accounts, honeypot, bot signups, spam protection
Requires at least: 6.2
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 2.1.2
License: MIT
License URI: https://opensource.org/licenses/MIT

Keeps spam registrations out without a captcha: an invisible honeypot, blocklists for usernames and email addresses, and a log of what was refused.

== Description ==

Open registration on a WordPress or WooCommerce site and the fake accounts
start arriving. The usual answer is a captcha, which asks every real visitor to
prove themselves because a few of them are not people.

DahuConcept Signup Guard takes the opposite approach: nothing is asked of the visitor.
A field they never see is added to the form, and an attempt that fills it in is
a bot. Alongside that, two blocklists refuse the usernames and the email
domains you never want to see.

= What it does =

* **Honeypot.** An invisible field, named differently on every site so a bot
  written against this plugin cannot recognise and skip it.
* **Username blocklist.** Plain text, one entry per line, matched anywhere in
  the name, ignoring case.
* **Email blocklist.** Same, on the address — a domain such as `.example`
  blocks everything ending in it.
* **Plus aliases.** Optionally refuse `name+anything@gmail.com`, which lets one
  mailbox register endlessly. Only on providers that collapse them; elsewhere a
  plus address is often a legitimate, distinct one.
* **Log.** The last 200 refused attempts, with what was submitted and why it
  was refused.

= Where it applies =

The WordPress registration form, and the WooCommerce one when WooCommerce is
active. WooCommerce is not required.

= On addresses and privacy =

The log records the visitor's address, **anonymised by default**: the last
octet of an IPv4 address and the last 80 bits of an IPv6 one are zeroed. That
is enough to recognise a repeat offender's network and not enough to single out
a person. Storing the full address is a setting you turn on knowingly, and it
may require a mention in your privacy policy.

Proxy headers such as `X-Forwarded-For` are **not trusted by default**: they are
set by the client unless a proxy overwrites them, so believing them lets anyone
write any address into your log. A site genuinely behind a proxy declares its
header through a filter.

= For developers =

    // Trust a proxy header, only if a proxy you control sets it.
    add_filter( 'ubac_signup_trusted_ip_headers', function () {
        return array( 'HTTP_CF_CONNECTING_IP' );
    } );

    // Domains on which a "+" alias is refused.
    add_filter( 'ubac_signup_plus_alias_domains', function ( $domains ) {
        $domains[] = 'example.com';
        return $domains;
    } );

= External services =

This plugin does not contact any external service. Nothing is sent anywhere;
everything it records stays in your own database.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`, or install it from the
   Plugins screen.
2. Activate it through the Plugins screen.
3. Go to Settings > Signup Guard.

The honeypot is on from the first activation; the blocklists start empty.

== Frequently Asked Questions ==

= Does it require WooCommerce? =

No. It protects the WordPress registration form on its own, and also covers
WooCommerce's when WooCommerce is active.

= Will it block real customers? =

The honeypot only refuses a form where an invisible field was filled in, which
a person cannot do. The blocklists refuse exactly what you put in them — they
are plain text, not regular expressions, so a typo cannot turn into a rule that
refuses everybody.

= Does it store IP addresses? =

Anonymised by default — the network part only. Storing the full address is an
option you turn on deliberately.

= Does it send anything anywhere? =

No. There is no external service, no API key and no remote list.

== Screenshots ==

1. The settings screen.
2. The log of refused attempts.

== Changelog ==

= 2.1.2 =
* Changed: the compiled French catalogue now travels in the package. It was left out on the grounds that the directory serves language packs, which is true only for a copy installed from the directory and only once translations have been imported: a copy installed by hand showed English with a catalogue that was fully translated.
* Changed: the French readme moves under docs/, so the directory's checker no longer reports an unexpected markdown file at the plugin root.

= 2.1.1 =
* Changed: WordPress 6.2 is now the floor. Three extensions of the family need it for prepare()'s %i identifier placeholder, and the family states one floor rather than two because the welcome screen and the shared admin stylesheet are copied byte for byte across it.

= 2.1.0 =
* The activation screen now opens on the plugin's own mark rather than its initials, and the banner carries the same drawing as a watermark.

= 2.0.0 =
* First public release.
* Now covers the WordPress registration form, not only WooCommerce's;
  WooCommerce is no longer required.
* The honeypot field is named per site instead of being the same everywhere.
* IP addresses are anonymised by default, with an option for the full address.
* Proxy headers are no longer trusted unless declared through a filter.
* Plus-alias blocking is limited to providers that collapse them, and the list
  is filterable.

== Upgrade Notice ==

= 2.1.2 =
French now works on a copy installed by hand: the compiled catalogue travels in the package. The French readme moves under docs/.

= 2.1.1 =
WordPress 6.2 is now the floor, in step with the rest of the family. Nothing else changes.

= 2.0.0 =
First public release.
