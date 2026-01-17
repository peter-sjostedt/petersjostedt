# Hospitex RFID Tracking - Bakgrund och Vision

> **OBS:** Detta dokument beskriver bakgrunden och visionen för systemet, INTE en spec på den färdiga lösningen. Syftet är att ge kontextförståelse.

## Syfte
Fullständig spårbarhet av textilier från tillverkning till makulering.

## Flöde
```
Hospitex → Tillverkare → Lager/Kund → Tvätteri → Kund → Makulering
```

## Kärnprinciper
- En händelse kan påverka många RFID (batch)
- En RFID har många händelser över tid (historik)
- Prestanda prioriteras över normalisering
- QR ger kontext, RFID identifierar plagg
- Rådata + spårbarhet = kunderna skapar egen nytta

---

## Målgrupper

| Typ | Användning | Exempel |
|-----|------------|---------|
| Små aktörer | Färdigt UI, standard-QR | Tvättstugor, vårdhem, vårdc |
| Medelstora | UI + enkel rapportering | Tillverkare, hotellkedjor |
| Stora aktörer | API-integration till egna system | Sjukhus, koncerner, industritvätterier |

---

## 11 Händelsetyper

### 1. Registrera garanti
**Aktör:** Hospitex-administratör
**Syfte:** Sätt garantivillkor på nya taggar
**Flöde:** QR (garantivillkor) → Batch-skanna RFID → Skapar poster i Garanti-tabell
**Resultat:** Taggar har garantivillkor men finns inte i RFID-tabellen än

### 2. Tag till tillverkare
**Aktör:** Hospitex lagerarbetare
**Syfte:** Skicka RFID-taggar till tillverkare
**Flöde:** QR (tillverkare) → Batch-skanna RFID → Skapar RFID-poster med ägare
**Resultat:** Taggar i systemet med tillverkaren som första ägare

### 3. Tag till plagg
**Aktör:** Tillverkarens produktionspersonal
**Syfte:** Sätt tag på färdigt plagg
**Flöde:** QR (artikelreferens) → Batch-skanna RFID → Uppdaterar artikel_id
**Resultat:** RFID kopplade till specifik artikelreferens

### 4. Tag till order
**Aktör:** Tillverkarens lagerarbetare
**Syfte:** Koppla plagg till beställning
**Flöde:** QR (order + leverans) → Batch-skanna RFID → Kopplar till händelser
**Resultat:** Varje RFID kan få flera händelser med en enda skanning

### 5. Utleverans
**Aktör:** Tillverkarens lagerarbetare
**Syfte:** Skicka order till kund
**Flöde:** Ofta kombinerat med steg 4 via QR-kombination
**Resultat:** Plagg registrerade som skickade

### 6. Inleverans
**Aktör:** Kundens godsmottagare
**Syfte:** Kund tar emot order
**Flöde:** QR (inleverans) → Skanna RFID → Byter ägare, aktiverar garanti, artikel-konvertering
**Resultat:** Ägarskap överfört, garanti startad

### 7. Ändring ägare
**Aktör:** Vaktmästare/logistikpersonal
**Syfte:** Flytta plagg mellan avdelningar/enheter
**Flöde:** QR (ny ägare) → Skanna RFID → Uppdaterar ägare, ev. artikel-konvertering
**Resultat:** Plagg har ny ägare med bevarad historik

### 8. Till tvätt
**Aktör:** Sjukhuspersonal eller tvätteripersonal
**Syfte:** Skicka plagg till tvätteri
**Flöde:** QR (till_tvätt) → Bulk-skanna RFID genom säck/vagn → Ökar antal_tvättar
**Resultat:** Tvättcykel räknad (300 plagg på 30 sekunder)

### 9. Från tvätt
**Aktör:** Tvätteripersonal eller sjukhuspersonal
**Syfte:** Plagg tillbaka från tvätt
**Flöde:** QR (från_tvätt) → Bulk-skanna RFID → Registrerar händelse
**Resultat:** Plagg registrerade som rena (ägare ändras inte)

