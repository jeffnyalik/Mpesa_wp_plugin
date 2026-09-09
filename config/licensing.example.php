<?php
/**
 * Optional local overrides (gitignored as config/licensing.php).
 *
 * Freemius product id + public key live in plug-one.php (official snippet).
 * Put the Freemius SECRET key in wp-config.php only — see docs/licensing.md.
 *
 * Docs: leave PLUG_ONE_DOCS_URL empty to use the in-plugin /docs/*.md files
 * until you have a public website (GitHub Pages is fine later).
 *
 * @package Plug_One
 */

defined( 'ABSPATH' ) || exit;

define( 'PLUG_ONE_SUPPORT_EMAIL', 'jeffnyak@gmail.com' );
define( 'PLUG_ONE_SUPPORT_PHONE', '0716431039' );
define( 'PLUG_ONE_DOCS_URL', '' ); // empty = ship with plugin /docs
define( 'PLUG_ONE_PRICING_URL', '' ); // empty = Freemius upgrade URL
