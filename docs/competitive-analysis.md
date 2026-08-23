# Analisi competitiva — WooCommerce AI readiness e agentic commerce

Analisi aggiornata al 23 agosto 2026. Le informazioni derivano dalle schede pubbliche dei plugin e dai relativi changelog; non è stato copiato codice, testo commerciale, design o materiale proprietario. I prodotti concorrenti sono usati soltanto per individuare bisogni ricorrenti, rischi e opportunità di posizionamento.

## Sintesi esecutiva

Il mercato si sta dividendo in quattro famiglie:

1. audit della qualità del catalogo;
2. feed e superfici di discovery (`llms.txt`, JSON-LD, ACP, UCP, MCP);
3. correzioni manuali o generate con AI;
4. analytics, attribuzione ordini e gestione multi-store.

La soglia minima competitiva non è più un semplice punteggio. Un prodotto credibile deve spiegare i problemi, lavorare su cataloghi grandi, mostrare le priorità, consentire un percorso di correzione e mantenere trasparenti dati e limiti.

DestinX adotterà questa posizione:

- **Free come prodotto e motore principale**, installabile e utile da solo;
- audit locale, deterministico, spiegabile e senza limiti artificiali;
- correzioni sempre controllate dall'utente, con anteprima e possibilità di annullamento quando verranno introdotte;
- protocolli separati dal modello dati, perché le specifiche sono ancora in evoluzione;
- Pro come add-on che registra capacità aggiuntive sul motore Free;
- ConfigCraft Suite come servizio opzionale sostanziale per AI, storico remoto e multi-store, mai come dipendenza della Free.

## Matrice dei concorrenti

