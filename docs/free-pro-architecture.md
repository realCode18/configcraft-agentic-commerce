# Architettura Free + add-on Pro

## Regola non negoziabile

`DestinX AI Commerce for WooCommerce` è il plugin principale, il motore e l'unico prodotto pubblicato su WordPress.org. Deve funzionare da solo e continuare a funzionare se il Pro non è installato, è disattivato, è incompatibile, perde la licenza o il servizio ConfigCraft non è raggiungibile.

`DestinX AI Commerce Pro` sarà un plugin add-on separato, distribuito fuori da WordPress.org. Non conterrà una copia del motore e non potrà funzionare senza la Free.

## Responsabilità

| Area | Free — motore | Pro — add-on |
| --- | --- | --- |
| WooCommerce | Lettura e normalizzazione prodotti con API pubbliche | Usa il payload normalizzato della Free |
| Regole | Registro, valutatore, codici problema, severità e versione modello | Registra regole aggiuntive tramite contratto pubblico |
| Scansioni | Lock, batch, retry, snapshot e stato | Registra task aggiuntivi senza creare un secondo scanner |
| Dati | Tabelle, repository e retention base | Usa repository/servizi Free o tabelle proprie soltanto per dati esclusivi |
| Interfaccia | Dashboard, filtri, export e pannello prodotto | Aggiunge tab, card e azioni negli slot dichiarati |
| Scritture | Nessuna nella 1.0; in seguito fix manuali sicuri | AI e bulk con anteprima, capability, nonce, audit e undo |
| Discovery | Modello dati; feed base soltanto dopo la 1.0 | Adapter e validatori avanzati |
| Rete | Nessuna chiamata esterna nella 1.0 | Chiamate ConfigCraft/AI soltanto dopo configurazione e consenso |
| Licenza | Nessun codice o stato licenza | Licenza, piano e compatibilità gestiti dall'add-on |
| Aggiornamenti | Solo WordPress.org | Canale dell'add-on esterno, mai avviato o installato dalla Free |

## Dipendenze di attivazione

Il futuro add-on dovrà:

1. dichiarare `Requires Plugins: destinx-ai-commerce`;
2. verificare l'esistenza della versione minima del core;
3. attendere l'hook di bootstrap pubblico del core;
4. non registrare route, cron o schermate se il core non è pronto;
5. mostrare un solo avviso contestuale e azionabile in caso di incompatibilità;
6. non disattivare, sostituire o modificare file della Free;
7. fallire in modo sicuro senza compromettere WooCommerce.

La Free non deve verificare se il Pro esiste. Conosce solo contratti generici di estensione.

## Contratto di estensione

### Già disponibile

- `destinx_ai_commerce_product_data`
- `destinx_ai_commerce_product_issues`
- `destinx_ai_commerce_pricing_adapters`
- `destinx_ai_commerce_audit_limit`
- `destinx_ai_commerce_batch_size`

Il payload prodotto espone già un contesto `pricing` normalizzato. Un motore
esterno può dichiarare modalità, sorgente, etichetta, disponibilità, adapter,
livello di verifica, proprietà dello stato di acquisto e range facoltativo senza
cambiare il valutatore Free o falsificare il prezzo nativo WooCommerce. La
disponibilità deve essere esplicita: la sola modalità `dynamic` non elimina il
finding di prezzo mancante.

Il registro pricing della Free accetta oggetti che implementano
`DestinX\AICommerce\Pricing_Adapter`. ID duplicati o non validi vengono ignorati,
gli adapter sono ordinati per priorità e ID, e un'eccezione di una singola
integrazione non interrompe la scansione. Gli adapter inclusi verificano le
principali famiglie di prezzo dinamico, preventivo e prodotto configurabile;
il filtro generico resta disponibile per ConfigCraft e integrazioni proprietarie.

### Da stabilizzare prima dell'ecosistema add-on

- costante/versione del contratto API distinta dalla versione del plugin;
- hook `destinx_ai_commerce_loaded` dopo il bootstrap completo;
- evento dopo una scansione completata e dopo lo switch dello snapshot;
- registry per regole con ID univoco, severità, penalità e callback;
- registry per moduli della dashboard e colonne export;
- servizi read-only per snapshot, aggregati e singolo prodotto;
- capability dedicate per audit, export e correzioni;
- oggetti risultato immutabili o array con schema documentato;
- deprecation policy di almeno un ciclo minor prima della rimozione.

