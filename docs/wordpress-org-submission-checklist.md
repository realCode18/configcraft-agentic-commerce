# Checklist WordPress.org — DestinX AI Commerce

Questa checklist è un gate: una voce non verificata impedisce l'invio della 1.0.0.

## Identità e metadati

- [ ] Nome pubblico: DestinX AI Commerce for WooCommerce.
- [ ] Slug richiesto: `destinx-ai-commerce`.
- [ ] Directory del plugin: `destinx-ai-commerce`.
- [ ] File principale: `destinx-ai-commerce.php`.
- [ ] Text domain: `destinx-ai-commerce`.
- [ ] Il nome e lo slug iniziano con il nostro brand, non con il marchio WooCommerce.
- [ ] La formula `for WooCommerce` descrive la compatibilità senza suggerire proprietà di Automattic.
- [ ] `Plugin URI` è una pagina univoca dedicata al prodotto.
- [ ] `Author` e `Author URI` identificano DestinX.
- [ ] Versione header, costante, changelog e Stable tag coincidono.
- [ ] Header `Requires Plugins: woocommerce` presente.
- [ ] Header WordPress, PHP, `WC requires at least` e `WC tested up to` verificati.
- [ ] Nessun `Update URI` o updater esterno nella build WordPress.org.

## Licenze e sorgenti

- [ ] Plugin dichiarato GPL-2.0-or-later.
- [ ] Ogni file PHP, JavaScript, CSS, immagine, font e libreria è GPL-compatible.
- [ ] Nessun codice offuscato o nome deliberatamente illeggibile.
- [ ] Nessun JavaScript o CSS solo minificato senza sorgente pubblica.
- [ ] Repository GitHub pubblico e linkato dal readme.
- [ ] Istruzioni di build e strumenti di sviluppo documentati.
- [ ] `composer.json` incluso nel pacchetto oppure collegato in modo evidente dal readme.
- [ ] Nessuna libreria WordPress duplicata nel plugin.
- [ ] Nessuna libreria premium o con licenza incompatibile.

## Modello gratuito e commerciale

- [ ] Tutte le funzioni incluse nel plugin funzionano senza pagamento.
- [ ] Nessun limite a tempo, quota artificiale, trial o sandbox.
- [ ] Nessuna funzione locale disabilitata da una licenza.
- [ ] Nessun controllo licenza nell'MVP WordPress.org.
- [ ] Nessun codice premium incluso nella repository SVN.
- [ ] Nessun pulsante che installi o aggiorni codice da server esterni.
- [ ] Eventuali futuri servizi ConfigCraft forniranno funzionalità sostanziale e non sola validazione licenza.
- [ ] Eventuali upsell futuri saranno limitati alla pagina del plugin, non ingannevoli e non invasivi.

## Privacy e servizi esterni

- [ ] Nessuna richiesta HTTP esterna nell'MVP.
- [ ] Nessuna telemetria, activation ping, heartbeat remoto o click tracking.
- [ ] Nessun asset caricato da CDN, inclusi font e script.
- [ ] Nessun iframe remoto in amministrazione.
- [ ] Nessun dato prodotto inviato fuori dal sito.
- [ ] Nessun dato cliente, ordine, pagamento o utente letto o memorizzato.
- [ ] Il readme dichiara chiaramente che l'elaborazione è locale.
- [ ] Se in futuro viene aggiunto un servizio esterno: opt-in esplicito, circostanze, dati inviati, endpoint, Terms e Privacy Policy documentati.
- [ ] Nessun link o credito nel frontend; eventuali crediti futuri saranno opt-in e disattivati per default.

## Sicurezza

- [ ] Ogni azione amministrativa verifica una capability appropriata.
- [ ] I nonce non vengono usati come sostituti dell'autorizzazione.
- [ ] Ogni POST, GET, REQUEST e FILE viene letto solo per i campi necessari.
- [ ] Input sanitizzato e validato il prima possibile.
- [ ] Dati slashed passati attraverso `wp_unslash()` prima della sanitizzazione quando necessario.
- [ ] Output escaped nel contesto finale con `esc_html`, `esc_attr`, `esc_url` o `wp_kses` appropriato.
- [ ] Query SQL preparate con placeholder e nomi tabella controllati.
- [ ] Nessun accesso diretto non giustificato a tabelle WordPress/WooCommerce.
- [ ] Export CSV protetto contro formula injection (`=`, `+`, `-`, `@`, tab e carriage return iniziali).
- [ ] Endpoint admin-post restituiscono header corretti e terminano in sicurezza.
- [ ] Nessun path, stack trace o dettaglio database mostrato agli utenti.
- [ ] File PHP protetti da accesso diretto dove applicabile.
- [ ] Nessun uso di `eval`, `base64_decode` per codice, shell execution o remote code loading.

