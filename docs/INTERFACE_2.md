# PERIFLOW // COGNITIVE CORE
## Documentazione Tecnica dell'Interfaccia Grafica e Guida alle API Analytics v2.0

Benvenuto nella documentazione operativa dell'interfaccia SaaS monolitica sviluppata per il framework di **Cognitive Caching** su hardware Bare Metal Linux Swarm (Nodo A: Raspberry Pi 4 Bare Metal + PostgreSQL 16; Nodo B: MacBook M1 + Ollama LLM).

---

### 1. ANALISI ANATOMICA DELL'INTERFACCIA GRAFICA

L'interfaccia implementa un'estetica tecnica e ad altissimo contrasto ispirata a *periflow-saas.it*, ottimizzata per il monitoraggio termodinamico e le performance analitiche reali dello Swarm, strutturata in quattro macro-componenti:

#### A. // GATEKEEPER ROUTER (Pannello di Controllo Input)
* **Funzione:** È l'intercettatore atomico del sistema. Cattura la stringa di calcolo o la query adattiva (\(q\)) inserita dall'utente.
* **Comportamento Algoritmico:** Calcola i token consumati e gestisce la persistenza istantanea dell'operazione. Ogni transazione utente viene sigillata in tempo reale nel database PostgreSQL all'interno della tabella `user_query_logs`.

#### B. MONITORS DI TELEMETRIA SUPERIORI (Ecosistema API `/api/analytics`)
I monitor superiori non si affidano più a simulazioni locali, ma estraggono i dati reali in tempo reale tramite chiamate HTTP GET protette (autenticazione tramite Token Bearer Sanctum) verso l'endpoint dedicato `/api/analytics`. Il payload JSON è strutturato in quattro moduli analitici nativi forniti dal `CognitiveAnalyticsService`:

* **BYPASS RATE (Efficienza della Cache):** Indica la percentuale di hit aggregata dello Swarm (`bypass_rate`). Calcolata matematicamente sulle metriche di storage reale. Nei casi di Hit, la latenza crolla a una media fulminea di **~6.83 ms** (`avg_hit_latency_ms`), azzerando l'esecuzione computazionale.
* **LATENZA DI RISUONATORI LLM (Cache Miss):** Quando l'intercettatore fallisce (Miss), il sistema instrada la query verso i core di generazione del Nodo B (Ollama). Il monitor mostra visivamente l'impatto sul tempo di elaborazione, registrando latenze medie reali di **~11,231.19 ms** (`avg_miss_latency_ms`) dovute alla generazione dinamica dei token da parte dell'LLM.
* **BILANCIO DI POTENZA ISTANTANEA (Energy Saver):** Espone il monitor energetico dello Swarm in modalità puramente rigorosa basata sulla potenza istantanea misurata. Visualizza l'impatto hardware esatto combinando due metriche native:
  * `total_watts_consumed`: La potenza effettiva assorbita dai nodi durante i calcoli (es. *90.50 Watts*).
  * `total_watts_saved`: La potenza istantanea preservata evitando l'accensione dei modelli generativi grazie all'intercettazione sub-lineare della cache (es. *2659.50 Watts*).
  *(Nota Semantica: Al fine di preservare l'integrità fisica delle grandezze, è omessa qualsiasi conversione arbitraria della potenza istantanea in Watt-ora (Wh) senza un'integrazione temporale continua).*

#### C. CONSOLE STDOUT LOGS (Terminale di Output Real-time)
Mimica l'output standard di un terminale Linux. Stampa la complessità algoritmica reale del ciclo in esecuzione e lo stato di instradamento:
* Ricerca sub-lineare **\(O(\log_B N)\)** tramite indici compositi nativi B-Tree in caso di Cache Hit.
* Generazione neurale **\(O(N) + \text{Ollama LLM Generation}\)** sul Nodo B in caso di Cache Miss.

#### D. // PERIDYNAMIC SWARM ACTIVE NODES (Stato di Salute dell'Hardware)
Mappa lo stato di salute dei nodi fisici memorizzato nella tabella time-series `node_telemetry` tramite un ciclo disaccoppiato (Read/Write Separation):
* **Nodo A (Raspberry Pi 4):** Lo stato dell'hardware (CPU load %, temperatura core °C, RAM e Storage) viene campionato ad alta fedeltà ogni **60 secondi** in background dal demone Cron di produzione tramite il comando nativo `php artisan telemetry:collect-node-a`, azzerando la write-amplification sulle richieste web.
* **Nodo B (MacBook M1):** Invia i propri dati fisici (temperatura di stasi/calcolo, stato di Ollama e assorbimento stimato in Watts) tramite un endpoint di Ingest HTTP POST dedicato, popolando il payload JSONB del database.

---

### 2. PROTOCOLLO DI VALIDAZIONE (LINEA GUIDA PER I TEST)

La stabilità dell'infrastruttura API e l'accuratezza dei calcoli analitici del Core sono protette da Feature Test automatizzati (`tests/Feature/Api/AnalyticsTest.php`).

#### Stringa Ufficiale di Test Peridodinamica (Copia e Incolla nel Router):
```text
f_peridynamic \(= \int_{\mathcal{H}_x} \mathbf{f}\)(\(\mathbf{u}(x',t) - \mathbf{u}\)(x,t), x' - x) dV_{x'}
```

#### Fasi del Ciclo di Validazione dell'Endpoint:
1. **Verifica Privata (401 Unauthorized):** Qualsiasi richiesta HTTP GET esterna o non autenticata verso `/api/analytics` viene intercettata dal middleware e respinta con status `401`, isolando le metriche dello Swarm da vettori di attacco esterni.
2. **Verifica Autenticata (200 OK):** Una richiesta eseguita da un client autenticato con token valido richiama istantaneamente il controller. Il test convalida che la risposta restituisca lo status `200` e che la struttura del payload JSON contenga rigorosamente le chiavi aggregate (`success`, `timestamp`, `data -> user, swarm, energy, cache`), garantendo la perfetta riproducibilità e l'immutabilità dei dati mappati sulla Dashboard frontend.
