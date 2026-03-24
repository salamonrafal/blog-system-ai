# VERSION

## 2026-03-14

- Dodano zestaw testów jednostkowych dla `ArticleSlugger`, `ArticlePublisher`, `UserChecker` oraz `User`.
- Dodano prosty runner testów w `tests/run.php`.
- Uzupełniono `README.md` o instrukcję uruchamiania testów jednostkowych.
- Zmieniono testy jednostkowe, aby korzystały z paczki PHPUnit zamiast własnego runnera.
- Dodano konfigurację PHPUnit w `phpunit.xml.dist` oraz skrót `composer test` w `composer.json`.
- Zaktualizowano `README.md` o sposób uruchamiania testów przez PHPUnit.

## 2026-03-16

- Przebudowano warstwę wizualną aplikacji, dodając nowy motyw, skrypty interfejsu oraz zestaw assetów graficznych dla bloga i panelu administracyjnego.
- Odświeżono widoki panelu administracyjnego, w tym dashboard oraz ekrany listy, tworzenia i edycji artykułów.
- Usprawniono logowanie oraz nawigację w aplikacji, dodając elementy prowadzące użytkownika po blogu i panelu admina.
- Dodano komunikat prywatności oraz rozszerzono skróty administracyjne w layoucie.
- Rozszerzono interfejs o obsługę internacjonalizacji i poprawki dostępności w widokach bloga, panelu administracyjnego i formularzach.
- Zmieniono układ strony głównej bloga, widoku artykułu i metadanych, a część skrótów administracyjnych przeniesiono do bardziej odpowiednich miejsc.
- Wzmocniono walidację formularza artykułu oraz obsługę błędów, w tym licznik znaków i komunikaty przy polach formularza.
- Dodano sekcję skrótów administracyjnych z szybkimi ustawieniami i opcjami logowania na urządzeniu oraz dopracowano zachowanie interfejsu po stronie JavaScript.
- Rozbudowano listę artykułów w panelu administracyjnym o klikalny tytuł prowadzący do podglądu oraz zestaw akcji kontekstowych dla edycji, archiwizacji i usuwania wpisów.
- Dodano status `Zarchiwizowany`, logikę archiwizacji artykułów i możliwość trwałego usuwania tylko zarchiwizowanych wpisów wraz z potwierdzeniem w stylu interfejsu aplikacji.
- Ograniczono podgląd nieopublikowanych artykułów do zalogowanych użytkowników, zachowując publiczny dostęp dla wpisów opublikowanych.
- Dopracowano interfejs listy artykułów w panelu admina, w tym ikonowe akcje, wspólny customowy tooltip oraz poprawki układu tabeli i czytelności elementów sterujących.

## 2026-03-17

