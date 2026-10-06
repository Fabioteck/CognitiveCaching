# Swarm Telemetry Pipeline & Architecture (Phase 5 - Operational)

Questo documento descrive in dettaglio la logica ingegneristica, i flussi di dati e l'architettura della **Pipeline di Telemetria e Analytics** implementata all'interno dello Swarm (Nodo A + Nodo B). Questo modulo è progettato per garantire il monitoraggio hardware continuo e l'estrazione delle metriche di efficienza senza introdurre overhead sulle prestazioni della cache cognitiva.

---

## 1. Architettura di Riferimento e Pipeline Dati

L'infrastruttura adotta un modello completamente **disaccoppiato (Read/Write Separation)** per azzerare gli effetti collaterali e la *write amplification* sul database durante le richieste di lettura del frontend.

```text
                  ┌──────────────────────┐
                  │      FRONTEND        │
                  │   Dashboard / UI     │
                  └──────────┬───────────┘
                             │ GET (Request JSON)
                             ▼
                  ┌──────────────────────┐
                  │  TelemetryController │
                  │   (READ-ONLY APIS)   │
                  └──────────┬───────────┘
                             │
                             ▼
                    ┌────────────────┐
                    │   PostgreSQL   │◄────────────────────────┐
                    │                │                         │
                    │ node_telemetry │                         │
                    │ user_query_logs│                         │
                    │ benchmarks     │                         │
                    └───────▲────────┘                         │
                            │                                  │
         ┌──────────────────┴──────────────────┐               │
         │                                     │               │
  [ WRITE VIA CRON ]                   [ WRITE VIA HTTP ]      │
         │                                     │               │
┌────────┴─────────────────────────┐ ┌─────────┴─────────────┐ │
│        NODO A (Raspberry Pi)     │ │     NODO B (Mac M1)   │ │
│   CollectNodeATelemetry Command  │ │ TelemetryIngestCtrl   │ │
│  (Raccoglie metriche native Linux│ │ (Riceve JSON via POST │ │
│   + stato PostgreSQL e Nostr)    │ │  e mappa le metriche) │ │
└──────────────────────────────────┘ └───────────────────────┘ │
                                                               │
                                                               │
                  ┌──────────────────────────────┐             │
                  │   CognitiveAnalyticsService  ├─────────────┘
                  │ (Modulo di Estrazione Dati)  │
                  └──────────────────────────────┘
```

---

## 2. Schema di Persistenza Unificato (`node_telemetry`)

Per evitare la frammentazione dei dati in tabelle multiple per ogni singolo nodo, è stata adottata una **strategia ibrida con colonne fortemente tipizzate + payload flessibile JSONB**.

### Struttura della Tabella
*   `id` (BigInteger, Primary Key)
*   `node_identifier` (String: `node-a`, `node-b`): Identificativo univoco del nodo nello Swarm.
*   `temperature_c` (Decimal): Temperatura del SoC/CPU misurata nativamente.
*   `cpu_clock_mhz` (Decimal): Frequenza operativa del processore.
*   `cpu_load_percent` (Decimal): Percentuale di utilizzo complessivo della CPU.
*   `status_state` (String: `ONLINE`, `READY`, `OFFLINE`): Stato operativo del nodo.
*   `metrics_payload` (JSONB): Colonna estensibile per metriche specifiche del nodo (es. consumi elettrici in Watts, utilizzo Core Fire, latenze software del motore LLM).
*   `recorded_at` (Timestamp): Momento esatto della misurazione sul nodo di origine.
*   `received_at` (Timestamp): Momento di ricezione e persistenza nel database centralizzato.

