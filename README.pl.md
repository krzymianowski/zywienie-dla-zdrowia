# Żywienie dla Zdrowia

Żywienie dla Zdrowia to rozwijana wtyczka open-source dla WordPressa. Ma wspierać zespoły prowadzące strony placówek medycznych w utrzymaniu publicznej sekcji „Żywienie dla zdrowia” oraz ułatwiać publikację związanych z nią dokumentów i informacji.

Repozytorium jest generyczne i nie jest związane z żadną konkretną organizacją ani wdrożeniem.

> **Status projektu:** wersja `0.1.0` jest wczesną wersją developerską. Standalone pipeline jadłospisów i jego pierwsza integracja z WordPress uploads są zaimplementowane, ale administracja oraz wszystkie funkcje użytkowe pozostają planowane. Ta wersja nie jest gotowa do użycia produkcyjnego.

## Status funkcjonalności

### Zaimplementowano (Implemented)

- Plik startowy wtyczki z poprawnym nagłówkiem, ochroną przed bezpośrednim uruchomieniem i lifecycle aktywacji storage jadłospisów.
- Niezależny parser nazw jadłospisów zgodnych z konwencją `YYYY-MM-DD_YYYY-MM-DD_nazwa.pdf`, niezmienny model dokumentu i maszynowo czytelne błędy parsowania.
- Niezależny, nierekurencyjny scanner katalogu jadłospisów, który odrzuca symlinki, raportuje nierozpoznane wpisy, sortuje dokumenty według dat z nazw i deterministycznie grupuje identyczne okresy.
- Niezależny validator kandydatów PDF sprawdzający symlink, zwykły i czytelny plik, opcjonalne MIME, nagłówek oraz marker EOF w ograniczonym fragmencie końca.
- Niezależny builder zwalidowanego katalogu jadłospisów, który zachowuje tylko dokumenty zaakceptowane przez scanner i ograniczony validator PDF, filtruje grupy okresów oraz łączy deterministycznie uporządkowane issues.
- Wyznaczanie ścieżki przez WordPress uploads API, tworzenie `zywienie-dla-zdrowia/jadlospisy/` podczas aktywacji oraz provider łączący ten katalog ze standalone pipeline.
- Testy PHPUnit parsera nazw, scannera filesystemu, validatora kandydatów PDF i pipeline katalogu bez uruchamiania WordPressa.
- Narzędzia developerskie Composer: PHP_CodeSniffer i WordPress Coding Standards.
- Początkową dokumentację projektu, bezpieczeństwa, współpracy i specyfikacji produktu.
- Workflow CI sprawdzający konfigurację Composer, składnię PHP, PHPCS i PHPUnit.

### Planowane dla v1.0 (Planned for v1.0)

- Jadłospisy.
- Wyniki badań laboratoryjnych.
- Materiały edukacyjne.
- Konfigurowalny link do zewnętrznego formularza uwag lub ankiety.
- Dodatkowe zabezpieczenia serwerowe, antywirusowe lub sanitizacja dokumentów wymagane przez konkretne wdrożenie.
- Konfiguracja przez WordPress Options API i cache przez Transients API.
- Panel administratora, Status publikacji, shortcode’y i publiczny frontend.
- Shortcode’y wymienione w [roboczej specyfikacji v1.0](docs/specification-v1.0.md).

### Poza zakresem v1.0 (Out of scope)

- Własne tabele bazy danych i Custom Post Types.
- REST API oraz framework JavaScript.
- Przechowywanie odpowiedzi ankiet lub danych pacjentów.
- Telemetria, cookies instalowane przez wtyczkę i automatyczne usuwanie dokumentów.
- Ocena, gwarantowanie lub certyfikowanie zgodności z prawem.

## Odbiorcy i rozwiązywany problem

Projekt jest przeznaczony dla zespołów odpowiedzialnych za publiczne strony WordPress placówek medycznych. Jego celem jest zapewnienie małego i przewidywalnego sposobu prezentowania dokumentów związanych z żywieniem oraz odnośnika do zewnętrznie zarządzanej ankiety, bez tworzenia osobnego frameworka treści lub magazynu danych.

## Kontekst regulacyjny