- Poprawiono kontrast akcji usuwania na `admin_article_index` w jasnym motywie, w tym ikonę kosza i przycisk `Usuń` w oknie potwierdzenia.
- Usunięto efekt podświetlenia oraz zmianę tła z przycisku kosza na liście artykułów, tak aby stan hover i focus pozostawał wizualnie neutralny.
- Dodano edytor podstawowego formatowania treści artykułów z paskiem narzędzi dla pogrubienia, kursywy, podkreślenia, wyrównania, cytatu, bloku kodu, linków, obrazów, nagłówków `H1-H7` oraz list punktowanych i numerowanych.
- Wprowadzono bezpieczne renderowanie zapisanej treści artykułu w stylu lekkiego Markdowna po stronie serwera wraz z nowym filtrem Twig i stylami dla sformatowanej zawartości.
- Dodano testy jednostkowe dla parsera formatowania artykułów, obejmujące renderowanie bloków, formatowania inline, wyrównania oraz zabezpieczenie przed surowym HTML.
- Zastąpiono tekstowe etykiety przycisków formatowania ikonami w formularzach tworzenia i edycji artykułu, zachowując opisy `aria-label` i podpowiedzi `title`.
- Dodano popup pomocy otwierany z ikony `?` w toolbarze edytora oraz usunięto stałą podpowiedź o formatowaniu spod pola treści.
- Przebudowano układ edytora treści tak, aby toolbar i `textarea` tworzyły jedno spójne pole z jednym obramowaniem i bardziej narzędziowym wyglądem ikon.
- Poprawiono czytelność okna pomocy formatowania, przenosząc przycisk zamknięcia do prawego górnego rogu i rozbijając opis na listę zasad oraz przykłady bloków.
- Usprawniono zachowanie edytora po kliknięciu formatowania, zachowując zaznaczenie i pozycję przewinięcia `textarea`, aby użytkownik nie tracił orientacji w treści.
- Dodano do artykułów opcjonalną grafikę nagłówkową z nowym polem w encji, formularzu, walidacją, tłumaczeniami oraz migracją bazy danych.
- Rozszerzono publiczny widok bloga o prezentację grafiki nagłówkowej na liście artykułów i w widoku `blog_show`, a także o wykorzystanie tej grafiki w metadanych `og:image` i `twitter:image`.
- Przebudowano hero artykułu na `blog_show`, nakładając tytuł na dolną część grafiki z półprzezroczystym tłem poprawiającym czytelność.
- Usunięto krótki opis z widoku `blog_show`, pozostawiając go do wykorzystania w innych miejscach, takich jak listing i meta opis.
- Dopracowano szczegóły układu widoku artykułu, w tym pełną szerokość belki tytułu na obrazku oraz odpowiedni górny odstęp sekcji z grafiką.
- Uporządkowano metadane pól formularza artykułu tak, aby podpowiedzi były wyrównane do lewej, a liczniki znaków do prawej.
- Dodano wyraźniejszy odstęp między polem `Grafika nagłówkowa` a polem `Treść`, poprawiając czytelność formularzy tworzenia i edycji.
- Wzbogacono podpowiedź pola `Grafika nagłówkowa` o ikonę oraz subtelny akcent wizualny, jednocześnie zachowując spójny układ z licznikiem znaków.
- Dodano przełącznik włączający i wyłączający grafikę nagłówkową artykułu wraz z nowym polem w encji, migracją oraz logiką domyślnej grafiki `/assets/img/default-headline-article-pixel-art.png` używanej, gdy opcja jest aktywna, a adres obrazka pozostaje pusty.
- Połączono przełącznik grafiki nagłówkowej i pole adresu w jedną sekcję formularza oraz dodano dynamiczne ukrywanie całego panelu adresu, podpowiedzi i licznika znaków po wyłączeniu tej opcji.
- Ujednolicono listę artykułów na `blog_index`, przenosząc tytuł na obszar headline także dla wpisów bez własnej grafiki dzięki dekoracyjnemu wypełnieniu zastępczemu.
- Dopracowano sekcję metadanych na ekranie edycji artykułu, wydzielając ją od formularza, a następnie upraszczając do czytelnego układu `etykieta -> wartość` bez efektu zagnieżdżonych paneli.
- Zwiększono wizualną separację między sekcją metadanych a formularzem edycji artykułu, dodając większy odstęp przed częścią formularza.
- Rozszerzono `admin_article_index` o akcję `Opublikuj` dostępną dla wszystkich wpisów poza już opublikowanymi oraz dodano obsługującą ją trasę `POST` w panelu administracyjnym.
- Uproszczono tabelę na `admin_article_index`, usuwając kolumny `Język` i `Slug` oraz porządkując liczbę widocznych kolumn w stanie pustej listy.
- Ustabilizowano układ listy artykułów w panelu admina, nadając kolumnie `Tytuł` stałą szerokość z zawijaniem dłuższych nazw wpisów.
- Dopracowano wyrównanie tabeli `admin_article_index`, centrując nagłówki, wartości w kolumnach `Status` i `Aktualizacja` oraz pionowo wyrównując zawartość komórek.
- Dodano subtelne wyróżnienie co drugiego wiersza na `admin_article_index`, dopasowane do jasnego i ciemnego motywu aplikacji.

## 2026-03-18