### Strategia di Indicizzazione
1.  **Indice Composto (`node_identifier`, `recorded_at`):** Ottimizzato per le query time-series della dashboard che richiedono l'andamento storico di un nodo specifico.
2.  **Indice Singolo (`received_at`):** Per logiche di rotazione dei log, pulizia automatica o sincronizzazione asincrona.
3.  **Indice GIN su `metrics_payload`:** Consente interrogazioni ad alte prestazioni direttamente sulle sotto-chiavi del JSONB (es. estrazione dei record dove il consumo istantaneo supera una determinata soglia in Watts).

---

## 3. Flussi di Raccolta Dati

### Nodo A (Raspberry Pi 4 / Ubuntu / Ecosistema Locale)
La telemetria hardware del Nodo A è governata dal Demone Cron di sistema.
*   **Meccanismo:** Un cron job invoca ogni 60 secondi il runner di Laravel (`php artisan schedule:run`).
*   **Comando Dedicato:** `php artisan telemetry:collect-node-a`.
*   **Estrazione Nativa:** Legge direttamente i descrittori del kernel Linux (es. `/proc/stat` per il carico CPU, `/sys/class/thermal/` per la temperatura del SoC) ed interroga lo stato locale di PostgreSQL e del relay Nostr.
*   **Isolamento:** Le letture avvengono in background senza impattare sul tempo di risposta delle API utente.

### Nodo B (MacBook M1 / Ollama / Motore LLM)
La telemetria del Nodo B è guidata dagli eventi di inferenza e da script push nativi.
*   **Meccanismo:** Invia i dati tramite una richiesta HTTP POST protetta verso l'endpoint centrale di ingest del Nodo A.
*   **Mappatura Metriche:**
    *   `temp` $\rightarrow$ `temperature_c`
    *   `clock` $\rightarrow$ `cpu_clock_mhz`
    *   `power` $\rightarrow$ `metrics_payload.power`
    *   `core_fire_usage` $\rightarrow$ `metrics_payload.core_fire_usage`
    *   `ollama_status` $\rightarrow$ `metrics_payload.ollama_status` / `status_state`
*   **Caching Realtime:** L'ultimo payload ricevuto viene mantenuto istantaneamente in una cache veloce in-memory (`nodo_b_real_telemetry`) prima di essere archiviato in modo asincrono nella time-series su PostgreSQL.

---

## 4. Consolidamento della Semantica Energetica

Il progetto impone il rigoroso rispetto dei vincoli fisici e formali delle grandezze misurate, bandendo conversioni arbitrarie.

*   **Potenza Istantanea (Watts):** I dati raccolti nei log delle query (`watts_saved`, `watts_consumed`) e nella telemetria hardware del Mac M1 (`power`) rappresentano una misura di potenza istantanea in un determinato millesimo di secondo.
*   **Energia Integrata (Watt-ora / Wh):** Per calcolare l'energia accumulata o risparmiata (Wh o kWh) è matematicamente necessaria un'integrazione della potenza lungo un intervallo temporale noto.
*   **Scelta Architetturale:** All'interno del `CognitiveAnalyticsService`, l'estrazione dei dati aggrega somme e medie mantenendo le unità di misura originali espresse in **Watts**. Non vengono applicate divisioni spurie per 3600 per simulare i Watt-ora, proteggendo la correttezza scientifica dei report mostrati all'utente.

---

## 5. Esposizione ed Isolamento dei Test

La logica di calcolo e aggregazione è stata interamente incapsulata nel modulo `CognitiveAnalyticsService.php` ed esposta in sicurezza tramite il `TelemetryController@getAnalytics` mappato sulla rotta protetta `/api/analytics`.

L'intera pipeline è protetta da un set di **Feature Test automatizzati** (`tests/Feature/Api/AnalyticsTest.php`) che verificano ad ogni ciclo di integrazione continua (CI):
1.  Il blocco degli accessi non autenticati con risposta `401 Unauthorized`.
2.  L'integrità strutturale del JSON ritornato agli utenti autenticati (`200 OK`), garantendo la presenza immutabile dei quattro sotto-blocchi informativi: `user`, `swarm`, 'energy', `cache`.