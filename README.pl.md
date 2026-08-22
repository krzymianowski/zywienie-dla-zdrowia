# Żywienie dla Zdrowia

Żywienie dla Zdrowia to rozwijana wtyczka open-source dla WordPressa. Ma wspierać zespoły prowadzące strony placówek medycznych w utrzymaniu publicznej sekcji „Żywienie dla zdrowia” oraz ułatwiać publikację związanych z nią dokumentów i informacji.

Repozytorium jest generyczne i nie jest związane z żadną konkretną organizacją ani wdrożeniem.

> **Status projektu:** wersja `0.1.0` jest wczesną wersją developerską. Standalone pipeline jadłospisów i badań, deterministyczny wybór najnowszego wyniku badania, WordPress storage/providery, skoordynowany cache katalogów powiązany z fingerprintem okresów, techniczny status administracyjny ze szczegółami latest result oraz publiczne shortcode’y jadłospisów są zaimplementowane. Publiczna prezentacja badań nadal jest planowana. Ta wersja nie jest gotowa do użycia produkcyjnego.

## Status funkcjonalności

### Zaimplementowano (Implemented)

- Plik startowy wtyczki z poprawnym nagłówkiem, ochroną przed bezpośrednim uruchomieniem i lifecycle aktywacji storage jadłospisów oraz wyników badań.
- Niezależny parser nazw jadłospisów zgodnych z konwencją `YYYY-MM-DD_YYYY-MM-DD_nazwa.pdf`, niezmienny model dokumentu i maszynowo czytelne błędy parsowania.
- Niezależny, nierekurencyjny scanner katalogu jadłospisów, który odrzuca symlinki, raportuje nierozpoznane wpisy, sortuje dokumenty według dat z nazw i deterministycznie grupuje identyczne okresy.
- Niezależny validator kandydatów PDF sprawdzający symlink, zwykły i czytelny plik, opcjonalne MIME, nagłówek oraz marker EOF w ograniczonym fragmencie końca.
- Niezależny builder zwalidowanego katalogu jadłospisów, który zachowuje tylko dokumenty zaakceptowane przez scanner i ograniczony validator PDF, filtruje grupy okresów oraz łączy deterministycznie uporządkowane issues.
- Niezależny i deterministyczny classifier dzielący zwalidowane grupy okresów na aktualne, nadchodzące i archiwalne względem jawnie przekazanej daty kalendarzowej.
- Niezależny parser nazw wyników badań zgodnych z `YYYY-MM-DD_YYYY-MM-DD_YYYY-MM-DD_nazwa.pdf`, niezmienny model dokumentu i wyniku parsowania oraz maszynowo czytelne błędy walidacji.
- Deterministyczny standalone matcher wiążący wynik badania z grupą jadłospisu wyłącznie przy dokładnej zgodności obu dat okresu i reprezentujący brak grupy jako prawidłowy wynik unmatched.
- Niezależny selector najnowszego wyniku badania z niezmiennymi stanami empty, matched i unmatched, niezależny od kolejności wejścia i bez fallbacku do starszego matched result.
- Niezależny, nierekurencyjny scanner katalogu wyników badań, który odrzuca symlinki, zachowuje błędy parsera i deterministycznie porządkuje rozpoznane dokumenty oraz issues.
- Niezależny pipeline katalogu wyników badań, który wykorzystuje istniejący bounded PDF candidate validator, pomija odrzucone pliki, łączy deterministyczne issues i zwraca zwalidowane dokumenty wraz z associations matched lub unmatched.
- Wyznaczanie ścieżki przez WordPress uploads API, tworzenie `zywienie-dla-zdrowia/jadlospisy/` podczas aktywacji oraz provider łączący ten katalog ze standalone pipeline.
- Wyznaczanie przez WordPress uploads API i tworzenie podczas aktywacji katalogu `zywienie-dla-zdrowia/badania/` oraz provider przekazujący jawnie dostarczone, zwalidowane grupy jadłospisów do standalone pipeline badań.
- Cache WordPress Transients API o czasie życia około pięciu minut oraz serwis katalogu udostępniający odczyt z cache i programowe operacje refresh/clear.
- Skoordynowany WordPress service katalogu wyników badań z jawnymi stanami dostępności menu/lab oraz osobnym pięciominutowym transientem powiązanym z niezależnym od kolejności fingerprintem okresów jadłospisu.
- Natywną stronę administracyjną WordPress „Status publikacji” z osobnymi statusami technicznymi jadłospisów i wyników badań, bezpiecznym raportowaniem issues oraz associations unmatched, szczegółami wybranego latest result i skoordynowanym odświeżaniem chronionym capability oraz nonce w przepływie POST/Redirect/GET.
- Bezparametrowy shortcode `[zfdz_jadlospisy]`, który grupuje zwalidowanych kandydatów PDF według dokładnego okresu oraz renderuje linki do aktualnych i nadchodzących jadłospisów na podstawie WordPress uploads base URL.
- Bezparametrowy shortcode `[zfdz_jadlospisy_archiwum]`, który renderuje archiwalne okresy jadłospisów od najnowszego i zachowuje grupowanie identycznych okresów.
- Testy PHPUnit parsera nazw, scannera filesystemu, validatora kandydatów PDF, pipeline katalogu i classifiera okresów bez uruchamiania WordPressa.
- Narzędzia developerskie Composer: PHP_CodeSniffer i WordPress Coding Standards.
- Początkową dokumentację projektu, bezpieczeństwa, współpracy i specyfikacji produktu.
- Workflow CI sprawdzający konfigurację Composer, składnię PHP, PHPCS i PHPUnit.