- Dodano nowy moduł ustawień bloga w panelu administracyjnym pod trasą `/admin/settings/blog` z formularzem do zarządzania tytułem bloga, opisem SEO strony głównej, obrazkiem społecznościowym oraz słowami kluczowymi SEO.
- Rozszerzono pływające menu `Szybkie akcje` w sekcji `Szybkie ustawienia`, dodając jako ostatni element odnośnik `Ustawienia bloga`.
- Wprowadzono nową encję `BlogSettings`, repozytorium, provider, formularz oraz migrację bazy danych dla trwałego przechowywania ustawień bloga.
- Podłączono ustawienia bloga do globalnych danych Twiga, dzięki czemu `app_name`, meta description, keywords oraz obrazy `og:image` i `twitter:image` są pobierane z konfiguracji zapisanej w bazie.
- Zaktualizowano stronę główną bloga i layout aplikacji tak, aby korzystały z konfigurowalnych metadanych SEO i podglądu linków dla portali społecznościowych.
- Poprawiono migrację `Version20260318110000`, usuwając nieprawidłowy zapis komentarzy typu Doctrine w SQL dla SQLite, który powodował błąd `SQLSTATE[HY000]: General error: 1 incomplete input`.
- Dodano testy jednostkowe dla nowej encji `BlogSettings`, providera `BlogSettingsProvider` oraz rozszerzenia Twig `AppGlobalsExtension`, potwierdzając poprawne wartości domyślne, normalizację danych, cache providera i eksport globali do widoków.
- Dodano konfigurowalną paginację listy artykułów na stronie głównej, wraz z nowym ustawieniem `Ilość artykułów na stronę` w `admin_blog_settings`, domyślną wartością `5`, migracją bazy oraz uproszczonym pagerem z ikonami i skróconą listą numerów stron.
- Ujednolicono zapis dat artykułów do strefy `UTC` dla pól `publishedAt`, `createdAt` i `updatedAt`, dopinając normalizację w encji `Article`, serwisie publikacji oraz testach jednostkowych.
- Dodano zapis strefy czasowej użytkownika do cookie `user_timezone` przy wejściu na stronę oraz nowy resolver po stronie PHP, dzięki czemu daty artykułów mogą być renderowane w strefie czasowej użytkownika.
- Dodano zapis języka użytkownika do cookie `user_language` oraz resolver języka po stronie PHP, aby logika formatowania dat była spójna między JavaScriptem a warstwą serwerową.
- Wprowadzono wspólny filtr Twig `user_date` do formatowania dat w strefie użytkownika i formacie zależnym od języka (`pl` / `en`), upraszczając szablony bloga i panelu administracyjnego oraz rozszerzając zestaw testów jednostkowych dla resolverów i rozszerzeń Twig.
- Rozszerzono parser i toolbar formatowania artykułów o `inline code`, wymuszoną nową linię, separator poziomy oraz tabele w stylu Markdown, a publiczny widok artykułu uzupełniono o stylowanie nowych elementów i testy jednostkowe renderera.
- Dodano obsługę bloku preformatowanego `:::pre ... :::`, który zachowuje układ i wcięcia treści bez traktowania jej jako blok kodu, wraz z nowym przyciskiem w edytorze, stylami i testem parsera.
- Poprawiono działanie przycisku `Blok preformatowany` w edytorze, aby opakowywał aktualnie zaznaczony blok treści zamiast zastępować go domyślnym przykładem.
- Dopracowano odstępy separatora poziomego w publicznym widoku artykułu, nadając mu własny rytm pionowy niezależny od ogólnego spacingu bloków treści.
- Przebudowano okno pomocy formatowania w formularzach tworzenia i edycji artykułu, dzieląc treść na zakładki `Podstawowe` i `Zaawansowane` oraz dodając przewijane wnętrze modala, dzięki czemu cała instrukcja mieści się w obrębie ekranu.

## 2026-03-19

- Poprawiono renderowanie treści bezpośrednio po tabelach, usuwając przedwczesne domykanie bloku tabeli i naprawiając obsługę wymuszonych nowych linii w akapicie następującym po tabeli.
- Uzupełniono testy parsera formatowania o przypadki graniczne dla akapitów po tabeli, w tym zachowanie wielu początkowych nowych linii renderowanych jako `<br>`.
- Dopracowano rytm pionowy bloków `blockquote` i `pre` na stronie artykułu, nadając im własne marginesy oraz zerując odstępy skrajne dla pierwszego i ostatniego elementu w treści.
- Naprawiono renderowanie linków i obrazów Markdown z adresami URL zawierającymi query string, eliminując podwójne encodowanie `&amp;` w atrybutach `href`, `src` i `alt`.
- Dodano podstawową dockerizację projektu z obrazem opartym o `php:8.4-fpm`, konfiguracją `nginx`, skryptem startowym kontenera oraz `.dockerignore` ograniczającym zbędne pliki w buildzie.
- Dostosowano uruchamianie aplikacji do środowiska kontenerowego, w tym nasłuch serwera developerskiego na `0.0.0.0` oraz domyślne wartości zmiennych środowiskowych w `.env.template`.
- Uzupełniono `README.md` o ręczne komendy Dockera do budowania obrazu, uruchamiania kontenera developerskiego, wejścia do środka oraz jego zatrzymywania i usuwania.

