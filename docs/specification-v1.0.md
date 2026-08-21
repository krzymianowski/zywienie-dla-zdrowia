# Żywienie dla Zdrowia — robocza specyfikacja v1.0

## Status dokumentu

To robocza specyfikacja planowanego zakresu v1.0. Etap 0 został zakończony. Etap 1 dostarczył niezależny parser nazw jadłospisów i model dokumentu, Etap 2 — niezależny scanner katalogu, Etap 3 — ograniczony standalone validator kandydatów PDF, Etap 4 — standalone pipeline zwalidowanego katalogu jadłospisów, Etap 5 — pierwszą integrację z WordPress uploads i lifecycle katalogu `jadlospisy`, Etap 6 — WordPress-specific cache katalogu oraz serwis kontrolowanego odświeżania, Etap 7 — pierwszą techniczną stronę administracyjną „Status publikacji”, Etap 8 — standalone klasyfikację okresów oraz jej liczniki w panelu, a Etap 9 — pierwszy publiczny shortcode aktualnych i nadchodzących jadłospisów. Publiczne archiwum, konfiguracja przez Options API, administracja pozostałych modułów i ich shortcode’y pozostają planowane.

## Zaimplementowany zakres Etapu 1

Parser rozpoznaje wyłącznie nazwy zgodne z konwencją `YYYY-MM-DD_YYYY-MM-DD_nazwa.pdf`. Dla prawidłowej nazwy zwraca model zawierający oryginalny filename, datę początku, datę końca i niezmienioną nazwę dokumentu. Dla błędnej nazwy zwraca maszynowo czytelny kod błędu.

Zaimplementowana walidacja obejmuje separatory ścieżek i NUL, rozszerzenie PDF, strukturę nazwy, rzeczywiste daty kalendarzowe, kolejność dat oraz niepustą nazwę Unicode. Parser nie otwiera plików, nie sprawdza MIME, nie korzysta z filesystemu ani WordPress API i nie renderuje treści.

## Zaimplementowany zakres Etapu 2

Standalone scanner filesystemu przyjmuje absolutną ścieżkę katalogu jadłospisów pochodzącą z zaufanej konfiguracji aplikacji. Ścieżka nie jest wartością pobieraną bezpośrednio z requestu HTTP; scanner nie próbuje usuwać z niej separatorów ani interpretować jej jako nazwy pliku.

Scanner:

- sprawdza wyłącznie bezpośrednią zawartość wskazanego katalogu i nie skanuje podkatalogów;
- nie korzysta z opcji `FOLLOW_SYMLINKS`, sprawdza symlink przed zwykłym plikiem i raportuje go jako `unsafe_symlink`;
- przekazuje nazwy wszystkich zwykłych plików do parsera Etapu 1, bez wcześniejszego filtrowania po rozszerzeniu;
- zwraca rozpoznane kandydaty na dokumenty oraz oddzielne issues zawierające wyłącznie nazwę wpisu i maszynowo czytelny kod błędu;
- zgłasza podkatalogi i inne nietypowe wpisy jako `unsupported_entry_type`, bez skanowania ich zawartości;
- sortuje dokumenty malejąco według dat z nazw, z binarnym `strcmp()` dla deterministycznego tie-breakera, i nie używa `filemtime` ani locale systemowego;
- grupuje dokumenty o dokładnie tej samej dacie początku i końca oraz deterministycznie sortuje grupy i issues;
- nie pozwala, aby nierozpoznany plik blokował zwrócenie poprawnych dokumentów;
- reprezentuje brak katalogu, błędny typ ścieżki, brak odczytu lub błąd skanowania przez kody katalogowe i puste kolekcje wyniku;
- nie zapisuje absolutnej ścieżki źródłowej ani targetu symlinka w modelach wyniku.

Scanner nie otwiera dokumentów, nie odczytuje ich zawartości, nie analizuje MIME ani magic bytes i nie potwierdza, że kandydat z rozszerzeniem `.pdf` jest rzeczywistym dokumentem PDF. Etap 3 dostarcza osobny, ograniczony validator kandydatów PDF, a Etap 4 łączy oba komponenty w odrębnej warstwie orkiestracji bez zmiany odpowiedzialności scannera. Scanner nie korzysta z WordPress API, nie tworzy katalogów i nie modyfikuje ani nie usuwa wpisów.

## Zaimplementowany zakres Etapu 3

Standalone validator PDF przyjmuje absolutną ścieżkę pliku pochodzącą z zaufanej warstwy aplikacji, a nie bezpośrednio z requestu HTTP. Wynik nie przechowuje ani nie udostępnia ścieżki pliku, `realpath` ani targetu symlinka.

Validator:

