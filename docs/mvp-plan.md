# Piano MVP — DestinX AI Commerce for WooCommerce

## Scheda del prodotto

| Voce | Decisione |
| --- | --- |
| Nome pubblico | DestinX AI Commerce for WooCommerce |
| Nome breve | DestinX AI Commerce |
| Slug WordPress.org | `destinx-ai-commerce` |
| Text domain | `destinx-ai-commerce` |
| Editore | DestinX |
| Dipendenza | WooCommerce |
| Licenza | GPL-2.0-or-later |
| Versione obiettivo | 1.0.0 |
| Inizio esecuzione | 23 agosto 2026 |
| Release candidate | 8 settembre 2026 |
| Invio previsto a WordPress.org | 9 settembre 2026 |
| Tempo di sviluppo attivo stimato | 14 giorni di lavoro, incluso il rebranding già completato |
| Revisione WordPress.org | Fino a 14 giorni lavorativi, esterni al nostro controllo |

Le tempistiche presuppongono che non emergano incompatibilità bloccanti su un catalogo reale. Il tempo di revisione ufficiale parte dopo l'invio e può richiedere uno o più cicli di correzione.

## Decisione strategica

Costruiamo un solo prodotto: un plugin WooCommerce che misura quanto catalogo e configurazione del negozio siano chiari, completi e utilizzabili dai sistemi automatici di ricerca e shopping.

La versione 1.0.0 sarà:

- utile e completa senza pagamento;
- completamente locale;
- priva di telemetria;
- priva di chiamate a servizi esterni;
- priva di funzioni bloccate da licenza;
- diagnostica e non distruttiva;
- focalizzata sulla qualità dei dati, senza promettere indicizzazione o vendite.

ConfigCraft Suite non farà parte del flusso obbligatorio dell'MVP. Potrà essere collegata in futuro come servizio opzionale reale per monitoraggio multi-store, storico remoto e reportistica, con consenso esplicito, termini e privacy documentati. Non verrà usata come semplice validatore di licenze per bloccare funzioni già presenti nel plugin.

### Decisione Free + Pro

La versione Free non è una demo e non è un contenitore per funzioni bloccate. È il plugin principale, possiede il motore di normalizzazione, le regole, le scansioni, il repository dei risultati e l'interfaccia di diagnosi. Deve poter essere installata e usata per intero senza Pro, account, chiave o ConfigCraft.

La futura versione Pro sarà un add-on separato, installato separatamente e dipendente dalla Free. Userà contratti pubblici del core per aggiungere regole, workflow, AI, protocolli avanzati e servizi multi-store; non duplicherà classi, scansioni o tabelle del motore. Se il Pro è assente, incompatibile, senza licenza o offline, tutte le funzioni Free continuano a lavorare.

Il plugin WordPress.org non conterrà codice premium disabilitato, non convaliderà licenze, non scaricherà add-on e non userà un updater esterno. Questa separazione segue la raccomandazione della linea guida WordPress.org sul trialware, che indica add-on esterni per escludere il codice premium.

## Proposta di valore

In pochi minuti il merchant deve poter rispondere a tre domande:

1. Il mio catalogo contiene i dati necessari per essere compreso correttamente?
2. Quali prodotti hanno i problemi più importanti?
3. Cosa devo correggere concretamente dentro WooCommerce?

Il plugin non afferma che un prodotto verrà mostrato, indicizzato o consigliato da ChatGPT, Gemini o altri servizi. Misura condizioni tecniche e qualità dei dati che possono facilitare l'interpretazione automatica.

## Utenti principali

### Proprietario di un negozio

Vuole una valutazione semplice, un elenco ordinato delle priorità e istruzioni comprensibili senza conoscere feed, schema o protocolli.

### E-commerce manager

Vuole filtrare i problemi, correggere gruppi di prodotti e rieseguire la scansione per misurare il miglioramento.

### Agenzia

Vuole una diagnosi ripetibile, esportabile e coerente fra cataloghi diversi. La gestione multi-store centralizzata resta post-MVP.