### Planowane dla v1.0 (Planned for v1.0)

- Jadłospisy.
- Publiczna polityka prezentacji wyników badań, publiczny shortcode badań, linki i zbiorczy frontend oraz konfiguracja Options API.
- Materiały edukacyjne.
- Konfigurowalny link do zewnętrznego formularza uwag lub ankiety.
- Dodatkowe zabezpieczenia serwerowe, antywirusowe lub sanitizacja dokumentów wymagane przez konkretne wdrożenie.
- Konfiguracja przez WordPress Options API.
- Zbiorczy shortcode `[zywienie_dla_zdrowia]`, shortcode’y pozostałych modułów, rozbudowa panelu administracyjnego oraz opcjonalne style frontendu.
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

Zaimplementowany scanner przyjmuje zaufaną ścieżkę katalogu z konfiguracji aplikacji, sprawdza wyłącznie bezpośrednie wpisy, odrzuca symlinki oraz nigdy nie czyta ani nie wykonuje zawartości dokumentów. Builder katalogu waliduje wyłącznie kandydatów filename zaakceptowanych przez scanner i łączy problemy scannera oraz validatora bez ujawniania ścieżek źródłowych. Finalny katalog zawiera kandydatów, którzy przeszli walidację nazwy, typu wpisu i ograniczoną walidację PDF candidate. WordPress storage odrzuca bezpośrednie symlinki i kolidujące pliki w każdej zarządzanej ścieżce root lub modułu. Nie oznacza to skanowania malware, sanitizacji PDF, pełnej walidacji struktury PDF ani gwarancji bezpieczeństwa dokumentu i nie zastępuje zabezpieczeń serwera, antywirusa ani kontroli administratora. Strona administracyjna wykonuje escaping niezaufanych nazw wpisów, a handler odświeżania niezależnie wymaga `manage_options` i nonce WordPressa. Przyszłe funkcje WordPress zachowają te same zasady walidacji, późnego escaping, capability i nonce. Zawartość katalogu uploads nie będzie dołączana ani wykonywana jako PHP.

Projekt v1.0 zakłada, że sama wtyczka nie będzie przechowywać danych pacjentów ani odpowiedzi ankiet, instalować cookies, wysyłać telemetrii ani przekazywać danych do usług zewnętrznych. Moduł ankiety będzie jedynie odnośnikiem skonfigurowanym przez administratora; wtyczka nie będzie deklarować, że zewnętrzny formularz jest anonimowy.

Publiczny frontend ma wykorzystywać semantyczny HTML, obsługę klawiatury, widoczny focus, responsywne i neutralne style oraz informacje niezależne wyłącznie od koloru. Podstawowe funkcje muszą działać bez JavaScriptu. Bez odpowiednich testów projekt nie deklaruje formalnej zgodności z WCAG.