I nomi definitivi verranno congelati soltanto con test di integrazione; fino ad allora questa sezione descrive il contratto richiesto, non API già pubbliche.

## Flusso di bootstrap

```text
WordPress
  └─ WooCommerce disponibile
      └─ DestinX AI Commerce Free
          ├─ normalizza il catalogo
          ├─ esegue regole e scansioni
          ├─ conserva gli snapshot
          ├─ espone UI e contratti
          └─ emette core loaded
              └─ DestinX AI Commerce Pro (se presente e compatibile)
                  ├─ registra regole/moduli/task
                  ├─ usa i dati del core
                  └─ collega ConfigCraft solo dopo opt-in
```

## Integrità dei dati

- La Free è l'unica fonte del punteggio base e dello stato della scansione.
- Il Pro può aggiungere metriche, ma non deve sovrascrivere silenziosamente il punteggio Free.
- Ogni risultato memorizza versione del modello, versione plugin e timestamp.
- Le migrazioni Pro non modificano tabelle Free senza una API di migrazione del core.
- Disattivare il Pro non rende illeggibili o inutilizzabili i dati Free.
- Disinstallare il Pro rimuove soltanto i dati Pro secondo una scelta esplicita documentata.

## Sicurezza delle correzioni

Ogni futura modifica a un prodotto, Free o Pro, deve seguire lo stesso percorso:

1. capability specifica;
2. nonce e validazione server-side;
3. lettura del valore corrente;
4. proposta e diff in anteprima;
5. approvazione esplicita;
6. scrittura via CRUD WooCommerce;
7. snapshot del valore precedente;
8. audit log locale;
9. undo idempotente;
10. nuova valutazione del prodotto.

Le azioni AI non possono applicare contenuti in background senza una regola di approvazione chiaramente attivata dall'amministratore.

## ConfigCraft Suite

ConfigCraft è il control plane opzionale, non il runtime del plugin Free.

### Può possedere

- autenticazione, organizzazioni e ruoli;
- licenza e billing del Pro;
- orchestrazione AI e gestione quote;
- cifratura dei provider key o modalità bring-your-own-key;
- storico lungo, comparazione multi-store e alert;
- reportistica e log delle elaborazioni remote;
- portale di download/supporto dell'add-on.

### Non può possedere

- l'attivazione o l'esecuzione della Free;
- lo score base del catalogo;
- i risultati locali necessari alla dashboard Free;
- la capacità di disabilitare funzioni Free;
- installazione automatica del Pro dalla Free;
- telemetria implicita.

### Contratto rete del Pro

Prima di ogni categoria di invio devono essere dichiarati finalità, endpoint, dati, retention e fornitore. Le chiamate devono essere opt-in, timeout-bounded, non bloccanti per storefront/checkout quando possibile e disattivabili. Nessun dato cliente o pagamento viene inviato per funzioni di catalogo.

## Packaging e repository

- Repository pubblico corrente: Free e documentazione WordPress.org.
- Repository privato futuro: add-on Pro, test di compatibilità e pipeline pacchetti.
- Pacchetti ZIP distinti, namespace distinti e nessuna classe duplicata.
- La Free usa esclusivamente il sistema aggiornamenti WordPress.org.
- Il Pro non viene incluso, scaricato o installato dal pacchetto WordPress.org.
- Ogni release Pro dichiara un intervallo di versioni Free compatibili.

## Test obbligatori del rapporto Free/Pro

- Free sola su installazione nuova e aggiornamento.
- Pro attivo con Free compatibile.
- Pro presente ma Free assente.
- Pro più nuovo con Free troppo vecchia.
- Free aggiornata con Pro vecchio.
- Pro disattivato durante una scansione.
- ConfigCraft irraggiungibile.
- licenza Pro scaduta.
- disinstallazione separata dei due plugin.
- nessuna regressione nelle funzioni Free in tutti gli scenari.

## Regola commerciale

Il Pro vende accelerazione, scala e servizi, non il diritto di usare un motore già incluso nella Free. La Free risolve completamente la diagnosi locale; il Pro aggiunge automazione AI, operazioni bulk, protocolli avanzati, analytics e coordinamento multi-store.