## Namespace e convivenza

- [ ] Namespace PHP univoco DestinX.
- [ ] Costanti globali con prefisso univoco.
- [ ] Option, transient, cron hook, Action Scheduler group e AJAX/admin action prefissati.
- [ ] Handle CSS/JS e classi CSS prefissati.
- [ ] Nessuna funzione o classe globale generica.
- [ ] Nessun uso di API WooCommerce `Internal` o `@internal`.
- [ ] Hook pubblici documentati e con nomi stabili.
- [ ] Il plugin non modifica globalmente stili o script di altre pagine admin.

## Database, coda e lifecycle

- [ ] Creazione e migrazione tabella idempotenti tramite API WordPress.
- [ ] Versione schema salvata e confrontata durante gli upgrade.
- [ ] Query indicizzate sui campi usati per filtri e ordinamento.
- [ ] Una sola scansione attiva per sito.
- [ ] Azioni duplicate rimosse o ignorate in sicurezza.
- [ ] Lock con scadenza e recupero scansione bloccata.
- [ ] Fallback WP-Cron testato senza Action Scheduler.
- [ ] Disattivazione non elimina i risultati ma interrompe attività non necessarie.
- [ ] Disinstallazione annulla code ed elimina tabella, opzioni e transient del plugin.
- [ ] Multisite per-site e network testati, incluso un sito creato dopo network activation.

## Interfaccia amministrativa

- [ ] Sottomenu sotto WooCommerce; nessun top-level menu non necessario.
- [ ] Nessun redirect inatteso dopo attivazione.
- [ ] Nessun avviso globale non indispensabile.
- [ ] Avvisi contestuali, risolvibili e auto-rimossi quando la causa scompare.
- [ ] Nessuna pubblicità invasiva.
- [ ] Colori non usati come unico mezzo per comunicare stato.
- [ ] Focus visibile e ordine di tab logico.
- [ ] Tabelle utilizzabili a zoom 200% e su schermi piccoli.
- [ ] Label e nomi accessibili per controlli, progress e navigazione.
- [ ] Test keyboard-only completato.
- [ ] Test contrasto completato.

## Internazionalizzazione

- [ ] Tutte le stringhe sorgente sono in inglese.
- [ ] Tutte le stringhe UI passano da funzioni i18n WordPress.
- [ ] Text domain identico allo slug.
- [ ] Placeholder documentati con commenti translators.
- [ ] Stringhe plurali usano `_n()` correttamente.
- [ ] Nessuna interpolazione che impedisca la traduzione.
- [ ] POT generato e verificato.
- [ ] Layout verificato con stringhe più lunghe.

## Compatibilità WooCommerce

- [ ] WooCommerce assente: nessun fatal error.
- [ ] WooCommerce inattivo: avviso appropriato e nessuna inizializzazione dipendente.
- [ ] Prodotti letti tramite API pubbliche `WC_Product`/CRUD.
- [ ] Simple, variable, virtual e downloadable testati.
- [ ] Brand nativo e global unique ID testati.
- [ ] HPOS verificato e compatibilità dichiarata.
- [ ] Cart e Checkout Blocks non vengono alterati.
- [ ] Product Editor classico e nuovo Product Editor verificati o limitazione documentata.
- [ ] WooCommerce latest e versione precedente supportata in CI o QIT.
- [ ] Nessuna query diretta alle tabelle ordini.

## `readme.txt`

- [ ] Formato passato nel validator ufficiale.
- [ ] Short description entro 150 caratteri.
- [ ] Massimo 5 tag pertinenti, senza nomi di concorrenti.
- [ ] Descrizione fattuale, non keyword-stuffed.
- [ ] Nessuna promessa di ranking, vendite o conformità.
- [ ] Requisiti e dipendenza WooCommerce chiari.
- [ ] Installazione e uso descritti in pochi passaggi.
- [ ] FAQ su dati, servizi esterni, limiti e numero prodotti.
- [ ] Privacy locale esplicitata.
- [ ] Changelog 1.0.0 completo.
- [ ] Stable tag numerico e corrispondente alla cartella SVN.
- [ ] Plugin URI univoca.
- [ ] Link a supporto, sorgente, privacy e termini solo se pertinenti.
- [ ] Descrizioni screenshot corrispondenti agli asset reali.

## Pacchetto di distribuzione