## Stato di partenza

La pre-release 0.2.0 ha già completato circa il 55% del nucleo tecnico:

- plugin installabile con dipendenza WooCommerce;
- valutatore deterministico dei prodotti;
- scansione rapida degli ultimi 25 prodotti;
- scansione completa a lotti tramite Action Scheduler o WP-Cron;
- persistenza locale dei risultati;
- stato di avanzamento e aggiornamento automatico della dashboard;
- risultati paginati con punteggio più basso per primo;
- pannello nel prodotto con guida di correzione;
- disinstallazione dei dati locali;
- PHPUnit, WordPress Coding Standards e smoke test WordPress/WooCommerce;
- CI su PHP 7.4, 8.1 e 8.4.

Le fasi di robustezza, diagnosi del negozio, centro operativo e conformità sono ora completate. Prima della 1.0.0 restano la matrice estesa di qualità e compatibilità, Plugin Check finale, asset, beta su staging e procedura WordPress.org.

## Perimetro funzionale 1.0.0

### 1. Attivazione e dipendenze

- Il plugin si attiva solo su una versione supportata di WordPress e PHP.
- WooCommerce viene dichiarato con `Requires Plugins: woocommerce`.
- Se WooCommerce non è disponibile, nessun servizio dipendente viene avviato e viene mostrato un solo avviso contestuale.
- L'attivazione crea o aggiorna lo schema senza perdere dati validi.
- Disattivazione e disinstallazione annullano le azioni pianificate; la disinstallazione rimuove tabella e opzioni proprietarie.
- Il plugin non crea account, non apre redirect promozionali e non invia dati.

### 2. Scansione del catalogo

- Anteprima immediata degli ultimi 25 prodotti pubblicati.
- Scansione completa manuale di tutti i prodotti pubblicati.
- Lotti predefiniti da 25 prodotti, filtrabili fra 5 e 100.
- Action Scheduler come motore principale e singoli eventi WP-Cron come fallback.
- Una sola scansione attiva per sito.
- Deduplicazione delle azioni, recupero delle scansioni rimaste ferme e possibilità di riprovare dopo un errore.
- La scansione precedente resta visibile finché quella nuova non è completata.
- I nuovi risultati diventano attivi in modo atomico a fine scansione.
- Prodotti eliminati o non più pubblicati non rimangono nei risultati attivi.
- Nessun catalogo completo viene caricato in memoria.

### 3. Controlli a livello prodotto

| Area | Controllo MVP | Esito richiesto |
| --- | --- | --- |
| Identità | Titolo specifico e sufficientemente descrittivo | Segnalazione se troppo corto |
| Contenuto | Descrizione principale non vuota e sufficientemente completa | Segnalazione con guida editoriale |
| Prezzo | Prezzo valido sul prodotto o sulle varianti acquistabili | Problema ad alta priorità se assente |
| Immagine | Immagine in evidenza disponibile | Problema medio se assente |
| Tassonomia | Categoria prodotto specifica | Problema medio se assente |
| Brand | Brand nativo WooCommerce, tassonomia, attributo o metadato supportato | Problema medio se assente |
| Identificatore | GTIN, EAN, UPC, ISBN o identificatore globale | Problema medio se assente quando applicabile |
| SKU | SKU stabile | Problema basso se assente |
| Attributi | Presenza di attributi strutturati | Problema basso o medio secondo il tipo prodotto |
| Spedizione | Peso oppure dimensioni complete per prodotti fisici | Problema basso se entrambi assenti |
| Variabili | Almeno una variante acquistabile | Problema alto se nessuna variante valida |
| Varianti | Prezzo, attributi e disponibilità delle varianti | Riepilogo dei difetti sul prodotto padre |
| Acquistabilità | Prodotto pubblicato ma non acquistabile per configurazione incompleta | Problema alto |
| Disponibilità | Stato scorte riconoscibile | Informazione contestuale, senza penalizzare una normale indisponibilità temporanea |

I prodotti virtuali e scaricabili non ricevono segnalazioni relative a peso o dimensioni. Le regole devono poter essere estese tramite filtri pubblici documentati.