- odrzuca bezpośredni symlink przed sprawdzeniem istnienia i typu pliku, dzięki czemu broken symlink zwraca `unsafe_symlink`;
- sprawdza istnienie, zwykły typ i czytelność pliku oraz obsługuje błędy otwarcia, stat i odczytu kodami maszynowymi;
- otwiera plik binarnie i nie wykonuje jego zawartości;
- korzysta z `finfo` i `FILEINFO_MIME_TYPE`, jeżeli rozszerzenie `fileinfo` jest dostępne, akceptując `application/pdf` oraz `application/x-pdf`;
- pomija MIME check bez odrzucania pliku, jeżeli `finfo` nie jest dostępne, ale traktuje techniczną awarię dostępnego detektora jako `mime_detection_failed`;
- sprawdza dokładnie początkowy, ośmiobajtowy nagłówek `%PDF-X.Y`;
- szuka `%%EOF` wyłącznie w ostatnich maksymalnie 4096 bajtach;
- nie wczytuje całego dokumentu do pamięci i nie sprawdza rozszerzenia filename;
- zwraca jedynie informację, czy plik jest **valid PDF candidate**, lub maszynowo czytelny kod błędu.

Validator nie jest pełnym parserem PDF, nie analizuje obiektów, xref, skryptów, formularzy, embedded files, metadanych, podpisów ani szyfrowania. Nie wykrywa malware, nie sanitizuje ani nie konwertuje dokumentów i nie gwarantuje bezpieczeństwa ich zawartości. Nie zastępuje zabezpieczeń serwera, antywirusa ani kontroli administratora. Przenośne API PHP nie zapewnia tutaj pełnej ochrony przed wszystkimi race conditions filesystemu odpowiadającej `open(..., O_NOFOLLOW)`.

## Zaimplementowany zakres Etapu 4

Standalone builder katalogu jest osobną warstwą orkiestracji. Scanner pozostaje niezależny i odpowiada za bezpieczne przeglądanie katalogu, typ wpisów, parsowanie filename, sortowanie i grupowanie kandydatów. Validator pozostaje niezależny i odpowiada wyłącznie za ograniczoną walidację wskazanego pliku jako PDF candidate.

Builder:

- przyjmuje zaufaną ścieżkę katalogu z przyszłej warstwy aplikacji lub konfiguracji, a nie bezpośrednio z requestu HTTP;
- najpierw uruchamia scanner i zachowuje jego directory-level error bez wykonywania walidacji dokumentów po takim błędzie;
- przekazuje do validatora wyłącznie dokumenty rozpoznane przez scanner, budując ścieżkę z zaufanego katalogu i parser-approved filename;
- zachowuje kolejność dokumentów zwróconą przez scanner i usuwa z finalnego katalogu kandydatów odrzuconych przez validator;
- łączy issues scannera i validatora jako nazwę wpisu oraz maszynowo czytelny kod błędu, po czym sortuje całość deterministycznie przez binarne `strcmp()`;
- filtruje istniejące grupy scannera do zaakceptowanych dokumentów, zachowuje ich kolejność i pomija grupy, które stały się puste;
- traktuje zmianę, usunięcie lub zastąpienie pliku pomiędzy scanem i validation jako entry-level issue zwrócone przez validator, bez przerywania całego katalogu;
- nie przechowuje ani nie udostępnia ścieżki katalogu, ścieżki pliku ani targetu symlinka w publicznym modelu wyniku.

Finalny katalog zawiera wyłącznie **validated PDF candidates**, które przeszły walidację nazwy, typu wpisu i ograniczone kontrole validatora PDF. Nie oznacza to skanowania malware, sanitizacji PDF, pełnej walidacji struktury dokumentu ani gwarancji bezpieczeństwa. Pipeline nie wykonuje zawartości plików, nie używa WordPress API i nie integruje się jeszcze z katalogiem uploads WordPressa.

## Zaimplementowany zakres Etapu 5

Warstwa WordPress integration jest oddzielona od standalone komponentów Etapów 1–4. Żadna klasa parsera, scannera, validatora, buildera ani ich modeli wyniku nie korzysta z WordPress API.

`ZFDZ_WordPress_Menu_Storage`:

- pobiera bieżący uploads `basedir` przez `wp_get_upload_dir()` i defensywnie sprawdza odpowiedź oraz błąd WordPress uploads;
- wyznacza stałe, niekonfigurowalne ścieżki `zywienie-dla-zdrowia/jadlospisy` bez wartości z requestu HTTP i bez założenia standardowego `wp-content/uploads`;
- odrzuca bezpośrednie symlinki oraz konflikty typu wpisu dla zarządzanego root i katalogu `jadlospisy`, bez zakazywania symlinków wyżej w konfiguracji uploads;
- zwraca stabilne `WP_Error`: `uploads_unavailable`, `storage_unsafe_symlink`, `storage_path_conflict`, `storage_create_failed` lub `storage_not_readable`, bez absolutnej ścieżki w komunikacie lub error data;
- udostępnia `get_menu_directory_path()` bez tworzenia katalogu oraz idempotentne `ensure_menu_directory()` korzystające z `wp_mkdir_p()`;
- po utworzeniu sprawdza typ i czytelność katalogu, ale nie wymaga `is_writable()`, ponieważ dokumenty mogą być dostarczane przez osobne konto systemowe.