## 2026-03-22

- Wydzielono kontenerowy etap sprawdzania i przygotowania katalogów `var/cache` oraz `var/log` do osobnego skryptu `docker/scripts/checking-tasks.sh`, upraszczając `docker/scripts/entrypoint.sh` i porządkując logi startowe usług.
- Rozszerzono workflow `deploy-container` o uruchamianie kontenera z polityką restartu `unless-stopped` oraz dodano ignorowanie plików kopii zapasowych w katalogu `var/backup`.
- Poprawiono pusty stan listy opublikowanych artykułów na `blog_index` i `admin_dashboard`, zachowując spójne marginesy względem reszty głównego bloku oraz nadając komunikatowi styl wyróżnionego komunikatu informacyjnego.
- Doprecyzowano sortowanie listy opublikowanych artykułów na stronie głównej `blog_index`, tak aby wpisy były wyświetlane od najnowszej daty publikacji, z bezpiecznym fallbackiem do `createdAt`.
- Dodano na `admin_article_index` akcję `Eksport`, która zapisuje artykuł do wewnętrznej kolejki opartej na nowej tabeli `article_export_queue`, wraz z encją, repozytorium, migracją i zabezpieczeniem przed duplikowaniem otwartych zleceń eksportu.
- Rozszerzono pływające menu administracyjne w sekcji `Szybkie ustawienia` o przycisk `Stan kolejek` oraz dodano nową stronę `admin_queue_status`, pokazującą elementy oczekujące na przetworzenie przez zadanie w tle.
- Rozbudowano ekran `admin_queue_status` o usuwanie pojedynczych wpisów z kolejki, zbiorczą akcję `Wyczyść kolejkę`, stałą szerokość kolumny tytułu z obcinaniem długich nazw oraz uproszczony zestaw kolumn bez `Slug`.
- Ujednolicono komunikaty potwierdzające operacje niebezpieczne, przenosząc usuwanie artykułu i czyszczenie kolejki na wspólny komponent alertu z przyciskiem zamknięcia po prawej stronie, a następnie wyśrodkowując go na ekranie.
- Przeprojektowano komunikaty `flash`, przenosząc je do pływającego stosu przy górnej krawędzi strony, dodając przycisk zamknięcia dla każdego wpisu oraz dopracowując ich wygląd tak, aby bardziej przypominały komunikaty systemowe.
- Zmieniono ikonę akcji `Opublikuj` na liście artykułów, aby była wyraźnie odróżnialna od ikony używanej przez akcję `Eksport`.
- Dodano konsolowe zadanie `app:article-export:process-queue` wraz ze skrótem `composer article-export:process-queue`, które przetwarza kolejkę eksportów artykułów w tle i zapisuje rekordy wykonanych eksportów.
- Wprowadzono nową tabelę `article_export` z encją, repozytorium, enumami statusu i typu oraz migracją, aby przechowywać historię wygenerowanych eksportów wraz z liczbą artykułów i ścieżką do pliku.
- Dodano serwis generujący pliki eksportu JSON z kompletem danych artykułu, a format payloadu ujednolicono tak, aby pole `article` zawsze było tablicą i mogło w przyszłości obsłużyć wiele wpisów.
- Zmieniono mechanizm eksportu tak, aby każdy wpis z `article_export_queue` tworzył osobny plik oraz osobny rekord w tabeli `article_export`, bez blokowania pozostałych pozycji kolejki przy błędzie pojedynczego eksportu.
- Rozszerzono `README.md` o opis kolejki eksportów, ręczne uruchamianie konsumenta oraz obsługę eksportów z poziomu konsoli.
- Dodano nowy ekran `admin_article_export_index` z listą eksportów, pobieraniem plików i odnośnikiem `Eksporty` w pływającym menu administracyjnym w sekcji `Szybkie ustawienia`.
- Rozbudowano listę eksportów o stałą szerokość kolumny `Plik`, prezentację samej nazwy pliku, akcję usuwania pojedynczego eksportu z potwierdzeniem oraz zbiorczą akcję `Usuń wszystkie eksporty`, która usuwa wpisy i pliki z dysku.
- Uzupełniono pobieranie eksportu tak, aby po kliknięciu `Pobierz` status zmieniał się z `Nowy` na `Pobrany`, a odpowiedź wymuszała `Content-Type: application/json` bez zależności od komponentu `symfony/mime`.
- Przeniesiono katalog eksportów `var/exports` do konfiguracji YAML jako parametr `app.article_export_directory`, dzięki czemu lokalizację plików można zmienić bez modyfikowania kodu źródłowego.