Zasady zgłaszania podatności opisuje [SECURITY.md](SECURITY.md).

## Lifecycle storage WordPress

Podczas aktywacji plugin wyznacza bieżący uploads basedir przez `wp_get_upload_dir()` i idempotentnie zapewnia przez `wp_mkdir_p()` następującą strukturę:

```text
<WordPress uploads basedir>/
└── zywienie-dla-zdrowia/
    ├── jadlospisy/
    └── badania/
```

Storage jadłospisów i wyników badań niezależnie wyznaczają swoje lokalizacje z tego samego WordPress uploads basedir. Aktywacja zapewnia oba katalogi po kolei. Błąd zatrzymuje aktywację krótkim tłumaczalnym komunikatem, ale nie cofa poprawnie utworzonego katalogu i nie usuwa dokumentów. Zwykłe ładowanie pluginu nie tworzy katalogów, nie skanuje dokumentów i nie buduje katalogu wynikowego. Dezaktywacja nie usuwa katalogów ani dokumentów, a ten etap nie dodaje cleanup przy uninstall.

Provider jadłospisów wyznacza ścieżkę dopiero po jawnym `get_catalog()`. Provider wyników badań również wykonuje pracę wyłącznie na jawne żądanie i przyjmuje od konsumenta już zwalidowane grupy okresów jadłospisów. Nie pobiera automatycznie katalogu jadłospisów i nie tworzy brakującego katalogu podczas odczytu. Skoordynowany service wyników badań odpowiada za późniejszą orkiestrację menu → badania, zachowując tę granicę providera.

## Cache katalogu jadłospisów

Serwis katalogu WordPress zapisuje w cache wyłącznie poprawne obiekty `ZFDZ_Menu_Catalog_Result` na około pięć minut pod stałym, wersjonowanym kluczem transientu `zfdz_menu_catalog_v1`. Poprawny katalog może zawierać entry-level issues. Błędy katalogowe są zwracane bez cache’owania. Nieoczekiwana wartość transientu jest usuwana i traktowana jako cache miss. Programowy refresh usuwa poprzednią wartość przed ponownym zbudowaniem katalogu, a wyczyszczenie cache usuwa wyłącznie transient i nigdy nie skanuje ani nie zmienia filesystemu.

Załadowanie pluginu ani utworzenie serwisu nie wykonuje operacji transientu lub filesystemu. Praca rozpoczyna się dopiero po jawnym pobraniu, odświeżeniu lub wyczyszczeniu katalogu przez konsumenta. Zaimplementowany przycisk administracyjny wykonuje jawny, chroniony refresh; zwykłe renderowanie strony korzysta z cache przez `get_catalog()`.

## Skoordynowany cache katalogu wyników badań

`ZFDZ_WordPress_Lab_Result_Catalog_Service` najpierw pobiera zwalidowany katalog jadłospisów przez jego istniejącego właściciela. Nieudany katalog menu daje stan `menu_catalog_unavailable`; cache i provider badań nie są wtedy wywoływane, a wynik nie udostępnia katalogu badań. Dla poprawnego menu powstaje deterministyczny fingerprint SHA-256 zależny wyłącznie od posortowanych par `menu_start_date`/`menu_end_date`. Pusta lista grup ma stabilny fingerprint, a kolejność wejściowa, filenames, nazwy, ścieżki, URL-e, locale i czas nie wpływają na wynik.

Successful katalogi badań są cache’owane przez 300 sekund w osobnym transiencie `zfdz_lab_result_catalog_v1` razem z odpowiadającym fingerprintem menu. Zmiana okresów menu unieważnia stary cache przed ponownym zbudowaniem associations. Uszkodzony payload, inny fingerprint, failed catalog lub nieoczekiwana wartość są usuwane i traktowane jako cache miss. Successful catalog pozostaje cache’owalny, gdy zawiera entry-level issues lub associations unmatched.

`success` oznacza techniczną dostępność obu katalogów; unmatched pozostaje prawidłowym association. `menu_catalog_unavailable` oznacza, że matching nie może zostać oceniony i lab catalog jest celowo nieobecny. `lab_catalog_unavailable` zachowuje successful menu catalog wraz z failed lab catalog. `refresh_result()` czyści lab cache, odświeża menu i świeżo buduje katalog badań jako jedną operację. `clear_cache()` usuwa wyłącznie `zfdz_lab_result_catalog_v1`; właścicielem `zfdz_menu_catalog_v1` pozostaje menu catalog service.