### 4. Modello di punteggio

Il punteggio resta deterministico e spiegabile:

- base: 100;
- ogni problema contiene `code`, `severity` e `penalty`;
- il punteggio finale è limitato fra 0 e 100;
- `Ready`: 80–100 e nessun problema ad alta severità;
- `Needs work`: 50–79, oppure 80–100 con almeno un problema ad alta severità;
- `At risk`: 0–49.

La pre-release 0.4.0 introduce e congela il modello `1.0.0`. Ogni futura variazione sostanziale dei pesi incrementerà la versione del modello e verrà dichiarata nel changelog. Il pannello deve sempre spiegare quali penalità hanno prodotto il risultato; non useremo un punteggio opaco generato da AI.

### 5. Controlli a livello negozio

La dashboard includerà una checklist separata dal punteggio medio dei prodotti:

- sito servito in HTTPS;
- visibilità ai motori di ricerca non disabilitata;
- permalink non impostati su Plain;
- paese, indirizzo e valuta WooCommerce configurati;
- pagine carrello, checkout e account assegnate e pubblicate;
- pagina privacy configurata;
- pagina termini e condizioni configurata;
- indicazione chiara della policy resi/rimborsi o avviso che deve essere verificata;
- almeno un metodo/zona di spedizione per cataloghi con prodotti fisici;
- WooCommerce REST API e normali endpoint WordPress non disabilitati da una configurazione evidente;
- presenza di prodotti pubblicati e visibili nel catalogo.

Questi controlli sono diagnostici. Il plugin non dichiara né garantisce conformità legale.

### 6. Dashboard operativa

- Punteggio medio del catalogo.
- Totale scansionato e totale pubblicato.
- Conteggi Ready, Needs work e At risk.
- Stato e avanzamento della scansione.
- Data e ora dell'ultima scansione completata.
- Tabella ordinata per punteggio crescente.
- Ricerca per nome o SKU.
- Filtri per stato, tipo di problema e categoria.
- Paginazione server-side.
- Link diretto alla modifica del prodotto.
- Testo di correzione per ogni problema.
- Stato vuoto, errore e recupero comprensibili.
- Nessun banner pubblicitario o avviso globale persistente.

### 7. Pannello nel prodotto

- Punteggio calcolato sui dati correnti, anche prima di una nuova scansione completa.
- Stato leggibile oltre al colore.
- Elenco espandibile dei problemi.
- Istruzione concreta per ogni correzione.
- Link alla dashboard generale.
- Nessuna modifica automatica del prodotto.

### 8. Export CSV

- Export dell'ultima scansione completata.
- Colonne: ID, nome, SKU, tipo, punteggio, stato, codici problema, severità, data scansione e URL di modifica.
- Generazione in streaming senza salvare file pubblici sul server.
- Controllo capability `manage_woocommerce` e nonce dedicato.
- Valori compatibili con Excel e protetti da CSV formula injection.

### 9. Privacy e dati

- Nessun dato cliente, ordine, utente o pagamento viene letto o salvato.
- Vengono letti soltanto configurazione del negozio e dati di prodotti pubblicati.
- Risultati e stato scansione restano nel database WordPress locale.
- Nessuna richiesta HTTP esterna, nessun font remoto e nessun asset CDN.
- Nessun cookie aggiunto.
- Nessuna telemetria, ping di attivazione o tracciamento dei clic.
- La disinstallazione cancella tutti i dati proprietari.

## Flussi utente obbligatori

### Prima apertura

1. Il merchant attiva il plugin.
2. Apre WooCommerce > AI Commerce.
3. Vede l'anteprima, una spiegazione sintetica e il pulsante per la scansione completa.
4. Nessun wizard o account è obbligatorio.

### Scansione completa

1. Il merchant seleziona Scan full catalog.
2. Capability e nonce vengono verificati.
3. La coda elabora il catalogo a lotti.
4. La dashboard aggiorna l'avanzamento senza bloccare la richiesta web.
5. A completamento, la nuova scansione sostituisce quella precedente.
6. I problemi più gravi vengono mostrati per primi.