## 2026-03-23

- Rozszerzono `admin_article_index` o możliwość zaznaczania wielu artykułów i zbiorczą akcję `Eksportuj zaznaczone`, która dodaje wybrane wpisy do kolejki eksportu z pominięciem pozycji już oczekujących.
- Dodano nową stronę `admin_article_import_index` z formularzem przesyłania pliku JSON do importu, nową tabelę `article_import_queue` oraz odnośnik `Importy` w pływającym menu administracyjnym.
- Rozszerzono ekran `admin_queue_status`, aby pokazywał oczekujące elementy kolejki importu obok eksportów i pozwalał usuwać oba typy wpisów.
- Dodano konsolowe zadanie `app:article-import:process-queue` wraz ze skrótem `composer article-import:process-queue`, które importuje artykuł z pliku eksportu JSON, aktualizuje istniejący wpis po `slug` albo tworzy nowy oraz zapisuje komunikat błędu w kolejce przy statusie `FAILED`.
- Uzupełniono `README.md` o nową sekcję `Import queue`, opis ręcznego uruchamiania importu artykułów oraz przykład użycia komendy importera w `crontab`.
- Rozbudowano ekran `admin_article_import_index` o pobieranie i usuwanie pojedynczych plików importu, zbiorczą akcję `Usuń wszystko` z potwierdzeniem, przycisk `Odśwież` w górnej sekcji oraz dopracowany układ kolumny `Plik źródłowy` ze stałą szerokością i tooltipem pokazującym nazwę oryginalną oraz nazwę pliku na serwerze w osobnych liniach.
- Dopracowano ekran `admin_article_export_index` oraz `admin_queue_status`, dodając przycisk `Odśwież` do górnej sekcji akcji i porządkując odstępy tak, aby układ był spójny zarówno dla pustych stanów, jak i dla list z danymi.
- Uszczelniono proces importu artykułów, dodając jawne błędy dla pustych pól `title`, `slug` i `content`, dla nieobsługiwanych wartości `language` i `status`, zachowując identyfikację artykułu wyłącznie po `slug` oraz gwarantując, że przy aktualizacji wpisu pole `createdAt` nie jest nadpisywane.
- Przebudowano pływające menu administracyjne, grupując `Stan kolejek`, `Importy` i `Eksporty` pod klikalnym rozwijanym elementem `Importy & Eksporty`, przeniesionym bezpośrednio za `Lista artykułów`.
- Ujednolicono wygląd nowego elementu `Importy & Eksporty` w pływającym menu, dopasowując rozmiar czcionki do pozostałych pozycji oraz zachowanie rozwijanego panelu do obsługi kliknięcia zamiast najechania.
- Rozszerzono testy jednostkowe `ArticleImportProcessor` o nieprzetestowane wcześniej ścieżki błędów, obejmujące brakujący lub niedozwolony plik importu, uszkodzony JSON, nieobsługiwany format i wersję, niepoprawną strukturę payloadu oraz błędne typy i wartości pól takich jak `excerpt`, `headline_image`, `headline_image_enabled` i `published_at`.
- Dodano badge z licznikami oczekujących elementów do pływającego menu dla `Importy & Eksporty`, `Stan kolejek`, `Importy` i `Eksporty`, zasilane globalnymi danymi Twiga na podstawie wpisów o statusach `pending` w kolejkach oraz `new` dla gotowych eksportów.
- Naprawiono ekran `Lista kolejek` dla osieroconych wpisów eksportu po usuniętych artykułach, filtrując widok do rekordów z istniejącym artykułem, czyszcząc oczekujące wpisy eksportu przy usuwaniu artykułu oraz rozszerzając akcję `Wyczyść kolejkę`, aby usuwała także ukryte elementy `pending`, które nie pojawiały się na liście.
- Przebudowano `admin_dashboard`, zastępując listę artykułów zestawem paneli dla `Artykuły`, `Importy`, `Eksporty`, `Stan kolejek` i `Ustawienia bloga`, z nowym układem kafelkowym i szybkimi akcjami prowadzącymi do odpowiednich sekcji administracyjnych.
- Rozszerzono panele `Artykuły`, `Importy` i `Eksporty` o kafelki liczników pokazujące sumy wszystkich rekordów oraz rozbicie na statusy i typy właściwe dla danej sekcji.
- Rozbudowano blok `Stan kolejek` o nowy widok carousel z zakładkami `Wszystkie`, `Kolejka importu` i `Kolejka eksportu`, prezentujący liczniki kolejek w kompaktowej formie bez dodatkowego wewnętrznego panelu.
- Przebudowano blok `Ustawienia bloga`, zamieniając elementy listy na kafelki informacyjne, usuwając pole `Tryb konfiguracji` i przenosząc `Tytuł bloga` do osobnego kafelka.
- Dodano testy jednostkowe `DashboardController`, obejmujące budowanie danych paneli `admin_dashboard`, sekcji podsumowania kolejek oraz fallbacków dla braku zapisanych ustawień bloga.
- Uzupełniono górną sekcję `admin_dashboard` o podsumowanie zalogowanego użytkownika z adresem e-mail oraz czytelną etykietą roli wyliczaną po stronie `DashboardController`.
- Skrócono i doprecyzowano tekst wprowadzający dashboard w obu wersjach językowych oraz uproszczono etykietę głównej akcji panelu artykułów z `Przeglądaj artykuły` do `Przeglądaj`.
- Dopracowano styl `admin_dashboard`, dodając mocniejsze wyróżnienie wartości liczbowych i kafelków metadanych, zwiększając odstępy w sekcji intro oraz przechodząc na bardziej elastyczny, responsywny układ siatki paneli z szerszymi blokami dla `Stan kolejek` i `Ustawienia bloga`.