Pliki będzie można dostarczać przez zewnętrznie skonfigurowane, ograniczone konto SFTP. Plugin nie implementuje SFTP, a administrator serwera odpowiada za ograniczenie konta wyłącznie do katalogu dokumentów. Credentials nie mogą być przechowywane w repozytorium. Pliki dostarczone poza WordPressem stają się widoczne po wygaśnięciu krótkiego cache lub wcześniej po jawnym programowym odświeżeniu.

## Strona administracyjna Status publikacji

Administratorzy z capability `manage_options` mogą otworzyć **Żywienie dla Zdrowia → Status publikacji**. Strona pobiera jeden skoordynowany wynik przez `ZFDZ_WordPress_Lab_Result_Catalog_Service`, używa zawartego w nim menu catalog dla dotychczasowej sekcji jadłospisów i osobno pokazuje techniczny status wyników badań. Nie pokazuje ścieżek filesystemu ani linków do dokumentów.

Dla poprawnego katalogu strona pobiera datę odniesienia jeden raz przez WordPress `current_datetime()` i klasyfikuje istniejące grupy okresów jako aktualne (`start_date <= today <= end_date`), nadchodzące (`start_date > today`) albo archiwalne (`end_date < today`). Granice są inkluzywne. Panel pokazuje datę odniesienia, trzy liczniki okresów oraz osobną informację, czy co najmniej jeden okres jadłospisu obowiązuje dzisiaj. Nie wyświetla jeszcze list okresów ani dokumentów.

Sekcja wyników badań pokazuje liczbę zwalidowanych dokumentów, wszystkich associations, associations matched i unmatched oraz entry-level issues. Pusty successful lab catalog ma status techniczny **OK**. Successful catalog z issues lub unmatched wymaga uwagi, ale nie jest directory failure. Unmatched oznacza wyłącznie brak dokładnego okresu menu i nie jest oceną medyczną. `menu_catalog_unavailable` uniemożliwia ocenę powiązań i pokazuje liczniki badań jako niedostępne, a `lab_catalog_unavailable` zachowuje działającą sekcję menu oraz bezpiecznie opisuje błąd katalogu badań.

Dla successful katalogu badań panel wyznacza **Najnowszy wynik** wyłącznie przez `ZFDZ_Lab_Result_Latest_Selector`. Pokazuje `EMPTY` jako brak zwalidowanego wyniku albo nazwę wybranego dokumentu, zakodowaną datę wyniku, wskazany okres jadłospisu i stan association `MATCHED`/`UNMATCHED`. Kolejność wejściowa nie wpływa na wybór, panel nie sortuje samodzielnie, a najnowszy unmatched result nigdy nie jest zastępowany starszym matched result. Derived selection nie jest cache’owana osobno. Latest matched nie usuwa starszych unmatched associations z ogólnego statusu ostrzegawczego i nie oznacza automatycznej decyzji publikacyjnej.

Ręczne odświeżanie używa klasycznego przepływu POST/Redirect/GET przez `admin-post.php`. Handler sprawdza `manage_options` i istniejący nonce WordPressa przed wywołaniem skoordynowanego `refresh_result()`, a następnie przekierowuje tylko z dozwolonym statusem success albo error. Jedna operacja odświeża menu i badania. Zwykłe renderowanie używa `get_result()` i respektuje oba cache. Strona nie dodaje własnego CSS ani JavaScriptu.

Na tym etapie status techniczny **OK** oznacza wyłącznie, że katalog jest technicznie dostępny i nie zawiera problemów scannera lub validatora. Brak aktualnego okresu jest raportowany osobno i nie zmienia statusu technicznego na błąd. Żaden z tych statusów nie oznacza publikacji wszystkich wymaganych materiałów ani zgodności organizacji z prawem.

Klasyfikacja okresów jest obliczana po pobraniu katalogu z cache i nie trafia do transientu. Dzięki temu zmiana daty witryny WordPress wpływa na klasyfikację przy następnym renderowaniu strony bez invalidacji cached catalog.