### Correzione

1. Il merchant filtra un problema o apre un prodotto dalla tabella.
2. Il pannello laterale spiega il difetto.
3. Il merchant corregge manualmente il prodotto e lo salva.
4. Il punteggio del pannello viene ricalcolato immediatamente.
5. Una nuova scansione aggiorna il riepilogo del catalogo.

### Export

1. Il merchant esporta l'ultima scansione completata.
2. Il server ricontrolla capability e nonce.
3. Il CSV viene scaricato direttamente e non resta sul server.

## Architettura prevista

### Moduli

- `Plugin`: bootstrap e registrazione dei servizi.
- `Database`: installazione, migrazione e rimozione schema.
- `Product_Data_Extractor`: normalizzazione degli oggetti `WC_Product` usando solo API pubbliche.
- `Product_Readiness_Evaluator`: regole pure e versionate.
- `Store_Readiness_Evaluator`: controlli di configurazione del negozio.
- `Catalog_Auditor`: anteprima non persistente.
- `Background_Audit`: orchestrazione e lock della scansione.
- `Audit_Repository`: scrittura e lettura paginata.
- `Issue_Catalog`: etichette, severità e rimedi traducibili.
- `Admin_Page`: dashboard, filtri e stati.
- `Product_Meta_Box`: diagnosi sul singolo prodotto.
- `Csv_Exporter`: export autorizzato e sicuro.

### Persistenza

La tabella proprietaria memorizzerà soltanto risultati di audit:

- `scan_id`;
- `product_id`;
- `score`;
- `status`;
- `issues` serializzati in JSON;
- `product_hash` per rilevare cambiamenti;
- `scanned_at` in UTC.

La chiave sarà composta da scansione e prodotto. Un'opzione manterrà ID della scansione attiva, stato, conteggi, timestamp ed eventuale errore. La scansione precedente verrà eliminata soltanto dopo il completamento della nuova, evitando dashboard parziali.

### Confini tecnici

- Nessun accesso diretto alle tabelle ordini.
- Nessuna classe WooCommerce `Internal` o annotata `@internal`.
- CRUD WooCommerce per prodotti e configurazione pubblica per il negozio.
- Query SQL preparate e nomi tabella controllati.
- Namespace, costanti, hook, option e handle con prefisso DestinX univoco.
- UI costruita con componenti e pattern WordPress/WooCommerce; JavaScript minimo e locale.

## Budget di prestazioni

- Dimensione lotto predefinita: 25 prodotti.
- Limite massimo configurabile tramite filtro: 100 prodotti.
- Risultati per pagina: 20.
- Nessuna query che carichi tutti gli ID o tutti gli oggetti prodotto contemporaneamente.
- Nessuna chiamata esterna durante attivazione, scansione o rendering.
- Ogni lotto deve terminare entro 10 secondi sull'ambiente di riferimento.
- Memoria del processo sotto 128 MB nell'ambiente di riferimento.
- Apertura dashboard senza scansione attiva entro 1,5 secondi sull'ambiente di riferimento, escluso bootstrap WordPress/WooCommerce.

Questi sono budget di progetto, non promesse assolute su hosting di terze parti.

## Compatibilità obiettivo

- WordPress 6.6 fino alla versione stabile più recente testata.
- PHP 7.4, 8.1 e 8.4.
- WooCommerce: ultima versione stabile e precedente major supportata.
- Prodotti semplici, variabili, virtuali e scaricabili.
- WooCommerce Brands nativo e fallback documentati.
- HPOS dichiarato compatibile perché il plugin non accede direttamente agli ordini.
- Cart e Checkout Blocks: nessuna interferenza.
- Multisite: attivazione per singolo sito obbligatoria; attivazione network e creazione di nuovi siti devono essere testate e supportate prima della 1.0.0.
- Interfaccia responsive e navigabile da tastiera.

## Piano temporale