Activation hook wywołuje `ensure_menu_directory()`. Błąd przerywa aktywację przez krótki, tłumaczalny komunikat `wp_die()` sugerujący kontrolę konfiguracji i uprawnień uploads, bez ujawniania ścieżki. Ponowna aktywacja nie usuwa ani nie nadpisuje istniejącego prawidłowego katalogu. Plugin nie rejestruje deactivation hooka, nie dodaje `uninstall.php` i nie usuwa dokumentów.

`ZFDZ_WordPress_Menu_Catalog_Provider` pobiera ścieżkę ze storage i dopiero po jawnym `get_catalog()` przekazuje ją do standalone `ZFDZ_Menu_Catalog_Builder`. Błąd storage jest konwertowany na `ZFDZ_Menu_Catalog_Result::from_directory_error()`, dzięki czemu przyszli konsumenci otrzymają jeden typ wyniku. Provider nie tworzy brakującego katalogu podczas odczytu; jego usunięcie po aktywacji prowadzi do istniejącego `directory_not_found`. `create_default()` jest prostą fabryką konkretnych zależności, a nie service containerem.

Samo załadowanie pluginu rejestruje activation hook i ładuje klasy, ale nie tworzy katalogu, nie skanuje filesystemu i nie waliduje PDF. Publiczne URL-e, panel, shortcode’y, frontend i cache nie są częścią Etapu 5.

## Zaimplementowany zakres Etapu 6

Cache katalogu jest oddzielną, WordPress-specific warstwą korzystającą z Transients API. Standalone komponenty Etapów 1–4 pozostają niezależne od WordPress API, a provider Etapu 5 nadal jest źródłem świeżego katalogu.

`ZFDZ_WordPress_Menu_Catalog_Cache`:

- korzysta ze stałego, wersjonowanego i niezależnego od requestu klucza `zfdz_menu_catalog_v1`;
- przechowuje bez ręcznej serializacji wyłącznie poprawny `ZFDZ_Menu_Catalog_Result`, również gdy zawiera entry-level issues;
- używa domyślnego TTL około pięciu minut;
- nie cache’uje directory-level failures ani obiektów `WP_Error`;
- traktuje nieoczekiwany typ transientu jako uszkodzony lub nieaktualny cache, usuwa go i zwraca cache miss;
- traktuje zapis i usunięcie transientu jako operacje best-effort, które nie blokują zwrócenia świeżego katalogu.

`ZFDZ_WordPress_Menu_Catalog_Service`:

- dla `get_catalog()` zwraca poprawny cache hit, a po cache miss pobiera wynik z istniejącego providera i zapisuje go tylko wtedy, gdy jest successful;
- dla `refresh_catalog()` najpierw usuwa poprzedni cache, następnie wymusza świeży odczyt providera i cache’uje wyłącznie successful result;
- dla `clear_cache()` tylko usuwa transient, bez skanowania filesystemu, tworzenia katalogów lub modyfikowania dokumentów;
- udostępnia `create_default()` korzystające z zaakceptowanej fabryki providera bez ponownego składania standalone pipeline.

Transient przechowuje wyłącznie istniejący publiczny model katalogu bez ścieżek filesystemu, credentials lub danych pochodzących z requestu. Samo załadowanie pluginu i utworzenie serwisu nie odczytuje ani nie zapisuje transientu, nie skanuje katalogu i nie waliduje PDF. Operacje rozpoczynają się dopiero po jawnym wywołaniu metody serwisu. Etap 6 nie dodaje panelu ani przycisku ręcznego odświeżania.

## Zaimplementowany zakres Etapu 7

Etap 7 dodaje główną pozycję menu **Żywienie dla Zdrowia** oraz pierwszą stronę administracyjną **Status publikacji**, dostępną wyłącznie z capability `manage_options`. Rejestracja hooków `admin_menu` i `admin_post_zfdz_refresh_menu_catalog` nie pobiera katalogu ani transientu podczas ładowania pluginu.

Strona statusu:

- pobiera dane wyłącznie przez `ZFDZ_WordPress_Menu_Catalog_Service::get_catalog()` i nie odwołuje się bezpośrednio do storage, scannera, validatora ani Transients API;
- klasyfikuje moduł jadłospisów jako **Błąd** dla directory failure, **Wymaga uwagi** dla successful catalog z issues albo **OK** dla successful catalog bez issues;
- pokazuje liczbę poprawnych dokumentów, okresów i problemów z jednego wyniku katalogu;
- mapuje aktualne kody scannera, parsera, validatora i storage na bezpieczne polskie komunikaty;
- wykonuje escaping nazw wpisów pochodzących z filesystemu i nie pokazuje ścieżek, targetów symlinków ani URL-i dokumentów;
- korzysta z semantycznych nagłówków, natywnej tabeli WordPress oraz tekstowych etykiet statusu bez własnego CSS i JavaScriptu.

Ręczne odświeżanie:

- używa formularza POST kierowanego do `admin-post.php` ze stałą akcją `zfdz_refresh_menu_catalog`;
- niezależnie sprawdza `manage_options` oraz nonce akcji `zfdz_refresh_menu_catalog`;
- wywołuje `ZFDZ_WordPress_Menu_Catalog_Service::refresh_catalog()` dopiero po obu kontrolach;
- realizuje POST/Redirect/GET przez `wp_safe_redirect()` do URL zbudowanego przez `admin_url()` i `add_query_arg()`;
- przekazuje po redirect wyłącznie status `success` albo `error`, a renderer sanityzuje go przez `wp_unslash()` i `sanitize_key()` oraz sprawdza whitelistę;
- nie przyjmuje filename, ścieżki, URL ani nazwy modułu i nie tworzy, nie modyfikuje ani nie usuwa dokumentów.

Status **OK** w Etapie 7 oznacza wyłącznie, że katalog jest technicznie dostępny i nie zawiera wykrytych problemów scannera lub validatora. Nie oznacza, że istnieje aktualny jadłospis, opublikowano wszystkie wymagane materiały ani że organizacja spełnia wymogi prawne. Panel pozostaje technicznym narzędziem diagnostycznym, a publiczny frontend nie jest częścią tego etapu.

## Zaimplementowany zakres Etapu 8

Standalone `ZFDZ_Menu_Period_Classifier` przyjmuje istniejące grupy `ZFDZ_Menu_Period_Group` oraz jawnie przekazaną datę odniesienia `today` w formacie `YYYY-MM-DD`. Nie korzysta z WordPress API, systemowego zegara, `date()`, locale ani metadata filesystemu. Defensywnie sprawdza format i rzeczywistą wartość kalendarzową daty odniesienia, a naruszenie tego programistycznego kontraktu zgłasza przez `InvalidArgumentException`.

Classifier tworzy niezmienny `ZFDZ_Menu_Period_Classification` zgodnie z regułami:

- **current**: `start_date <= today <= end_date`;
- **upcoming**: `start_date > today`;
- **archived**: `end_date < today`.

Granice okresu są inkluzywne. Każda prawidłowa grupa trafia dokładnie do jednej kategorii; kilka nakładających się grup może być jednocześnie aktualnych. Classifier zachowuje kolejność wejściową wewnątrz każdej kategorii, nie sortuje ponownie, nie zmienia grup i nie filtruje należących do nich dokumentów.

Integracja WordPress pobiera bieżący czas witryny przez `current_datetime()` dokładnie raz podczas renderowania poprawnego katalogu i przekazuje datę `YYYY-MM-DD` do standalone classifiera. Przy directory-level failure klasyfikacja nie jest wykonywana. Panel pokazuje datę odniesienia, liczby aktualnych, nadchodzących i archiwalnych okresów oraz osobną tekstową informację o obecności lub braku okresu obowiązującego dzisiaj.

Dotychczasowy status techniczny **OK / Wymaga uwagi / Błąd** zachowuje semantykę Etapu 7. Brak aktualnego okresu nie jest directory error ani błędem technicznym i nie zmienia statusu **OK** dla poprawnego katalogu bez issues. Informacja ta nie stanowi automatycznej oceny realizacji obowiązków prawnych.

Klasyfikacja jest wykonywana po pobraniu cached `ZFDZ_Menu_Catalog_Result`, nie jest zapisywana w transientach i nie zmienia klucza `zfdz_menu_catalog_v1`. Zmiana dnia w strefie czasu witryny WordPress nie wymaga odświeżenia ani invalidacji katalogu. Etap 8 nie dodaje nowych request inputs, formularzy, nonce, capabilities, URL-i dokumentów, zapisów do bazy, transientów, CSS ani JavaScriptu.

## Zaimplementowany zakres Etapu 9

Etap 9 dodaje bezparametrowy shortcode `[zfdz_jadlospisy]`. Rejestracja klasy `ZFDZ_WordPress_Menu_Shortcode` podczas ładowania pluginu jedynie dodaje hook `init`; dopiero callback `init` rejestruje shortcode. Sam bootstrap nie pobiera katalogu, transientu ani bieżącej daty i nie generuje URL-i dokumentów.

Renderer shortcode:

- pobiera katalog wyłącznie przez `ZFDZ_WordPress_Menu_Catalog_Service::get_catalog()` i nie wykonuje automatycznego `refresh_catalog()`;
- dla directory-level failure zwraca krótki, tłumaczalny komunikat niedostępności bez kodów błędów, nazw wpisów lub ścieżek filesystemu;
- dla successful catalog pobiera `current_datetime()` dokładnie raz, przekazuje datę `YYYY-MM-DD` do istniejącego standalone classifiera i renderuje wyłącznie grupy current oraz upcoming;
- zachowuje kolejność current z katalogu, a kopię upcoming odwraca, aby najbliższy przyszły okres był pierwszy; nie zmienia classifiera ani globalnego sortowania katalogu;
- grupuje wiele dokumentów pod jednym nagłówkiem dokładnego okresu i zachowuje kolejność dokumentów z `ZFDZ_Menu_Period_Group`;
- pokazuje obie sekcje także wtedy, gdy są puste, oraz nie renderuje archived groups, entry-level issues, nazw błędnych wpisów ani metadata technicznych;
- formatuje zakres przez ustawienie `date_format`, `wp_date()` i `wp_timezone()`, z bezpiecznym fallbackiem do `YYYY-MM-DD`, a dla okresu jednodniowego pokazuje jedną datę;
- działa bez własnego CSS, JavaScriptu, parametrów shortcode, request inputs, cookies, zewnętrznych requestów i operacji zmieniających stan.