## Publiczne shortcode’y jadłospisów

Na stronie WordPress należy umieścić bezparametrowy shortcode aktualnych i nadchodzących jadłospisów:

```text
[zfdz_jadlospisy]
```

Shortcode pobiera istniejący cached validated catalog, klasyfikuje grupy względem bieżącej daty witryny WordPress oraz renderuje kolejno sekcje **Aktualne jadłospisy** i **Nadchodzące jadłospisy**. Dokumenty z dokładnie tym samym zakresem dat pozostają w jednej grupie okresu. Najbliższe nadchodzące okresy są pokazywane jako pierwsze. Puste sekcje pozostają widoczne z jednoznacznym komunikatem, a techniczny błąd katalogu lub URL uploads powoduje wyłącznie krótki publiczny komunikat niedostępności bez danych diagnostycznych.

Na osobnej stronie archiwum należy umieścić bezparametrowy shortcode:

```text
[zfdz_jadlospisy_archiwum]
```

Renderuje on wyłącznie okresy archiwalne (`end_date < today`) pod nagłówkiem **Archiwum jadłospisów**, od najnowszego do starszych. Dokumenty z tego samego dokładnego okresu pozostają zgrupowane, a puste archiwum nadal pokazuje komunikat **Brak archiwalnych jadłospisów.** Istniejący `[zfdz_jadlospisy]` nadal renderuje wyłącznie okresy aktualne i nadchodzące.

Linki w obu shortcode’ach powstają z `baseurl` zwróconego przez `wp_get_upload_dir()` oraz oryginalnego filename zakodowanego przez `rawurlencode()`. Widoczna etykieta wykorzystuje nazwę rozpoznaną przez parser. Entry-level issues i nazwy błędnych wpisów nigdy nie są renderowane, a poprawne dokumenty pozostają widoczne, gdy katalog zawiera również issues. Okresy archiwalne są linkowane wyłącznie przez `[zfdz_jadlospisy_archiwum]`, a nie przez `[zfdz_jadlospisy]`.

Rozdzielenie archiwalnych dokumentów do osobnego shortcode jest wyłącznie sposobem prezentacji, a nie kontrolą dostępu. Plik znajdujący się w publicznym WordPress uploads może nadal być dostępny przez bezpośredni URL, jeżeli jest on znany, niezależnie od tego, czy aktualnie linkuje go którykolwiek shortcode. Ten etap nie dodaje private storage, proxy pobierania, blokowania URL-i ani reguł serwera WWW. Linkowane pliki są wyłącznie zwalidowanymi kandydatami PDF; istniejące kontrole nie zapewniają skanowania malware, sanitizacji, pełnego parsowania PDF ani gwarancji bezpieczeństwa.

Oba shortcode’y korzystają z `get_catalog()` i nigdy nie wymuszają refresh. Pliki dostarczone poza WordPressem stają się widoczne po wygaśnięciu około pięciominutowego cache lub po chronionym ręcznym odświeżeniu przez administratora. Klasyfikacja wykorzystuje świeżą datę witryny WordPress podczas każdego renderowania i nie jest cache’owana.

## Standalone filesystem pipeline wyników badań

Etap 11 definiuje kontrakt filename `YYYY-MM-DD_YYYY-MM-DD_YYYY-MM-DD_nazwa.pdf`. Pierwsze dwie daty identyfikują dokładny okres jadłospisu, trzecia jest datą wyniku badania, a pozostała niepusta część stanowi nazwę. Data wyniku musi być rzeczywistą datą kalendarzową, ale może przypadać przed okresem jadłospisu, w jego trakcie albo po nim.

Standalone parser odrzuca wejścia ścieżkowe, rozszerzenia inne niż PDF, błędne lub niemożliwe daty, odwrócony zakres jadłospisu, nieprawidłowy UTF-8, znaki kontrolne i nieprawidłowe nazwy. Zwraca niezmienny dokument albo jeden stabilny, maszynowo czytelny błąd. Nierekurencyjny scanner odrzuca symlinki i nieobsługiwane typy wpisów, przekazuje parserowi wyłącznie basename zwykłych plików i nie otwiera treści dokumentów. Builder katalogu waliduje tylko filenames zaakceptowane przez scanner za pomocą istniejącego bounded PDF candidate validatora, deterministycznie łączy issues i przekazuje zwalidowanych kandydatów do matchera dokładnego okresu.