| Fase | Date | Durata | Risultato |
| --- | --- | ---: | --- |
| 0. Identità definitiva | 23 agosto | Completata | Rebranding completo DestinX e slug congelato |
| 1. Hardening del nucleo | 24–25 agosto | 2 giorni | Scansione atomica, lock, retry e migrazioni |
| 2. Store readiness | 26–27 agosto | 2 giorni | Checklist negozio con rimedi |
| 3. Centro operativo | 28 e 31 agosto | 2 giorni | Filtri, ricerca, export e UX completa |
| 4. Conformità | 1–2 settembre | 2 giorni | Sicurezza, privacy, i18n, accessibilità e multisite |
| 5. Qualità e compatibilità | 3–4 settembre | 2 giorni | Matrice test, Plugin Check e performance |
| 6. Beta reale | 7–8 settembre | 2 giorni | Test manuale su staging e release candidate |
| 7. Pacchetto e invio | 9 settembre | 1 giorno | ZIP finale e domanda WordPress.org |
| Revisione esterna | 10–29 settembre | Fino a 14 giorni lavorativi | Risposta del Plugin Review Team |

## Dettaglio delle fasi e gate

### Fase 0 — Identità definitiva

Attività:

- rinominare plugin, file principale, directory, text domain e pacchetto;
- rinominare namespace, costanti, hook, action, option, handle CSS/JS e prefissi UI;
- migrare o rimuovere in sicurezza i dati delle sole pre-release ConfigCraft;
- rinominare repository GitHub e aggiornare URL;
- fissare lo slug `destinx-ai-commerce` prima dell'invio;
- portare la versione di sviluppo a 0.3.0.

Gate: nessuna occorrenza pubblica obsoleta del precedente nome ConfigCraft Agentic Commerce; suite precedente ancora verde.

### Fase 1 — Hardening del nucleo

Stato: completata in anticipo il 23 agosto 2026 nella pre-release 0.4.0.

Attività:

- introdurre `scan_id` e pubblicazione atomica dei risultati;
- deduplicare le azioni Action Scheduler/WP-Cron;
- lock con scadenza e recupero delle scansioni ferme;
- gestire prodotti modificati, eliminati o depubblicati durante una scansione;
- controllare gli errori di scrittura e mostrare un rimedio;
- completare regole per varianti e acquistabilità;
- documentare e congelare il modello punteggio 1;
- migrazioni di schema idempotenti.

Gate: scansioni consecutive e interrotte non producono duplicati, risultati parziali o stato bloccato.

### Fase 2 — Store readiness

Stato: completata in anticipo il 23 agosto 2026 nella pre-release 0.5.0.

Attività:

- implementare valutatore del negozio indipendente;
- aggiungere controlli HTTPS, indicizzazione, permalink, pagine WooCommerce, policy e spedizione;
- distinguere pass, warning e fail;
- associare a ogni controllo un rimedio concreto;
- testare negozi digital-only e negozi con prodotti fisici.

Gate: fixture automatizzate coprono almeno un negozio pronto, uno incompleto e uno solo digitale.

### Fase 3 — Centro operativo

Stato: completata in anticipo il 23 agosto 2026 nella pre-release 0.6.0.

Attività:

- ricerca prodotto e SKU;
- filtri stato, problema e categoria;
- conteggi coerenti con i filtri;
- export CSV sicuro;
- ultima scansione e stato dati;
- stati vuoti, fallimento, retry e nessun prodotto;
- rifinitura del pannello prodotto.

Gate: un merchant può individuare, aprire ed esportare tutti i prodotti con uno specifico problema senza leggere documentazione esterna.

### Fase 4 — Conformità

Stato: completata in anticipo il 23 agosto 2026 nella pre-release 0.7.0.

Attività:

- revisione capability, nonce, sanitizzazione, validazione ed escaping;
- revisione query e schema;
- verifica assenza richieste esterne, tracking, remote asset e updater;
- stringhe inglesi traducibili e generazione POT;
- controlli keyboard, focus, contrasto, label e screen-reader text;
- supporto multisite e cleanup per-site/network;
- dichiarazione HPOS e intestazioni WooCommerce;
- verifica licenze di ogni file e asset.