`ZFDZ_WordPress_Menu_Storage::get_menu_directory_url()` pobiera defensywnie `baseurl` z `wp_get_upload_dir()` i buduje stały URL `zywienie-dla-zdrowia/jadlospisy` bez tworzenia katalogu lub skanowania filesystemu. Link dokumentu powstaje wyłącznie z tego URL oraz `rawurlencode()` oryginalnego filename zaakceptowanego przez pipeline. `href`, nazwa dokumentu i etykieta okresu są escapowane podczas renderowania; filesystem paths nie trafiają do HTML.

Shortcode renderuje wyłącznie validated PDF candidates, ale nie określa ich jako bezpieczne, wolne od malware ani w pełni poprawne PDF. Pominięcie archiwalnych dokumentów oznacza tylko brak wygenerowanych linków w tym widoku i **nie jest mechanizmem kontroli dostępu**. Plik w publicznym WordPress uploads może pozostać dostępny przez znany bezpośredni URL. Etap 9 nie dodaje private storage, proxy pobierania, blokowania URL-i, reguł `.htaccess`, sanitizacji PDF ani skanowania antywirusowego.

Shortcode konsumuje cached catalog, a klasyfikację wykonuje względem świeżej daty witryny przy każdym renderowaniu. Plik dostarczony poza WordPressem może pojawić się po wygaśnięciu około pięciominutowego cache lub wcześniej po ręcznym odświeżeniu w panelu. Klucz `zfdz_menu_catalog_v1`, format transientu i kontrakty standalone pipeline pozostają bez zmian.

## Cel

Żywienie dla Zdrowia będzie wtyczką WordPress wspierającą prowadzenie publicznej sekcji:

> Żywienie dla zdrowia

Projekt ma ułatwić placówkom medycznym publikowanie wskazanych kategorii informacji w małej, przewidywalnej formie, bez budowania osobnego systemu zarządzania treścią.

Wtyczka jest narzędziem technicznym. Nie stanowi porady prawnej, nie ocenia, nie gwarantuje i nie certyfikuje zgodności działalności organizacji z prawem. Administrator organizacji odpowiada za treść, kompletność i prawidłowość publikowanych informacji.

## Kontekst regulacyjny