Matcher porównuje wyłącznie `menu_start_date` i `menu_end_date` z datami istniejącej `ZFDZ_Menu_Period_Group`. Brak dokładnego okresu tworzy association unmatched, a nie błąd parsera, walidacji PDF lub katalogu. Associations i finalne documents są deterministycznie uporządkowane według daty wyniku malejąco, dat okresu malejąco i oryginalnego filename rosnąco przez binarny `strcmp()`.

Standalone latest selector przyjmuje associations w dowolnej kolejności i stosuje identyczny porządek: `result_date` malejąco, `menu_start_date` malejąco, `menu_end_date` malejąco oraz oryginalny filename rosnąco przez binarny `strcmp()`. Pusta lista daje `EMPTY`. W pozostałych przypadkach najnowsza association daje `MATCHED` albo `UNMATCHED`. Najnowszy unmatched result nigdy nie jest ukrywany przez fallback do starszego matched result. Selector nie używa zegara systemowego, `filemtime`, filesystemu, WordPress API ani treści dokumentu i nie decyduje o publicznym pokazaniu pliku.

Aktywacja tworzy idempotentnie zarządzany katalog `badania/` pod WordPress uploads basedir. WordPress provider wyników badań wyznacza tę ścieżkę i przekazuje jawnie dostarczone, zwalidowane grupy okresów jadłospisów do standalone pipeline. Celowo nie pobiera sam katalogu jadłospisów. Skoordynowany service dostarcza te grupy, rozróżnia niedostępność menu i katalogu badań, cache’uje wyłącznie successful lab catalog dla dokładnego fingerprintu okresów menu i zasila techniczny status administracyjny. Panel wyznacza latest selection podczas successful renderowania, natomiast frontend jeszcze z niej nie korzysta; shortcode badań, publiczne linki, zbiorczy frontend i konfiguracja Options API także nie są zaimplementowane. Powiązanie lub wybór wyniku jest wyłącznie techniczną operacją na metadata. Plugin nie interpretuje treści badania, nie ocenia jego wyniku i nie potwierdza zgodności z normami lub wymaganiami prawnymi.

## Development

Minimalne wspierane środowisko to WordPress 6.8 oraz PHP 8.2. Zalecane jest PHP 8.3 lub nowsze. Narzędzia developerskie wymagają Composer 2. Instalacja zależności developerskich i uruchomienie kontroli:

```bash
composer install
composer validate --strict
composer lint
composer test
```

Projekt nie ma zależności runtime. Testy jednostkowe działają bez WordPressa i obejmują niezależny parser nazw, scanner katalogu jadłospisów, validator kandydatów PDF, pipeline zwalidowanego katalogu, classifier okresów aktualne/nadchodzące/archiwalne, parser nazw wyników badań, matcher dokładnego okresu, latest-result selector, nierekurencyjny scanner, zwalidowany pipeline badań oraz inwarianty coordinated service result. WordPress-specific storage, providery, obie warstwy transientów, coordinated service, strona administracyjna i publiczne shortcode’y jadłospisów są na tym etapie objęte lintem i PHPCS oraz wymagają manualnych smoke testów po review. Publiczna polityka prezentacji wyników badań, zbiorczy shortcode, frontend pozostałych modułów i konfiguracja nadal są planowane.

Zasady współpracy opisują [CONTRIBUTING.md](CONTRIBUTING.md) oraz nadrzędne instrukcje repozytorium w [AGENTS.md](AGENTS.md).

## Zastrzeżenie prawne

Wtyczka jest narzędziem technicznym wspierającym publikację. Nie stanowi porady prawnej oraz nie ocenia, nie gwarantuje ani nie certyfikuje zgodności działalności placówki lub innej organizacji z przepisami. Administrator organizacji pozostaje odpowiedzialny za treść, kompletność, prawidłowość, adekwatność i sposób publikacji informacji.

## Licencja

GPL-2.0-or-later. Zobacz [LICENSE](LICENSE).