Projekt powstał jako narzędzie wspierające techniczną publikację informacji związanych z polską sekcją „Żywienie dla zdrowia” w kontekście rozporządzenia Ministra Zdrowia z 12 grudnia 2025 r. w sprawie standardu organizacyjnego żywienia zbiorowego w podmiocie leczniczym wykonującym działalność leczniczą w rodzaju świadczenia szpitalne (Dz.U. 2025 poz. 1780). [Oficjalny tekst aktu jest dostępny w ELI](https://eli.gov.pl/eli/DU/2025/1780/ogl).

Planowany zakres wspiera publikację stosowanych jadłospisów, ostatniego wyniku badania laboratoryjnego z odniesieniem do właściwego jadłospisu, materiałów edukacyjnych oraz dostępu do kanału anonimowego zgłaszania uwag. Wtyczka nie będzie automatycznie interpretować ani prawnie walidować sposobu realizacji tych wymagań.

## Bezpieczeństwo i prywatność

Zaimplementowany scanner przyjmuje zaufaną ścieżkę katalogu z konfiguracji aplikacji, sprawdza wyłącznie bezpośrednie wpisy, odrzuca symlinki oraz nigdy nie czyta ani nie wykonuje zawartości dokumentów. Builder katalogu waliduje wyłącznie kandydatów filename zaakceptowanych przez scanner i łączy problemy scannera oraz validatora bez ujawniania ścieżek źródłowych. Finalny katalog zawiera kandydatów, którzy przeszli walidację nazwy, typu wpisu i ograniczoną walidację PDF candidate. WordPress storage odrzuca bezpośrednie symlinki i kolidujące pliki w dwóch ścieżkach zarządzanych przez plugin. Nie oznacza to skanowania malware, sanitizacji PDF, pełnej walidacji struktury PDF ani gwarancji bezpieczeństwa dokumentu i nie zastępuje zabezpieczeń serwera, antywirusa ani kontroli administratora. Przyszłe funkcje WordPress będą walidować i sanityzować dane wejściowe, wykonywać escaping możliwie późno oraz sprawdzać uprawnienia i nonce. Zawartość katalogu uploads nie będzie dołączana ani wykonywana jako PHP.

Projekt v1.0 zakłada, że sama wtyczka nie będzie przechowywać danych pacjentów ani odpowiedzi ankiet, instalować cookies, wysyłać telemetrii ani przekazywać danych do usług zewnętrznych. Moduł ankiety będzie jedynie odnośnikiem skonfigurowanym przez administratora; wtyczka nie będzie deklarować, że zewnętrzny formularz jest anonimowy.

Publiczny frontend ma wykorzystywać semantyczny HTML, obsługę klawiatury, widoczny focus, responsywne i neutralne style oraz informacje niezależne wyłącznie od koloru. Podstawowe funkcje muszą działać bez JavaScriptu. Bez odpowiednich testów projekt nie deklaruje formalnej zgodności z WCAG.

Zasady zgłaszania podatności opisuje [SECURITY.md](SECURITY.md).

## Lifecycle storage WordPress

Podczas aktywacji plugin wyznacza bieżący uploads basedir przez `wp_get_upload_dir()` i idempotentnie zapewnia przez `wp_mkdir_p()` następującą strukturę:

```text
<WordPress uploads basedir>/
└── zywienie-dla-zdrowia/
    └── jadlospisy/
```

Zwykłe ładowanie pluginu nie tworzy katalogów, nie skanuje dokumentów i nie buduje katalogu wynikowego. Dezaktywacja nie usuwa katalogów ani dokumentów, a ten etap nie dodaje cleanup przy uninstall. Provider katalogu WordPress wyznacza ścieżkę dopiero po jawnym wywołaniu `get_catalog()`.

Pliki będzie można dostarczać przez zewnętrznie skonfigurowane, ograniczone konto SFTP. Plugin nie implementuje SFTP, a administrator serwera odpowiada za ograniczenie konta wyłącznie do katalogu dokumentów. Credentials nie mogą być przechowywane w repozytorium.

## Development

Minimalne wspierane środowisko to WordPress 6.8 oraz PHP 8.2. Zalecane jest PHP 8.3 lub nowsze. Narzędzia developerskie wymagają Composer 2. Instalacja zależności developerskich i uruchomienie kontroli:

```bash
composer install
composer validate --strict
composer lint
composer test
```

Projekt nie ma zależności runtime. Testy jednostkowe działają bez WordPressa i obejmują niezależny parser nazw, scanner katalogu jadłospisów, validator kandydatów PDF oraz pipeline zwalidowanego katalogu. WordPress-specific lifecycle storage jest na tym etapie objęty lintem i PHPCS oraz wymaga manualnego smoke testu po review. Cache, administracja, shortcode’y, frontend i klasyfikacja aktualne/nadchodzące/archiwalne pozostają planowane.

Zasady współpracy opisują [CONTRIBUTING.md](CONTRIBUTING.md) oraz nadrzędne instrukcje repozytorium w [AGENTS.md](AGENTS.md).

## Zastrzeżenie prawne

Wtyczka jest narzędziem technicznym wspierającym publikację. Nie stanowi porady prawnej oraz nie ocenia, nie gwarantuje ani nie certyfikuje zgodności działalności placówki lub innej organizacji z przepisami. Administrator organizacji pozostaje odpowiedzialny za treść, kompletność, prawidłowość, adekwatność i sposób publikacji informacji.

## Licencja

GPL-2.0-or-later. Zobacz [LICENSE](LICENSE).