Gate: nessun problema di sicurezza noto, nessuna stringa UI non traducibile, nessuna chiamata esterna non documentata.

### Fase 5 — Qualità e compatibilità

Stato: completata in anticipo il 23 agosto 2026 nella pre-release 0.8.0. La matrice e le misure riproducibili sono registrate in [test-matrix.md](test-matrix.md).

Attività:

- ampliare unit test delle regole;
- integration test su database, scansioni multiple ed export;
- cataloghi sintetici da 0, 1, 26, 500 e almeno 5.000 prodotti;
- WordPress minimo e latest;
- WooCommerce latest e precedente;
- PHP 7.4, 8.1 e 8.4;
- multisite e network activation;
- WP_DEBUG e Query Monitor senza warning o query anomale;
- PHPCS, PHPUnit, Plugin Check e verifica del readme;
- installazione, aggiornamento, disattivazione e disinstallazione.

Gate: CI verde, Plugin Check senza errori e warning giustificati uno per uno.

### Fase 6 — Beta reale

Qui è richiesto l'intervento del proprietario:

- installare la release candidate su staging;
- eseguire una scansione su catalogo reale;
- controllare visivamente dashboard e pannello prodotto;
- verificare che i rimedi siano comprensibili;
- esportare e aprire il CSV;
- testare una nuova scansione dopo aver corretto almeno tre prodotti;
- verificare il log PHP e Action Scheduler.

Gate: nessun errore bloccante e approvazione del comportamento reale. Le preferenze estetiche non bloccanti possono entrare nella 1.0.1.

### Fase 7 — Pacchetto e invio

Attività:

- header e `readme.txt` definitivi;
- short description entro 150 caratteri e massimo 5 tag;
- icone 128x128 e 256x256;
- banner 772x250 e 1544x500;
- screenshot numerati e descritti;
- link al repository con sorgenti e strumenti di sviluppo;
- changelog 1.0.0;
- ZIP pulito e installabile;
- esecuzione finale Plugin Check sullo ZIP;
- testo breve e fattuale per la domanda;
- invio con account WordPress.org e email monitorata;
- preparazione struttura SVN `trunk`, `tags/1.0.0` e `assets`.

Gate: il contenuto dello ZIP coincide con il tag GitHub e supera tutti i controlli.

## Matrice di test minima

| Area | Casi obbligatori |
| --- | --- |
| PHP | 7.4, 8.1, 8.4 |
| WordPress | 6.6, latest |
| WooCommerce | latest, precedente supportata |
| Catalogo | 0, 1, 26, 500, 5.000 prodotti |
| Tipi prodotto | simple, variable, virtual, downloadable |
| Scansione | completa, seconda scansione, errore DB, coda interrotta, prodotto eliminato |
| Permessi | administrator, shop manager, utente senza `manage_woocommerce` |
| Sicurezza | nonce assente/errato, parametri alterati, CSV injection, SQL injection |
| Lifecycle | prima installazione, upgrade, deactivate/reactivate, uninstall |
| Multisite | per-site, network activation, nuovo sito |
| UI | desktop, tablet, mobile, keyboard-only, zoom 200% |
| Ambiente | WP_DEBUG attivo, cron disponibile e fallback cron |

## Metriche di successo del rilascio

- 100% delle scansioni di test termina o mostra un errore recuperabile.
- Zero fatal error, warning o notice del plugin con WP_DEBUG.
- Zero errori Plugin Check.
- Zero richieste HTTP esterne rilevate nell'MVP.
- Zero dati cliente/ordine persistiti.
- 100% delle azioni mutative protette da capability e nonce.
- Tutte le stringhe dell'interfaccia traducibili.
- Pacchetto installabile senza file di test, cache o credenziali.
- Un utente non tecnico riesce a trovare e correggere almeno tre problemi senza assistenza.

## Definition of Done della 1.0.0

La versione è pronta per WordPress.org solo quando:

- nome, slug, main file e text domain coincidono;
- WooCommerce è dichiarato e gestito come dipendenza;
- il plugin offre valore completo senza account o pagamento;
- scansione prodotto, checklist negozio, filtri, pannello ed export funzionano;
- nessuna scansione può restare bloccata in modo permanente;
- i risultati attivi non sono mai parziali;
- privacy e comportamento locale sono spiegati nel readme;
- non esistono telemetria, asset remoti, updater esterni o codice premium;
- tutte le licenze sono GPL-compatible;
- security review e matrice test sono completate;
- Plugin Check, PHPCS, PHPUnit e Playground sono verdi;
- accessibilità e responsive design sono verificati manualmente;
- installazione, aggiornamento e disinstallazione sono testati;
- readme, screenshot, icone e banner sono pronti;
- ZIP finale riscaricato e confrontato con la release GitHub;
- account WordPress.org ed email di revisione sono disponibili.

## Fuori dall'MVP

- correzione automatica o bulk dei prodotti;
- generazione automatica di descrizioni con AI;
- connessione obbligatoria a ConfigCraft Suite;
- licenze o piani premium nel plugin WordPress.org;
- telemetria o analytics remoti;
- dashboard multi-store;
- storico remoto e alert email;
- feed ACP, UCP, MCP o checkout agentico;
- garanzie di indicizzazione, ranking, vendita o conformità legale;
- lettura di clienti, ordini, pagamenti o dati personali.

Questi elementi verranno valutati soltanto dopo approvazione, stabilità e feedback della versione 1.0.0.

Le funzioni locali che completano il motore — correzioni manuali sicure, storico locale limitato, confronto prima/dopo, esclusioni, `llms.txt`, catalogo JSON di sola lettura e anteprima agente — sono candidate alle versioni Free 1.1–1.2. AI, bulk avanzato, protocolli transazionali, analytics, alert e multi-store appartengono invece all'add-on Pro o al servizio ConfigCraft.

La matrice dei concorrenti e la motivazione di questa ripartizione sono in [competitive-analysis.md](competitive-analysis.md). Il contratto tecnico fra i due plugin è in [free-pro-architecture.md](free-pro-architecture.md).

## Rischi e mitigazioni

| Rischio | Mitigazione |
| --- | --- |
| Slug rifiutato o contestato | Slug con brand proprietario DestinX e verifica prima dell'invio |
| Cataloghi grandi bloccano il server | Lotti limitati, lock, retry e nessun caricamento completo |
| Risultati incoerenti durante la scansione | Doppio buffer tramite `scan_id` e switch atomico |
| Differenze fra plugin brand/GTIN | Adapter e filtri documentati, test con implementazioni comuni |
| WP-Cron disabilitato | Action Scheduler principale, fallback e messaggio diagnostico |
| Revisione WordPress.org segnala violazioni | Checklist dedicata e Plugin Check prima di ogni pacchetto |
| Nome troppo promozionale | Claim limitati a diagnosi tecnica, nessuna promessa di visibilità |
| Future funzioni SaaS violano le regole | Servizio sostanziale, opt-in, disclosure completa e audit separato |

## Riferimenti ufficiali

- [Detailed Plugin Guidelines](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/)
- [Common issues found during plugin review](https://developer.wordpress.org/plugins/wordpress-org/common-issues/)
- [Planning, Submitting, and Maintaining Plugins](https://developer.wordpress.org/plugins/wordpress-org/planning-submitting-and-maintaining-plugins/)
- [Plugin Readmes](https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/)
- [Plugin Header Requirements](https://developer.wordpress.org/plugins/plugin-basics/header-requirements/)
- [WordPress Security](https://developer.wordpress.org/apis/security/)
- [WooCommerce extension development best practices](https://developer.woocommerce.com/docs/extensions/best-practices-extensions/extension-development-best-practices/)
- [WooCommerce compatibility and interoperability](https://developer.woocommerce.com/docs/extensions/best-practices-extensions/compatibility)
- [Plugin Check](https://wordpress.org/plugins/plugin-check/)
