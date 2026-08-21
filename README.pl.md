# Żywienie dla Zdrowia

Żywienie dla Zdrowia to rozwijana wtyczka open-source dla WordPressa. Ma wspierać zespoły prowadzące strony placówek medycznych w utrzymaniu publicznej sekcji „Żywienie dla zdrowia” oraz ułatwiać publikację związanych z nią dokumentów i informacji.

Repozytorium jest generyczne i nie jest związane z żadną konkretną organizacją ani wdrożeniem.

> **Status projektu:** wersja `0.1.0` jest wczesną wersją developerską. Standalone pipeline i classifier okresów jadłospisów, integracja z WordPress uploads, serwis katalogu oparty na transientach i pierwsza techniczna strona administracyjna są zaimplementowane, ale funkcje publiczne i pozostałe moduły nadal są planowane. Ta wersja nie jest gotowa do użycia produkcyjnego.

## Status funkcjonalności

### Zaimplementowano (Implemented)

- Plik startowy wtyczki z poprawnym nagłówkiem, ochroną przed bezpośrednim uruchomieniem i lifecycle aktywacji storage jadłospisów.
- Niezależny parser nazw jadłospisów zgodnych z konwencją `YYYY-MM-DD_YYYY-MM-DD_nazwa.pdf`, niezmienny model dokumentu i maszynowo czytelne błędy parsowania.
- Niezależny, nierekurencyjny scanner katalogu jadłospisów, który odrzuca symlinki, raportuje nierozpoznane wpisy, sortuje dokumenty według dat z nazw i deterministycznie grupuje identyczne okresy.
- Niezależny validator kandydatów PDF sprawdzający symlink, zwykły i czytelny plik, opcjonalne MIME, nagłówek oraz marker EOF w ograniczonym fragmencie końca.
- Niezależny builder zwalidowanego katalogu jadłospisów, który zachowuje tylko dokumenty zaakceptowane przez scanner i ograniczony validator PDF, filtruje grupy okresów oraz łączy deterministycznie uporządkowane issues.
- Niezależny i deterministyczny classifier dzielący zwalidowane grupy okresów na aktualne, nadchodzące i archiwalne względem jawnie przekazanej daty kalendarzowej.
- Wyznaczanie ścieżki przez WordPress uploads API, tworzenie `zywienie-dla-zdrowia/jadlospisy/` podczas aktywacji oraz provider łączący ten katalog ze standalone pipeline.
- Cache WordPress Transients API o czasie życia około pięciu minut oraz serwis katalogu udostępniający odczyt z cache i programowe operacje refresh/clear.
- Natywną stronę administracyjną WordPress „Status publikacji” ze statusem technicznym katalogu, licznikami okresów aktualnych/nadchodzących/archiwalnych względem daty witryny WordPress, bezpiecznymi opisami problemów i ręcznym odświeżaniem chronionym capability oraz nonce w przepływie POST/Redirect/GET.
- Testy PHPUnit parsera nazw, scannera filesystemu, validatora kandydatów PDF, pipeline katalogu i classifiera okresów bez uruchamiania WordPressa.
- Narzędzia developerskie Composer: PHP_CodeSniffer i WordPress Coding Standards.
- Początkową dokumentację projektu, bezpieczeństwa, współpracy i specyfikacji produktu.
- Workflow CI sprawdzający konfigurację Composer, składnię PHP, PHPCS i PHPUnit.

### Planowane dla v1.0 (Planned for v1.0)

- Jadłospisy.
- Wyniki badań laboratoryjnych.
- Materiały edukacyjne.
- Konfigurowalny link do zewnętrznego formularza uwag lub ankiety.
- Dodatkowe zabezpieczenia serwerowe, antywirusowe lub sanitizacja dokumentów wymagane przez konkretne wdrożenie.
- Konfiguracja przez WordPress Options API.
- Listy dokumentów aktualnych, nadchodzących i archiwalnych, rozbudowa panelu administracyjnego o pozostałe moduły, shortcode’y oraz publiczny frontend.
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

Zaimplementowany scanner przyjmuje zaufaną ścieżkę katalogu z konfiguracji aplikacji, sprawdza wyłącznie bezpośrednie wpisy, odrzuca symlinki oraz nigdy nie czyta ani nie wykonuje zawartości dokumentów. Builder katalogu waliduje wyłącznie kandydatów filename zaakceptowanych przez scanner i łączy problemy scannera oraz validatora bez ujawniania ścieżek źródłowych. Finalny katalog zawiera kandydatów, którzy przeszli walidację nazwy, typu wpisu i ograniczoną walidację PDF candidate. WordPress storage odrzuca bezpośrednie symlinki i kolidujące pliki w dwóch ścieżkach zarządzanych przez plugin. Nie oznacza to skanowania malware, sanitizacji PDF, pełnej walidacji struktury PDF ani gwarancji bezpieczeństwa dokumentu i nie zastępuje zabezpieczeń serwera, antywirusa ani kontroli administratora. Strona administracyjna wykonuje escaping niezaufanych nazw wpisów, a handler odświeżania niezależnie wymaga `manage_options` i nonce WordPressa. Przyszłe funkcje WordPress zachowają te same zasady walidacji, późnego escaping, capability i nonce. Zawartość katalogu uploads nie będzie dołączana ani wykonywana jako PHP.

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

