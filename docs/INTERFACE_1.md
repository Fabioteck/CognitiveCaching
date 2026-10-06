# PERIFLOW // COGNITIVE CORE
## Documentazione Tecnica dell'Interfaccia Grafica e Guida al Test v1.0

Benvenuto nella documentazione operativa dell'interfaccia SaaS monolitica sviluppata per il framework di **Cognitive Caching** su hardware Bare Metal Linux (Ubuntu 24.04 LTS + PostgreSQL 16).

---

### 1. ANALISI ANATOMICA DELL'INTERFACCIA GRAFICA

L'interfaccia implementa un'estetica tecnica e ad altissimo contrasto ispirata a *periflow-saas.it*, ottimizzata per il monitoraggio termodinamico e strutturata in quattro macro-componenti:

#### A. // GATEKEEPER ROUTER (Pannello di Controllo Input)
* **Funzione:** È l'intercettatore atomico del sistema. Cattura la stringa di calcolo o la query adattiva (\(q\)) inserita dall'utente.
* **Comportamento Algoritmico:** Estrae istantaneamente tre caratteristiche discretizzate (Modulo di Young, Coefficiente di Poisson e Orizzonte Geometrico) tramite hashing deterministico (`crc32`) per verificare l'esistenza del dato senza interpellare i nodi energetici.

#### B. MONITORS DI TELEMETRIA SUPERIORI (Metriche Hardware)
* **STATO ENGINE:** Visualizza lo stato istantaneo dell'infrastruttura. 
  * *Stasi Termica (Ciano):* Il calcolo è già stato risolto e intercettato.
  * *Risonanza Attiva (Ambra):* Cache Miss. Lo sciame è in funzione.
* **BYPASS RATE:** Indica il risparmio computazionale del server Bare Metal. Nei casi di Cache Hit schizza a **> 99%**, azzerando il consumo energetico.
* **TEMP CPU BARE METAL:** Telemetria simulata della temperatura interna del processore hardware (es. Raspberry Pi 4). Mostra la stasi (~42°C) vs lo stress da calcolo (~72°C).

#### C. CONSOLE STDOUT LOGS (Terminale di Output Real-time)
* Mimica l'output standard di un terminale Linux. Stampa la complessità algoritmica reale del ciclo in esecuzione:
  * Ricerca sub-lineare **\(O(\log_B N)\)** tramite indici compositi nativi B-Tree in caso di Hit.
  * Ricerca **\(O(N) + \text{Swarm Evaluation}\)** (Distanza di Manhattan Normalizzata) in caso di Miss.

#### D. // PERIDYNAMIC SWARM ACTIVE NODES (Stato dello Sciame nel DB)
* Mappa in tempo reale i record estratti direttamente da PostgreSQL 16. Ciascun modulo rappresenta un core Bare Metal con un vettore di competenza specifico (\(p_j\)).
* **Vincolo Termodinamico:** Se la temperatura di un nodo supera la soglia critica (es. Nodo Gamma a 74.5°C evidenziato in arancione), la funzione di smorzamento termico \(\Lambda_j(t)\) crolla a zero, forzando l'Engine a escluderlo dal calcolo per preservare l'hardware.

---

### 2. PROTOCOLLO DI VALIDAZIONE (LINEA GUIDA PER I TEST)

Per testare la robustezza e la transizione di stato dell'algoritmo, utilizzare la stringa matematica basata sulla **Teoria Peridodinamica Bond-Based di Silling**:

#### Stringa Ufficiale di Test (Copia e Incolla nel Router):
```text
f_peridynamic \(= \int_{\mathcal{H}_x} \mathbf{f}\)(\(\mathbf{u}(x',t) - \mathbf{u}\)(x,t), x' - x) dV_{x'}
```

#### Fasi del Ciclo di Test:
1. **Lancio Iniziale (Fase di Miss):** Al primo invio della stringa, l'Engine registra un *Cache Miss*. La console di log mostrerà l'attivazione della Risonanza Sintattica sullo Sciame e il calcolo dell'Orizzonte Adattivo con tolleranza $m_{\min}=3$. Il dato viene sigillato ed emesso in RAM.
2. **Secondo Lancio Intercettato (Fase di Hit):** Premendo nuovamente il tasto di invio mantenendo la stessa identica stringa, l'Engine intercetta l'input all'interno dell'**Intorno Relazionale Chiuso ($\Omega$)** tramite l'indice composito B-Tree. Il Bypass Rate sale istantaneamente a $>99\%$ in pochi microsecondi.
