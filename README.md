# DahuConcept Signup Guard

Keeps spam registrations out without a captcha.

Developed and maintained by [Hugo Vial-Jaime — Dahu-Concept](https://dahu-concept.fr).

## The idea

Open registration and the fake accounts start arriving. The usual answer is a
captcha, which asks every real visitor to prove themselves because a few of them
are not people.

This plugin asks the visitor nothing. A field they never see is added to the
form; an attempt that fills it in is a bot. Alongside that, two blocklists refuse
the usernames and email domains you never want to see.

## Features

- **Honeypot**, named differently on every site so a bot written against this
  plugin cannot recognise and skip it
- **Username blocklist** — plain text, one entry per line, matched anywhere,
  ignoring case
- **Email blocklist** — same, on the address
- **Plus aliases** — optionally refuse `name+anything@gmail.com`, only on
  providers that collapse them to one mailbox
- **Log** of the last 200 refused attempts

Applies to the WordPress registration form and to WooCommerce's when it is
active. WooCommerce is not required.

## Requirements

| | |
|---|---|
| WordPress | 6.0 or later |
| PHP | 7.4 or later |
| WooCommerce | optional |

## Addresses and privacy

The log records the visitor's address, **anonymised by default**: the last octet
of an IPv4 address and the last 80 bits of an IPv6 one are zeroed. Enough to
recognise a repeat offender's network, not enough to single out a person.
Storing the full address is a setting you turn on knowingly.

Proxy headers such as `X-Forwarded-For` are **not trusted by default**. They are
set by the client unless a proxy in front of the site overwrites them, so
believing them out of the box means anyone can write whatever address they like
into your log — worse than having no address at all.

```php
// Trust a header, only if a proxy you control sets it on every request.
add_filter( 'ubac_signup_trusted_ip_headers', function () {
    return array( 'HTTP_CF_CONNECTING_IP' );
} );
```

## Filters

| Filter | Purpose |
|---|---|
| `ubac_signup_trusted_ip_headers` | `$_SERVER` keys trusted for the visitor's address. Empty by default. |
| `ubac_signup_plus_alias_domains` | Domains on which a `+` alias is refused. Gmail and Googlemail by default. |

## External services

None. Nothing is sent anywhere; everything recorded stays in your own database.

## License

MIT — see [LICENSE](LICENSE).