## Cache katalogu jadłospisów

Serwis katalogu WordPress zapisuje w cache wyłącznie poprawne obiekty `ZFDZ_Menu_Catalog_Result` na około pięć minut pod stałym, wersjonowanym kluczem transientu `zfdz_menu_catalog_v1`. Poprawny katalog może zawierać entry-level issues. Błędy katalogowe są zwracane bez cache’owania. Nieoczekiwana wartość transientu jest usuwana i traktowana jako cache miss. Programowy refresh usuwa poprzednią wartość przed ponownym zbudowaniem katalogu, a wyczyszczenie cache usuwa wyłącznie transient i nigdy nie skanuje ani nie zmienia filesystemu.

Załadowanie pluginu ani utworzenie serwisu nie wykonuje operacji transientu lub filesystemu. Praca rozpoczyna się dopiero po jawnym pobraniu, odświeżeniu lub wyczyszczeniu katalogu przez konsumenta. Zaimplementowany przycisk administracyjny wykonuje jawny, chroniony refresh; zwykłe renderowanie strony korzysta z cache przez `get_catalog()`.

Pliki będzie można dostarczać przez zewnętrznie skonfigurowane, ograniczone konto SFTP. Plugin nie implementuje SFTP, a administrator serwera odpowiada za ograniczenie konta wyłącznie do katalogu dokumentów. Credentials nie mogą być przechowywane w repozytorium. Pliki dostarczone poza WordPressem stają się widoczne po wygaśnięciu krótkiego cache lub wcześniej po jawnym programowym odświeżeniu.

## Strona administracyjna Status publikacji

Administratorzy z capability `manage_options` mogą otworzyć **Żywienie dla Zdrowia → Status publikacji**. Strona pobiera dane wyłącznie przez `ZFDZ_WordPress_Menu_Catalog_Service`, pokazuje techniczny status modułu jadłospisów, liczbę dokumentów, okresów i problemów oraz bezpieczne polskie opisy wykrytych issues. Nie pokazuje ścieżek filesystemu ani linków do dokumentów.

Dla poprawnego katalogu strona pobiera datę odniesienia jeden raz przez WordPress `current_datetime()` i klasyfikuje istniejące grupy okresów jako aktualne (`start_date <= today <= end_date`), nadchodzące (`start_date > today`) albo archiwalne (`end_date < today`). Granice są inkluzywne. Panel pokazuje datę odniesienia, trzy liczniki okresów oraz osobną informację, czy co najmniej jeden okres jadłospisu obowiązuje dzisiaj. Nie wyświetla jeszcze list okresów ani dokumentów.

Ręczne odświeżanie używa klasycznego przepływu POST/Redirect/GET przez `admin-post.php`. Handler sprawdza `manage_options` i nonce WordPressa przed wywołaniem `refresh_catalog()`, a następnie przekierowuje tylko z dozwolonym statusem success albo error. Strona nie dodaje własnego CSS ani JavaScriptu.

Na tym etapie status techniczny **OK** oznacza wyłącznie, że katalog jest technicznie dostępny i nie zawiera problemów scannera lub validatora. Brak aktualnego okresu jest raportowany osobno i nie zmienia statusu technicznego na błąd. Żaden z tych statusów nie oznacza publikacji wszystkich wymaganych materiałów ani zgodności organizacji z prawem.

Klasyfikacja okresów jest obliczana po pobraniu katalogu z cache i nie trafia do transientu. Dzięki temu zmiana daty witryny WordPress wpływa na klasyfikację przy następnym renderowaniu strony bez invalidacji cached catalog.

## Development

Minimalne wspierane środowisko to WordPress 6.8 oraz PHP 8.2. Zalecane jest PHP 8.3 lub nowsze. Narzędzia developerskie wymagają Composer 2. Instalacja zależności developerskich i uruchomienie kontroli:

```bash
composer install
composer validate --strict
composer lint
composer test
```

Projekt nie ma zależności runtime. Testy jednostkowe działają bez WordPressa i obejmują niezależny parser nazw, scanner katalogu jadłospisów, validator kandydatów PDF, pipeline zwalidowanego katalogu oraz classifier okresów aktualne/nadchodzące/archiwalne. WordPress-specific lifecycle storage, warstwa cache transientów i strona administracyjna są na tym etapie objęte lintem i PHPCS oraz wymagają manualnych smoke testów po review. Publiczne shortcode’y, frontend, listy okresów i dokumentów, administracja pozostałych modułów oraz pozostała konfiguracja nadal są planowane.

Zasady współpracy opisują [CONTRIBUTING.md](CONTRIBUTING.md) oraz nadrzędne instrukcje repozytorium w [AGENTS.md](AGENTS.md).

## Zastrzeżenie prawne

Wtyczka jest narzędziem technicznym wspierającym publikację. Nie stanowi porady prawnej oraz nie ocenia, nie gwarantuje ani nie certyfikuje zgodności działalności placówki lub innej organizacji z przepisami. Administrator organizacji pozostaje odpowiedzialny za treść, kompletność, prawidłowość, adekwatność i sposób publikacji informacji.

## Licencja

GPL-2.0-or-later. Zobacz [LICENSE](LICENSE).