Projekt powstał jako narzędzie wspierające techniczną publikację informacji związanych z polską sekcją „Żywienie dla zdrowia” w kontekście rozporządzenia Ministra Zdrowia z 12 grudnia 2025 r. w sprawie standardu organizacyjnego żywienia zbiorowego w podmiocie leczniczym wykonującym działalność leczniczą w rodzaju świadczenia szpitalne (Dz.U. 2025 poz. 1780). [Oficjalny tekst aktu jest dostępny w ELI](https://eli.gov.pl/eli/DU/2025/1780/ogl).

Wymagania, których techniczną realizację ma wspierać planowany zakres projektu, obejmują w szczególności:

- publikację stosowanych jadłospisów;
- publikację ostatniego wyniku badania laboratoryjnego wraz z odniesieniem do jadłospisu;
- publikację materiałów edukacyjnych;
- umożliwienie anonimowego zgłaszania uwag.

Projekt nie będzie automatycznie interpretować przepisów ani prawnie walidować realizacji tych wymagań. Wtyczka nie stanowi porady prawnej, nie gwarantuje i nie certyfikuje zgodności. Organizacja publikująca odpowiada za treść, kompletność oraz sposób realizacji obowiązków.

## Planowane wymagania środowiskowe

- WordPress 6.8 lub nowszy.
- PHP 8.2 lub nowsze.
- PHP 8.3 lub nowsze jest zalecane.
- Composer 2 jest wymagany wyłącznie do pracy developerskiej; wtyczka nie ma zależności Composer runtime.

## Planowane moduły v1.0

1. **Jadłospisy** — prezentacja dokumentów z jadłospisami.
2. **Wyniki badań laboratoryjnych** — prezentacja dokumentów z wynikami badań.
3. **Materiały edukacyjne** — prezentacja materiałów przeznaczonych dla odbiorców publicznych.
4. **Link do anonimowego formularza uwag / ankiety** — prezentacja odnośnika skonfigurowanego przez administratora.

Określenie ankiety w interfejsie może odzwierciedlać zamierzony sposób jej użycia, ale wtyczka nie może deklarować ani gwarantować, że zewnętrzny formularz rzeczywiście jest anonimowy. Ocena zewnętrznej usługi pozostaje po stronie administratora.

## Źródło dokumentów

Zaimplementowany katalog jadłospisów znajduje się pod bieżącym uploads `basedir` zwróconym przez WordPress API:

```text
<WordPress uploads basedir>/
└── zywienie-dla-zdrowia/
    └── jadlospisy/
```

Etap 5 tworzy wyłącznie root pluginu i `jadlospisy/`. Nadal planowane podkatalogi pozostałych modułów:

```text
badania/
materialy/
```

Standalone scanner Etapu 2 i pipeline katalogu Etapu 4 nadal nie znają WordPress API. Storage Etapu 5 zarządza lokalizacją i lifecycle katalogu, a provider przekazuje ustaloną ścieżkę do standalone buildera dopiero na żądanie.

WordPress-specific serwis Etapu 6 korzysta z providera i cache transientów. Nie zmienia publicznego modelu katalogu ani kontraktów standalone pipeline.

Publiczny shortcode Etapu 9 pobiera URL katalogu osobno z uploads `baseurl`. Nie przekształca ścieżki `basedir` na URL i nie umieszcza ścieżek filesystemu w HTML.

### Planowana konwencja nazw jadłospisów

```text
YYYY-MM-DD_YYYY-MM-DD_nazwa.pdf
```

Dwie daty oznaczają planowany początek i koniec okresu obowiązywania dokumentu.

Zaimplementowane parser i scanner Etapów 1–2:

- odczytują datę początku i końca z nazwy pliku;
- grupują dokumenty dotyczące tego samego okresu;
- pozwalają na wiele diet lub dokumentów dla tego samego okresu;
- sortują dokumenty według dat wynikających z nazwy, a nie według `filemtime`;
- oddzielają poprawne dokumenty od nierozpoznanych wpisów bez przerywania całego skanowania.

Pipeline Etapu 4 dodatkowo odrzuca z finalnego katalogu kandydatów, którzy nie przeszli ograniczonej walidacji PDF, filtruje ich grupy okresów oraz zachowuje scanner issues razem z validation issues.

Classifier Etapu 8 dzieli wynikowe grupy na aktualne, nadchodzące i archiwalne według jawnie przekazanej daty, zachowując kolejność katalogu. W integracji WordPress datą odniesienia jest bieżąca data witryny zwrócona przez `current_datetime()`.

Nadal planowane są:

- integracja zapewniająca, że dokumenty nie są automatycznie usuwane;
- bezpieczne pomijanie nierozpoznanych nazw na publicznym frontendzie;
- informowanie administratora o nierozpoznanych dokumentach bez powodowania błędu frontendu.

Walidacja nazwy ma uwzględniać co najmniej następujące przypadki błędne:

- nieprawidłowy format lub nieistniejąca wartość daty;
- data końcowa wcześniejsza od daty początkowej;
- brak wymaganej części nazwy;
- niedozwolone rozszerzenie.

### Planowana konwencja nazw badań

```text
YYYY-MM-DD_nazwa.pdf
```

Data będzie metadanym wyprowadzanym z nazwy pliku zgodnie z regułami przyszłego komponentu.

Plugin ma docelowo:

- identyfikować najnowszy wynik na podstawie daty zapisanej w nazwie;
- umożliwiać administratorowi przypisanie wyniku do odpowiedniego jadłospisu lub pozycji jadłospisu;
- prezentować publicznie wynik wraz z informacją, którego jadłospisu dotyczy;
- ostrzegać administratora, jeżeli najnowszy wynik nie został prawidłowo powiązany;
- nie interpretować treści PDF ani nie oceniać wyniku badania.

Nazwy plików zawsze będą traktowane jako niezaufane dane wejściowe. Szczegółowe reguły parsera i scannera wyników badań zostaną określone przed implementacją tego modułu.

### Materiały edukacyjne

- Dokumenty będą znajdować się w podkatalogu `materialy/`.
- Data w nazwie pliku nie będzie wymagana.
- Plugin będzie prezentować materiały, ale nie będzie oceniał ich wartości naukowej.
- Odpowiedzialność za treść, źródła i piśmiennictwo pozostanie po stronie organizacji publikującej.

### Ankieta

- v1.0 będzie przechowywać wyłącznie URL formularza ustawiony przez administratora.
- Plugin nie będzie zbierać odpowiedzi ani pośredniczyć w ich przesyłaniu.
- Plugin nie będzie deklarować, że zewnętrzny formularz faktycznie jest anonimowy.

## Planowane dostarczanie dokumentów i SFTP

- Plugin nie będzie implementować serwera ani klienta SFTP.
- SFTP jest jednym z przewidywanych zewnętrznych sposobów dostarczania plików bezpośrednio do `<WordPress uploads basedir>/zywienie-dla-zdrowia/jadlospisy/`.
- Administrator serwera odpowiada za konfigurację osobnego konta SFTP z dostępem ograniczonym wyłącznie do katalogu dokumentów. Credentials i konfiguracja rzeczywistego wdrożenia nie mogą trafić do publicznego repozytorium.
- Plugin powinien działać identycznie niezależnie od tego, czy plik został dostarczony przez SFTP, czy inną bezpieczną metodę poza WordPressem.
- Plik dostarczony poza WordPressem pojawi się w katalogu najpóźniej po wygaśnięciu około pięciominutowego cache albo wcześniej po jawnym programowym wywołaniu `refresh_catalog()`.

## Panel administracyjny

Zaimplementowana nazwa pierwszej strony:

```text
Status publikacji
```

Etap 7 pokazuje dla modułu jadłospisów liczbę poprawnie rozpoznanych dokumentów, okresów i problemów oraz umożliwia chronione ręczne odświeżenie katalogu. Etap 8 uzupełnia stronę o datę odniesienia z czasu witryny WordPress, liczby aktualnych, nadchodzących i archiwalnych okresów oraz informację, czy istnieje co najmniej jeden okres obowiązujący dzisiaj. Listy okresów i dokumentów pozostają planowane. Docelowo rozbudowany Status publikacji ma dodatkowo pokazywać co najmniej:

- najnowszy wynik badania;
- stan powiązania badania z jadłospisem;
- liczbę materiałów edukacyjnych;
- stan konfiguracji ankiety.

Status techniczny ma przedstawiać wyłącznie dostępność katalogu i wykryte problemy scannera lub validatora. Osobna informacja o braku okresu obowiązującego dzisiaj nie oznacza automatycznie błędu pluginu, błędu serwera ani naruszenia prawa. Panel nie jest oceną zgodności z prawem.

## Planowana architektura

- Filesystem jako źródło dokumentów.
- WordPress Options API do konfiguracji.
- Zaimplementowany cache katalogu jadłospisów przez WordPress Transients API.
- Brak własnych tabel bazy danych w v1.0.
- Brak Custom Post Types w v1.0.
- Brak REST API w v1.0.
- Brak frameworków JavaScript.
- Brak przechowywania odpowiedzi ankiet.
- Brak telemetryki.
- Brak automatycznego usuwania dokumentów.
- Brak zależności runtime bez wyraźnej i udokumentowanej potrzeby.

Architektura ma pozostać możliwie prosta. Nowe warstwy i abstrakcje powinny powstawać wyłącznie wtedy, gdy rozwiążą konkretną potrzebę zaimplementowanego kodu.

## Cache katalogu jadłospisów

- Zaimplementowany cache korzysta z WordPress Transients API i stałego klucza `zfdz_menu_catalog_v1`.
- Successful `ZFDZ_Menu_Catalog_Result` jest przechowywany domyślnie przez około 5 minut; directory failures nie są cache’owane.
- `refresh_catalog()` programowo wymusza świeży odczyt, a `clear_cache()` wyłącznie usuwa transient.
- Zaimplementowana w Etapie 7 kontrolka administratora wywołuje `refresh_catalog()` wyłącznie przez chroniony POST.
- Klasyfikacja okresów Etapu 8 jest obliczana po odczycie katalogu, nie trafia do transientu i dlatego zmiana bieżącej daty nie wymaga invalidacji cache.
- Shortcode Etapu 9 korzysta wyłącznie z `get_catalog()`; nie wymusza refresh i pokazuje nowe pliki po wygaśnięciu cache lub po ręcznym odświeżeniu administratora.
- Podstawowa implementacja nie wymaga filesystem watcherów, daemonów, cronów ani własnej kolejki.

## Integracja ze stroną WordPress

- Plugin nie powinien automatycznie tworzyć ani modyfikować publicznych stron podczas aktywacji.
- Administrator sam umieszcza odpowiedni shortcode na wybranej stronie.
- Rozwiązanie powinno pozostać możliwie niezależne od Gutenberga, Elementora, WPBakery i konkretnego motywu.

Zaimplementowany shortcode:

```text
[zfdz_jadlospisy]
```

Nie przyjmuje parametrów, pokazuje aktualne i nadchodzące grupy jadłospisów oraz nie generuje publicznego archiwum. Korzysta z cached catalog i z ręcznego odświeżania dostępnego w panelu administratora.

Pozostałe planowane shortcode’y:

```text
[zywienie_dla_zdrowia]
[zfdz_badania]
[zfdz_materialy]
[zfdz_ankieta]
```

Powyższe pozostałe shortcode’y są **planowane dla v1.0 i nie są zaimplementowane w obecnej wersji developerskiej**. Ich dokładne atrybuty, zachowanie, semantyka HTML oraz komunikaty błędów zostaną określone przed implementacją.

## Bezpieczeństwo

Implementacja v1.0 ma przestrzegać następujących zasad:

- walidować dane wejściowe i sanityzować je odpowiednio do oczekiwanego typu;
- wykonywać escaping danych wyjściowych możliwie późno i zgodnie z kontekstem;
- używać WordPress capabilities do ochrony operacji uprzywilejowanych;
- wymagać i weryfikować nonce dla formularzy administracyjnych i operacji zmieniających stan;
- chronić operacje na filesystemie przed path traversal;
- nie ufać nazwom plików ani zawartości uploadów;
- nie wykonywać zawartości katalogu uploads;
- nie używać `eval`;
- nie stosować dynamicznych `include` ani `require` z katalogów uploadów;
- nie przechowywać credentials w repozytorium;
- nie dodawać zewnętrznych zależności runtime bez uzasadnienia;
- nie wysyłać telemetryki;
- nie wykonywać zewnętrznych requestów bez jawnej potrzeby funkcjonalnej i udokumentowania skutków dla prywatności.

## Prywatność

Projekt v1.0 ma być zaprojektowany tak, aby sama wtyczka:

- nie przechowywała danych pacjentów;
- nie przechowywała odpowiedzi ankiet;
- nie instalowała własnych cookies;
- nie dodawała telemetryki;
- nie wysyłała danych do usług zewnętrznych.

Moduł ankiety będzie jedynie odnośnikiem do adresu skonfigurowanego przez administratora. Wtyczka nie będzie pośredniczyć w przesyłaniu odpowiedzi i nie będzie deklarować, że zewnętrzny formularz rzeczywiście jest anonimowy.

Repozytorium nie może zawierać danych pochodzących z rzeczywistych wdrożeń, w tym identyfikujących nazw, domen, adresów, ścieżek, credentials, dokumentów, danych pacjentów lub pracowników ani screenshotów.

## Dostępność i frontend

Wymagania dla publicznego frontendu:

- semantyczny HTML;
- obsługa klawiatury;
- widoczny focus;
- informacja nieprzekazywana wyłącznie kolorem;
- responsywność;
- neutralny CSS;
- brak globalnego resetu CSS;
- brak narzucania `font-family`;
- klasy CSS z prefiksem `zfdz-`;
- podstawowe działanie bez JavaScriptu.

Formalna zgodność z WCAG nie będzie deklarowana bez przeprowadzenia i udokumentowania odpowiednich testów.

## Kod, język i i18n

- PHP ma być zgodny z WordPress Coding Standards.
- Techniczne identyfikatory i komentarze developerskie mają być angielskie.
- Interfejs użytkownika ma być przede wszystkim polski.
- Teksty użytkowe mają od początku korzystać z mechanizmów i18n WordPress.
- Text domain: `zywienie-dla-zdrowia`.
- Preferowany prefix globalnych identyfikatorów: `ZFDZ_` dla klas i stałych oraz `zfdz_` dla identyfikatorów pisanych małymi literami.
- Nie należy tworzyć globalnych funkcji bez rzeczywistej potrzeby.

## Poza zakresem v1.0

Poza elementami architektonicznymi wymienionymi wcześniej, v1.0 nie obejmuje mechanizmu aktualizacji wtyczki, własnego routera, frameworka aplikacyjnego, JavaScript build pipeline, lokalnego środowiska WordPress ani automatycznej oceny prawnej treści.

## Kryteria ukończenia przyszłego v1.0.0

Poniższe kryteria dotyczą przyszłego wydania `1.0.0`, a nie obecnej wersji developerskiej. Przed uznaniem v1.0 za ukończoną wymagane będą co najmniej:

- instalacja pluginu z pliku ZIP na czystym WordPressie bez błędów;
- poprawna obsługa planowanych katalogów dokumentów;
- wykrywanie dokumentów dostarczonych poza WordPressem;
- prawidłowe parsowanie i sortowanie jadłospisów według dat z nazw plików;
- grupowanie wielu diet lub dokumentów dla tego samego okresu;
- bezpieczna obsługa pustych katalogów, niedozwolonych rozszerzeń i błędnych nazw;
- automatyczne wykrywanie najnowszego wyniku badania;
- możliwość przypisania badania do jadłospisu lub jego pozycji;
- publiczna prezentacja wyniku wraz z informacją o powiązanym jadłospisie;
- prezentacja materiałów edukacyjnych;
- konfiguracja linku ankiety bez zbierania odpowiedzi przez plugin;
- działanie głównego i modułowych shortcode’ów;
- responsywność oraz podstawowe działanie publicznego frontendu bez JavaScriptu;
- brak znanych podatności bezpieczeństwa;
- przechodzące wszystkie dostępne kontrole jakości;
- test instalacji i działania na czystym WordPressie;
- test na stagingu z rzeczywistym motywem przed wdrożeniem produkcyjnym, bez kopiowania danych tego wdrożenia do publicznego repozytorium;
- potwierdzenie braku danych rzeczywistego wdrożenia w publicznym repozytorium.

## Kryterium Etapu 0

Etap 0 kończy się na pasywnym pliku głównym pluginu, dokumentacji i działających narzędziach jakości. Nie obejmuje skanowania katalogów, obsługi dokumentów, ustawień, shortcode’ów, cache, uploadu, panelu administracyjnego ani żadnego innego zachowania biznesowego.