### 10. Inventering
**Aktör:** Avdelningsansvarig
**Syfte:** Bekräfta att plagg finns
**Flöde:** QR (inventering) → Skanna alla RFID → Sparar ögonblicksbild
**Resultat:** Ögonblicksbild sparad (INTE en händelse per RFID)

### 11. Makulering
**Aktör:** Textilansvarig
**Syfte:** Ta plagg ur drift
**Flöde:** QR (orsak) → RFID eller streckkod → Garantikontroll → status = makulerad
**Resultat:** Plagg makulerat med automatisk garantikontroll

---

## QR-kombination
Systemet "kommer ihåg" senaste QR av varje typ:
- Samma typ → överskriver
- Olika typer → kombineras

**Praktiskt exempel:**
1. Skannar artikel-QR + order-QR (2 skanningar)
2. Skannar 20 uniformer
3. Resultat: 40 händelser skapade med 22 skanningar

---

## Praktiska användningsfall

### Hitta felsorterings-mönster
Tvätteri ser i rapporten: 47 plagg kom in från Vårdhem A men skickades tillbaka till Vårdhem B

### Kvalitetsuppföljning
Tillverkare ser att plagg till Sjukhus A makuleras efter 60 tvättar, till Sjukhus B efter 140 tvättar - samma produkt, olika tvätteri

### Försvunna textilier
Äldreboende ser att kläder hamnar i fel rum efter tvätt - från 10 "försvunna"/vecka till nästan 0

### Lönsamhetsanalys
Tvätteri ser att Kund B kräver 15% extra behandling vs Kund A:s 5% - samma pris, omförhandlar

### Koncernanalys
Vårdkoncern ser att Enhet 7 makulerar 3x mer handdukar än genomsnittet - fel tvättprogram

---

## Databasstruktur (Vision)

### Huvudtabeller
| Tabell | Syfte |
|--------|-------|
| RFID | Plagg med ägare, artikel, status, tvätträknare |
| Ägare | Organisationer (VAT-nummer) |
| Artikel | Artikeltyper per ägare |
| Händelse | Alla händelser med QR-data (JSON) |
| RFID-Händelse | Koppling många-till-många |
| Garanti | Garantivillkor per RFID |
| Artikel-konvertering | Mappning mellan ägares SKU |
| RFID-Ägare-koppling | Ägarhistorik |

### RFID-tabell
```
rfid_nummer (PK)
aktuell_ägare_id → Ägare
artikel_id → Artikel
senaste_händelse_id → Händelse
antal_tvättar
status (aktiv/makulerad/förlorad/skadad)
created_date
```

---

## Garanti

**Start:** Vid inleverans till slutkund

**Kontroll vid makulering:**
1. Inom garantimånader?
2. Under max tvättar?
3. Båda JA → Garantianspråk möjligt

**Backup:** Streckkod = RFID-nummer (om tag ej läsbar)

---

## API-endpoints (Vision)

| Endpoint | Användning |
|----------|------------|
| /api/garanti/registrera | Registrera garantivillkor |
| /api/handelse/initiera | Starta händelse, få händelse_id |
| /api/handelse/initiera-batch | Flera händelser samtidigt |
| /api/rfid/koppla | Koppla RFID till händelse |
| /api/rfid/koppla-batch | Koppla RFID till flera händelser |
| /api/rfid/historik | Hämta rådata för analys |
| /api/artikel/registrera | Skapa artikelreferens |
| /api/inventering/rapportera | Spara ögonblicksbild |

---

## Skalbarhet

| Fas | Användning |
|-----|------------|
| År 1 | UI, 100 plagg/dag |
| År 2 | API för enkel integration |
| År 3 | Eget system ovanpå API |
| År 4 | 10,000 plagg/dag |

Samma plattform genom hela tillväxtresan, all historisk data bevarad.

---

## Teknisk implementation (Vision)

| Komponent | Teknik |
|-----------|--------|
| Hårdvara | Smartphone + scanner, UHF RFID-taggar (200+ tvättcykler) |
| API | REST |
| Admin | Webbgränssnitt |
| Scanning | Mobilapp |
| Flexibel data | JSON (artikel_data, qr_data) |
| Prestanda | Dubbellagrad data, index på sökfält |