## 2026-03-24

- Dodano nową sekcję `Użytkownicy` w panelu administracyjnym z ekranem listy `admin_user_index`, ekranem edycji `admin_user_edit`, osobnym formularzem administracyjnym użytkownika oraz integracją ze skrótami w dashboardzie i pływającym menu.
- Rozszerzono dashboard administracyjny o panel `Użytkownicy`, pokazujący liczbę wszystkich kont, aktywnych, nieaktywnych oraz administratorów, a także dodano testy jednostkowe pokrywające nowe dane panelu.
- Uproszczono panele `user overview` i `user metadata`, wprowadzając kompaktowy wariant kafelków statystyk i metadanych, a na ekranie edycji artykułu dodatkowo rozciągnięto kafelek `Slug` na pełną szerokość górnego wiersza.
- Rozszerzono encję `User` oraz bazę danych o pola `Imię i nazwisko`, `Pseudonim`, `Krótki opis` i `Avatar`, a formularze i widoki użytkowników uzupełniono o możliwość ich edycji oraz prezentacji nazwy wyświetlanej.
- Dodano funkcję `Dodaj użytkownika` z osobnym ekranem `admin_user_new`, obowiązkowym hasłem przy tworzeniu nowego konta oraz wejściami z listy użytkowników, dashboardu i skrótów administracyjnych.
- Wprowadzono akcję usuwania użytkownika z listy wraz z modalem potwierdzenia, blokadą ukrywania i egzekwowania zakazu usunięcia pierwszego administratora oraz czyszczeniem powiązań użytkownika w artykułach przed skasowaniem konta.
- Rozszerzono artykuły o informacje, który użytkownik je utworzył i kto ostatnio je aktualizował, dodając nowe relacje w encji `Article`, migrację bazy danych, automatyczne uzupełnianie tych pól w akcjach panelu admina oraz prezentację tych danych na `admin_article_edit`.
- Przebudowano pływające menu administracyjne, dodając rozwijaną sekcję `Użytkownicy`, do której przeniesiono opcje `Dodaj użytkownika` i `Zarządzanie użytkownikami`, zachowując pierwszeństwo akcji dodawania nowego konta.
- Uzupełniono testy jednostkowe encji `User` i `Article`, formularza użytkownika oraz kontrolera użytkowników o nowe pola profilu, nazwę wyświetlaną, obsługę tworzenia użytkownika i dodatkowe dane przekazywane do widoków.