- [ ] Cartella radice unica `destinx-ai-commerce/`.
- [ ] Nessuna cartella `.git`, `.github`, test, cache, log, coverage o file IDE.
- [ ] Nessun segreto, token, credenziale o URL di staging.
- [ ] Nessuna dipendenza di sviluppo o vendor inutile.
- [ ] Sono presenti main file, includes runtime, asset locali, `readme.txt`, licenza, uninstall e sorgenti/build metadata richiesti.
- [ ] Archivio estratto e confrontato con il tag GitHub.
- [ ] Installazione dello ZIP verificata da Plugins > Add New.
- [ ] Plugin Check eseguito sul pacchetto estratto, non solo sulla working tree.
- [ ] Hash SHA-256 salvato nelle note di release.

## Test automatici finali

- [ ] `composer validate --strict`.
- [ ] Lint PHP di ogni file runtime e test.
- [ ] WordPress Coding Standards senza errori.
- [ ] PHPCompatibilityWP per PHP 7.4+.
- [ ] PHPUnit completo.
- [ ] WordPress Playground con WooCommerce.
- [ ] Plugin Check via WP-CLI.
- [ ] CI PHP 7.4, 8.1 e 8.4.
- [ ] CI WordPress 6.6 e latest.
- [ ] CI WooCommerce latest e precedente supportata.
- [ ] Test integrità ZIP.

## Test manuali finali

- [ ] Installazione pulita.
- [ ] Aggiornamento dalla pre-release più recente.
- [ ] WooCommerce non attivo.
- [ ] Catalogo vuoto.
- [ ] Catalogo reale.
- [ ] Scansione interrotta e ripresa.
- [ ] Seconda scansione dopo modifiche prodotto.
- [ ] Filtri, ricerca e paginazione.
- [ ] Export CSV aperto in Excel/LibreOffice.
- [ ] Pannello prodotto e guida.
- [ ] Responsive e accessibilità.
- [ ] WP_DEBUG e log WooCommerce puliti.
- [ ] Disattivazione e riattivazione.
- [ ] Disinstallazione con verifica database e coda.

## Asset WordPress.org

- [ ] Icona 128x128 PNG.
- [ ] Icona 256x256 PNG.
- [ ] Banner 772x250 PNG/JPG.
- [ ] Banner 1544x500 PNG/JPG.
- [ ] Screenshot 1: dashboard completa.
- [ ] Screenshot 2: problemi filtrati.
- [ ] Screenshot 3: pannello prodotto.
- [ ] Screenshot 4: checklist negozio.
- [ ] Tutti gli asset originali o con licenza GPL-compatible documentata.
- [ ] Nessun logo di terzi usato in modo fuorviante.

## Invio e revisione

- [ ] Account WordPress.org con email valida e controllata.
- [ ] `plugins@wordpress.org` in whitelist.
- [ ] Slug ricontrollato immediatamente prima dell'invio.
- [ ] ZIP completo, non una demo o placeholder.
- [ ] Descrizione di invio breve: scopo, funzionamento locale e dipendenza WooCommerce.
- [ ] Nessun invio duplicato durante l'attesa.
- [ ] Email del review team gestite nella stessa conversazione e con risposte puntuali.
- [ ] Ogni correzione richiesta applicata sia su GitHub sia nel nuovo ZIP.
- [ ] Dopo approvazione, SVN configurato con `trunk`, `tags/1.0.0` e `assets`.
- [ ] Commit SVN limitati a release complete e messaggi descrittivi.
- [ ] Release confirmation email verificata.

## Decisioni permanenti per la 1.0.0

- Nessuna telemetria.
- Nessun servizio esterno.
- Nessun account obbligatorio.
- Nessuna licenza.
- Nessuna funzione premium nel pacchetto.
- Nessun updater esterno.
- Nessun asset remoto.
- Nessuna modifica automatica dei prodotti.
- Nessun dato cliente o ordine.
- Nessuna promessa di indicizzazione, ranking, vendite o conformità.
- Nessun credito nel frontend.

## Fonti ufficiali

- [WordPress.org Detailed Plugin Guidelines](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/)
- [Common issues](https://developer.wordpress.org/plugins/wordpress-org/common-issues/)
- [Plugin Readmes](https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/)
- [Planning and submitting plugins](https://developer.wordpress.org/plugins/wordpress-org/planning-submitting-and-maintaining-plugins/)
- [Header requirements](https://developer.wordpress.org/plugins/plugin-basics/header-requirements/)
- [WordPress Security](https://developer.wordpress.org/apis/security/)
- [Plugin Check](https://wordpress.org/plugins/plugin-check/)
- [WooCommerce extension best practices](https://developer.woocommerce.com/docs/extensions/best-practices-extensions/extension-development-best-practices/)
- [WooCommerce compatibility](https://developer.woocommerce.com/docs/extensions/best-practices-extensions/compatibility)

