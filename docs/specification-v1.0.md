# Żywienie dla Zdrowia — robocza specyfikacja v1.0

## Status dokumentu

To robocza specyfikacja planowanego zakresu v1.0. Etap 0 został zakończony. Etap 1 dostarczył niezależny parser nazw jadłospisów i model dokumentu, a Etap 2 — niezależny scanner katalogu wraz z sortowaniem, grupowaniem i raportowaniem problematycznych wpisów. Integracja z katalogiem uploads WordPressa, walidacja MIME i zawartości PDF, moduły publiczne, konfiguracja, cache i shortcode’y pozostają planowane.

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

Scanner nie otwiera dokumentów, nie odczytuje ich zawartości, nie analizuje MIME ani magic bytes i nie potwierdza, że kandydat z rozszerzeniem `.pdf` jest rzeczywistym dokumentem PDF. Taka walidacja pozostaje wymagana przed przyszłym publicznym udostępnianiem plików. Scanner nie korzysta z WordPress API, nie tworzy katalogów i nie modyfikuje ani nie usuwa wpisów.

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

## Planowane źródło dokumentów

Źródłem dokumentów będzie filesystem w katalogu:

```text
wp-content/uploads/zywienie-dla-zdrowia/
```

Planowane podkatalogi:

```text
jadlospisy/
badania/
materialy/
```

Standalone scanner Etapu 2 nie tworzy tych katalogów i nie jest jeszcze połączony z katalogiem uploads WordPressa. Integracja oraz zarządzanie wymaganymi katalogami pozostają planowane.

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

Nadal planowane są:

- klasyfikacja okresu jako aktualnego, nadchodzącego lub archiwalnego z użyciem czasu WordPressa;
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
- SFTP jest jednym z przewidywanych zewnętrznych sposobów dostarczania plików do katalogu dokumentów.
- Dokumentacja wdrożeniowa powinna rekomendować osobne konto z dostępem ograniczonym wyłącznie do katalogu dokumentów. Credentials i konfiguracja rzeczywistego wdrożenia nie mogą trafić do publicznego repozytorium.
- Plugin powinien działać identycznie niezależnie od tego, czy plik został dostarczony przez SFTP, czy inną bezpieczną metodę poza WordPressem.

## Planowany panel administracyjny

Planowana nazwa dashboardu:

```text
Status publikacji
```

Status publikacji ma docelowo pokazywać co najmniej:

- liczbę poprawnie rozpoznanych jadłospisów;
- nierozpoznane pliki;
- najnowszy wynik badania;
- stan powiązania badania z jadłospisem;
- liczbę materiałów edukacyjnych;
- stan konfiguracji ankiety.

Status ma przedstawiać wyłącznie informacje techniczne i nie jest oceną zgodności z prawem.

## Planowana architektura

- Filesystem jako źródło dokumentów.
- WordPress Options API do konfiguracji.
- Transients API do cache.
- Brak własnych tabel bazy danych w v1.0.
- Brak Custom Post Types w v1.0.
- Brak REST API w v1.0.
- Brak frameworków JavaScript.
- Brak przechowywania odpowiedzi ankiet.
- Brak telemetryki.
- Brak automatycznego usuwania dokumentów.
- Brak zależności runtime bez wyraźnej i udokumentowanej potrzeby.

Architektura ma pozostać możliwie prosta. Nowe warstwy i abstrakcje powinny powstawać wyłącznie wtedy, gdy rozwiążą konkretną potrzebę zaimplementowanego kodu.

## Planowany cache

- Cache będzie korzystać z Transients API.
- Lista dokumentów będzie przechowywana w krótkim cache, domyślnie przez około 5 minut.
- Administrator będzie mieć możliwość ręcznego odświeżenia listy dokumentów.
- Podstawowa implementacja nie będzie wymagać filesystem watcherów, daemonów ani własnej kolejki.

## Planowana integracja ze stroną WordPress

- Plugin nie powinien automatycznie tworzyć ani modyfikować publicznych stron podczas aktywacji.
- Administrator sam umieszcza odpowiedni shortcode na wybranej stronie.
- Rozwiązanie powinno pozostać możliwie niezależne od Gutenberga, Elementora, WPBakery i konkretnego motywu.

Planowane shortcode’y:

```text
[zywienie_dla_zdrowia]
[zfdz_jadlospisy]
[zfdz_badania]
[zfdz_materialy]
[zfdz_ankieta]
```

Wszystkie powyższe shortcode’y są **planowane dla v1.0 i nie są zaimplementowane w obecnej wersji developerskiej**. Ich dokładne atrybuty, zachowanie, semantyka HTML oraz komunikaty błędów zostaną określone przed implementacją.

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
