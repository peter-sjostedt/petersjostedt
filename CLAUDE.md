# Claude Code Instructions

## Projektstandard

**VIKTIGT:** Läs och följ alltid `STANDARDS.md` innan du skriver kod.

Dokumentet innehåller:
- Filstruktur
- Databasmigrationer (använd alltid TEMPLATE.sql)
- PHP-sidstruktur
- UI/UX-mönster (lista + modal för CRUD)
- JavaScript-mönster
- Översättningsformat
- CSS-klasser

## Regler

1. **Hitta inte på** - Håll dig till fakta. Påstå aldrig att något är "standard" eller "best practice" utan källa. Om du inte vet, fråga.
2. **Lär dig och uppdatera** - När du får ny information, uppdatera STANDARDS.md och CLAUDE.md så att det inte upprepas.
3. **Följ projektets standard** - Gör inte egna lösningar när det finns en etablerad standard i detta projekt
4. **Gör bara det som efterfrågas** - Lägg inte till extra funktioner eller förbättringar
5. **Migrationer** - Läs alltid `database/migrations/TEMPLATE.sql` först
6. **UX-konsistens** - Alla CRUD-sidor ska använda lista + modal-mönstret
7. **Översättningar** - Alla texter ska vara översättningsbara via `t()`

## Språk

- Kod: Engelska (variabelnamn, funktioner)
- Kommentarer: Svenska eller engelska
- UI-texter: Svenska och engelska via translations.php