| Prodotto | Funzioni pubbliche rilevanti | Modello Free/Pro o cloud | Cosa impariamo |
| --- | --- | --- | --- |
| [MerchantStamp Catalog Audit](https://wordpress.org/plugins/merchantstamp-catalog-audit/) | ShopScore, copertura campi, problemi prioritari, JSON-LD, feed, storico di 60 scansioni, confronto e alert di degrado | Tutto il plugin locale è dichiarato gratuito; il servizio opzionale aggiunge multi-store e verifica remota | La diagnosi deve portare a priorità misurabili e confronto nel tempo; il cloud deve risolvere un problema realmente non locale |
| [AgenticTrack](https://en-ca.wordpress.org/plugins/agentictrack-agent-readiness-for-woocommerce/) | Audit prodotto/negozio, correzioni manuali con anteprima e undo, `llms.txt`, crawler rules, pannello prodotto | Audit, fix manuali e discovery Free; riscritture AI e analytics ordini Pro | Il percorso audit → correzione → nuova misura è più utile del solo score; AI e attribuzione sono una separazione premium comprensibile |
| [CodeAtoZ AI Readiness](https://wordpress.org/plugins/codeatoz-ai-readiness-woocommerce/) | Score, suggerimenti, `llms.txt`, scheduler, bot log, email, bulk fix, schema, export, multilingua e knowledge base | Dichiara esecuzione locale e nessuna API esterna | Il mercato apprezza una suite ampia, ma changelog e superficie estesa mostrano il costo di sicurezza, compatibilità e manutenzione |
| [Clustova Commerce](https://en-gb.wordpress.org/plugins/clustova-commerce/) | Feed ACP, `llms.txt`, schema senza duplicazioni, score e rilevazione basilare del canale AI | Fondazione Free; UCP/MCP, AI enrichment, revenue e ad tracking Pro | Il plugin principale può possedere il modello dati e i feed di base, mentre l'add-on aggiunge automazioni e analytics |
| [Goppa Agentic Commerce](https://wordpress.org/plugins/goppa-agentic-commerce/) | UCP, `llms.txt`, MCP di sola lettura e catalogo JSON; reporting opzionale | Endpoint locali senza account; reporting cloud opzionale | Dichiarare soltanto capacità realmente implementate. I changelog mostrano rischi concreti: prodotti nascosti esposti e manifest inizialmente non conforme |
| [UCPtools](https://wordpress.org/plugins/ucptools-ai-agent-discovery-for-woocommerce/) | Profilo UCP e funnel agenti registrato localmente | Storico locale Free; cloud per storico lungo, multi-store e alert | Retention limitata e reporting locale sono un buon modello privacy; multi-store è un servizio ConfigCraft naturale |
| [KaliCart Bridge](https://wordpress.org/plugins/kalicart-bridge/) | Checklist feed, conteggio gap, filtri prodotto, CSV, risoluzione brand e snapshot atomici | Funzioni presentate nel plugin principale | La robustezza operativa del feed conta: validazione per riga, esclusioni esplicite e conservazione dell'ultimo snapshot valido |
| [Goppa/Agentabile e altri feed-first](https://wordpress.org/plugins/agentabile/) | Attivazione rapida e feed pubblici senza configurazione | Generalmente Free o ponte verso un servizio | Il time-to-value è forte, ma un endpoint pubblico richiede controlli severi su visibilità, cache, invalidazione e privacy |
| [UCP Ready](https://wordpress.org/plugins/universal-commerce-protocol-ucp-for-woocommerce/) | Discovery, catalogo, OAuth, checkout, spedizione, coupon, ordini e simulatore | Implementazione locale estesa | Checkout e identità moltiplicano il rischio; DestinX li esclude finché specifiche, threat model e test end-to-end non saranno maturi |

## Funzioni ricorrenti

### Baseline del mercato

- score per prodotto e score aggregato del negozio;
- elenco ordinabile dei prodotti problematici;
- problemi principali e copertura dei campi;
- consigli concreti e link alla modifica;
- scansioni a lotti e aggiornamento pianificato;
- identificatori, brand, prezzo, stock, immagini, attributi e descrizioni;
- export CSV o JSON;
- pannello nel prodotto;
- `llms.txt` e catalogo strutturato;
- compatibilità con plugin SEO e assenza di JSON-LD duplicato;
- comportamento locale senza account per il nucleo Free.

### Differenziatori che creano valore

- priorità basata su impatto atteso e sforzo di correzione;
- anteprima della modifica, approvazione esplicita e undo;
- storico prima/dopo con versione del modello di scoring;
- snapshot atomici: l'ultimo risultato valido resta disponibile durante scansioni o rebuild;
- simulatore di ciò che un agente può effettivamente leggere;
- esclusioni esplicite e verifica di prodotti nascosti, privati o protetti;
- integrazione multilingua;
- analytics locali con retention limitata;
- alert, storico lungo e vista multi-store in un servizio centralizzato.

### Funzioni ad alto rischio

- scrittura automatica dei contenuti senza anteprima;
- modifica globale di `robots.txt` con regole permissive;
- feed pubblici che includono prodotti nascosti, protetti o destinati al B2B;
- schema JSON-LD aggiunto senza rilevare quello esistente;
- telemetria o attribuzione ordini attiva per impostazione predefinita;
- OAuth, checkout, coupon, pagamenti e ordini pilotati da agenti;
- dichiarazioni di conformità a protocolli emergenti senza test contro una specifica versionata;
- promesse di ranking, indicizzazione, raccomandazioni o vendite.

## Decisione di prodotto DestinX

### Free 1.0.0 — invio WordPress.org

La prima versione deve essere concentrata e già completa nel proprio scopo:

- scansione locale illimitata del catalogo;
- score deterministico e versione delle regole;
- controlli prodotto e negozio;
- scansione completa a lotti con snapshot atomico;
- dashboard, ricerca, filtri, priorità e CSV;
- pannello diagnostico nel prodotto;
- istruzioni di correzione e collegamenti diretti;
- nessuna modifica automatica, telemetria, licenza o servizio esterno.

### Evoluzione Free dopo l'approvazione

Queste funzioni resteranno nel plugin principale perché completano il motore e ne aumentano il valore senza richiedere un servizio:

- workspace di correzione manuale con anteprima, conferma e undo;
- storico locale limitato e confronto prima/dopo;
- esclusioni per prodotto e controlli rigorosi sulla visibilità;
- `llms.txt`, catalogo JSON di sola lettura e diagnostica dello schema;
- snapshot validati e rigenerazione su modifica catalogo;
- anteprima di ciò che un consumatore automatico riceve;
- API e hook stabili per gli add-on.

L'introduzione di feed pubblici avverrà soltanto dopo la 1.0.0, con specifica versionata, escape contestuale, cache invalidation, test su prodotti nascosti/protetti e disattivazione semplice.

### Add-on Pro

Il Pro sfrutterà i dati, le regole, le code, il repository e le interfacce della Free. Aggiungerà:

- suggerimenti e riscritture AI con revisione umana;
- azioni bulk sicure e workflow di approvazione;
- monitoraggio programmato, alert e storico avanzato;
- multilingua avanzato;
- adapter ACP/UCP/MCP e validatori dedicati, quando maturi;
- analytics del traffico agente e attribuzione, con privacy by design;
- connessione ConfigCraft per multi-store, team, report e automazioni.

### ConfigCraft Suite

ConfigCraft potrà gestire:

- account, organizzazioni, ruoli e negozi;
- sottoscrizione e licenza del solo add-on Pro;
- quote e fatturazione delle elaborazioni AI;
- dashboard multi-store, storico remoto, alert e report;
- download e aggiornamenti dell'add-on distribuito fuori da WordPress.org;
- termini, privacy, consensi e audit delle chiamate esterne.

Non gestirà il funzionamento della Free, non sarà contattato dalla Free e non riceverà dati finché l'utente non avrà installato/configurato il Pro e accettato il servizio.

## Vantaggio che vogliamo costruire

Non competiamo sul numero di sigle o di schermate. Il vantaggio DestinX sarà un ciclo affidabile e verificabile:

`misura → ordina per impatto/sforzo → correggi con controllo → confronta → pubblica soltanto dati validati`

Ogni risultato deve essere spiegabile, ogni scrittura reversibile, ogni endpoint verificabile e ogni invio esterno esplicito.

## Fonti ufficiali WordPress

- [Detailed Plugin Guidelines](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/)
- [Common issues found during plugin review](https://developer.wordpress.org/plugins/wordpress-org/common-issues/)

Le linee guida vietano trialware e funzioni locali presenti ma bloccate da pagamento; raccomandano add-on esterni per il codice premium. I servizi a pagamento sono ammessi quando forniscono funzionalità sostanziale, sono documentati e le comunicazioni esterne avvengono con consenso.
