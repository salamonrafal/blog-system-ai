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
- Uszczelniono zadanie `app:article-export:process-queue`, wprowadzając atomowe claimowanie pojedynczych rekordów kolejki eksportu przed rozpoczęciem przetwarzania oraz dostosowując testy jednostkowe komendy do nowego przepływu.
- Uszczelniono zadanie `app:article-import:process-queue`, wprowadzając atomowe claimowanie pojedynczych rekordów kolejki importu przed wywołaniem importera oraz aktualizując testy jednostkowe dla bezpiecznego przetwarzania równoległego.
- Rozszerzono `admin_article_index` o kolumnę `Autor`, prezentację nazwy twórcy wpisu oraz akcję `Przypisz do mnie` dla artykułów bez autora, wraz z lokalizacją komunikatów flash zależnie od języka użytkownika.
- Uzupełniono ekran edycji artykułu o dopracowany kafelek `Bieżący status workflow`, stabilizując układ metadanych tak, aby nazwa statusu mieściła się w obrębie panelu bez łamania lub wychodzenia poza obrys.
- Rozszerzono listę artykułów na stronie głównej `blog_index` o informację o autorze obok daty publikacji, z ikoną użytkownika i fallbackiem nazwy w kolejności `pseudonim -> imię i nazwisko -> e-mail`.
- Rozbudowano widok `blog_show` o panel autora pod treścią artykułu i nad dolnymi akcjami, pokazujący zdjęcie profilowe albo inicjały na kolorowym tle, nazwę autora i krótki opis profilu.
- Przebudowano dolne akcje na `blog_show`, zastępując tekstowe przyciski ikonami dopasowanymi do stylu strony, dodając akcję `Kopiuj link` oraz wizualne potwierdzenie skopiowania przez animowaną zamianę ikonki kopiowania na `ptaszka`.
- Uszczelniono obsługę błędów w komendzie `app:article-import:process-queue`, tak aby po awarii `flush()` na zamkniętym `EntityManager` rekord kolejki był ponownie ładowany na świeżym managerze i poprawnie oznaczany jako `FAILED`, wraz z nowym testem regresyjnym.
- Uszczelniono obsługę błędów w komendzie `app:article-export:process-queue`, tak aby po awarii `flush()` na zamkniętym `EntityManager` rekord kolejki eksportu był ponownie ładowany na świeżym managerze i poprawnie oznaczany jako `FAILED`, wraz z nowym testem regresyjnym.
- Zabezpieczono generowanie nazw plików eksportu artykułów, sanitizując `slug` przed użyciem go w ścieżce pliku, dzięki czemu nietypowe wartości nie tworzą zagnieżdżonych katalogów ani nie blokują zapisu eksportu.
- Rozszerzono czyszczenie `pending` importów na ekranie `admin_queue_status`, aby oprócz rekordów kolejki usuwało także odpowiadające im pliki z `var/imports`, zapobiegając pozostawianiu osieroconych payloadów na dysku.
- Poprawiono migrację dodającą pola `created_by_id` i `updated_by_id` do `article`, dopinając klucze obce do `app_user` z `ON DELETE SET NULL`, tak aby integralność relacji autorów była egzekwowana również na poziomie bazy danych.
- Uszczelniono pojedyncze usuwanie importu na ekranie `admin_queue_status`, tak aby oprócz rekordu kolejki kasowany był również odpowiadający mu plik z `var/imports`.

## 2026-03-25

- Rozszerzono kontenerowy skrypt `docker/scripts/checking-tasks.sh`, aby w razie potrzeby tworzył katalog eksportów z właściwymi uprawnieniami jeszcze przed startem usług.
- Uszczelniono import artykułów po błędzie walidacji, tak aby nieprawidłowy payload nie pozostawiał częściowo zaktualizowanej encji `Article` w pamięci Doctrine i nie mógł zostać przypadkowo zapisany przy późniejszym `flush()` oznaczającym element kolejki jako `FAILED`, wraz z nowym testem regresyjnym.
- Uszczelniono komendę `app:article-import:process-queue`, tak aby po resecie zamkniętego `EntityManager` odświeżała również repozytorium kolejki i dalsze operacje `claimNextPending()` oraz `flush()` wykonywała już na świeżych instancjach Doctrine, wraz z testem regresyjnym dla kontynuacji przetwarzania kolejnych elementów.
- Uodporniono dodawanie artykułów do kolejki eksportu na równoległe żądania, zastępując podatny na wyścig mechanizm `check-then-insert` atomowym enqueue na poziomie repozytorium oraz dodając migrację z partial unique index gwarantującym tylko jeden otwarty wpis eksportu (`pending` lub `processing`) na artykuł.
- Uszczelniono komendę `app:article-export:process-queue`, tak aby po wygenerowaniu pliku eksportu i późniejszej awarii zapisu do bazy usuwała osierocony plik JSON przed oznaczeniem elementu kolejki jako `FAILED`, wraz z testami regresyjnymi dla ścieżek z otwartym i zamkniętym `EntityManager`.
- Wydzielono współdzielony serwis `ManagedFilePathResolver`, który centralizuje bezpieczne rozwiązywanie ścieżek importów i eksportów, zastępując zduplikowaną logikę `resolveExportPath()` i `resolveImportPath()` w kontrolerach administracyjnych oraz w `ArticleImportProcessor`.
- Rozszerzono komendy `app:article-import:process-queue` i `app:article-export:process-queue` o logowanie błędów przez `LoggerInterface`, dzięki czemu awarie przetwarzania kolejek są zapisywane również poza wyjściem CLI wraz z kontekstem elementu kolejki.
- Zmieniono kolejność usuwania plików i rekordów importów oraz eksportów w panelu administracyjnym, tak aby najpierw kasowany był plik z dysku, a dopiero potem odpowiadający mu rekord w bazie, co zmniejsza ryzyko pozostawienia stanu nie do odtworzenia po błędzie operacji plikowej.
- Wydzielono współdzielony serwis `ManagedFileDeleter`, który usuwa duplikację prywatnego helpera kasującego pliki importu w `ArticleImportController` i `QueueStatusController`, oraz dodano testy jednostkowe dla nowego serwisu.
- Ujednolicono usuwanie plików eksportu w panelu administracyjnym, przepinając `ArticleExportController` na istniejącą metodę `ArticleExportFileWriter::delete()` i rozszerzając testy jednostkowe writer'a o scenariusz kasowania pliku.
- Poprawiono twardo wpisane polskie komunikaty w kontrolerach administracyjnych oraz w `public/assets/js/app.js`, uzupełniając brakujące znaki diakrytyczne w flashach, słowniku i fallbackach modali potwierdzeń.

## 2026-03-26

- Przebudowano frontendowy kod JavaScript, rozbijając monolityczny `public/assets/js/app.js` na zestaw modułów odpowiedzialnych osobno za `i18n`, preferencje użytkownika, layout, interakcje, panel administracyjny, edytor treści, podgląd obrazów, popup prywatności i współdzielone helpery.
- Rozdzielono warstwę internacjonalizacji na osobny słownik danych `public/assets/js/modules/i18n-data.js` oraz logikę w `public/assets/js/modules/i18n.js`, porządkując sposób aplikowania tłumaczeń i rejestracji listenerów dla komponentów dynamicznych.
- Dodano mechanizm budowania assetów JavaScript oparty o `esbuild`, wraz z `package.json`, skryptem `scripts/build-assets.mjs`, przełączaniem assetu w Twig zależnie od `app_env` oraz nowym globalsem środowiska w `AppGlobalsExtension`.
- Przebudowano obraz Dockera na bezpieczniejszy wariant multi-stage, przenosząc bundlowanie i minifikację assetów do osobnego etapu `node:22-bookworm-slim` oraz usuwając z runtime potrzebę instalowania Node.js przez zewnętrzny skrypt `curl | bash`.
- Uspójniono obsługę języka użytkownika po stronie JavaScript, normalizując wartości `lang` przy odczycie i zapisie w `localStorage` oraz przy zapisie cookie, tak aby wszystkie moduły operowały wyłącznie na wartościach `pl` i `en`.
- Uzupełniono i dopracowano internacjonalizację komponentów frontendowych, dodając brakujące klucze tłumaczeń dla modala usuwania użytkownika, podpisów i etykiet dostępności w podglądzie obrazów oraz placeholderów i promptów w edytorze formatowania artykułów.
- Poprawiono komponenty dynamiczne po refaktorze, usuwając niesymetryczne zerowanie `document.body.style.overflow` w modalach administracyjnych, eliminując podwójne `applyI18n()` inicjowane przez popup prywatności oraz dopinając odświeżanie tłumaczeń w już otwartym modalu podglądu obrazów po zmianie języka.
- Uporządkowano bootstrap aplikacji i build assetów, usuwając redundantne wywołanie `syncTopbarHeight()`, zmieniając import `esbuild` na bezpieczny wariant namespace w ESM, zachowując `.gitkeep` w `public/assets/build` podczas czyszczenia outputu oraz doprecyzowując `README.md`, że w środowisku developerskim Twig ładuje bezpośrednio źródłowe moduły z `public/assets/js`.

## 2026-03-27

- Dodano stronicowanie pod tabelą na `admin_article_index`.
- Wprowadzono osobne ustawienie liczby elementów na stronę dla listy artykułów w panelu administracyjnym, z domyślną wartością `25`.
- Rozszerzono model ustawień bloga, formularz panelu oraz migrację bazy danych o konfigurację `admin_articles_per_page`.
- Rozszerzono ustawienia bloga o konfigurowalny `URL aplikacji`, wykorzystywany przy budowaniu canonicali, `og:url`, linków kopiowania artykułów oraz pełnych adresów obrazków SEO, wraz z migracją bazy danych i aktualizacją testów jednostkowych.
- Dodano nowy moduł zarządzania kategoriami artykułów w panelu administracyjnym z ekranami listy, tworzenia, edycji i usuwania, nową encją `ArticleCategory`, repozytorium, formularzem, kontrolerem, migracją bazy danych oraz testami jednostkowymi.
- Rozszerzono dashboard i skróty administracyjne o sekcję kategorii artykułów, dzięki czemu nowy moduł jest dostępny bezpośrednio z panelu głównego i pływającego menu admina.
- Wprowadzono obsługę statusu kategorii (`Aktywna` / `Nieaktywna`) wraz z prezentacją w formularzu, tabeli listy i modalem potwierdzenia usuwania w stylu interfejsu aplikacji.
- Rozszerzono kategorie o wielojęzyczne tytuły i opisy, a następnie przebudowano model danych na generyczne mapy tłumaczeń `titles` i `descriptions`, przygotowując strukturę pod kolejne języki bez dodawania nowych pól do encji.
- Przebudowano formularze dodawania i edycji kategorii, grupując pola w zakładkach `Podstawowe` oraz osobnych panelach tłumaczeń per język, z dopracowanym układem, spacingiem i bardziej kompaktowymi tabami.
- Dodano w panelach tłumaczeń akcji `Uzupełnij z podstawowych`, która kopiuje nazwę i krótki opis z sekcji podstawowej do aktualnie edytowanego wariantu językowego.
- Uzupełniono moduł kategorii o pełną internacjonalizację `PL/EN` dla nagłówków ekranów, zakładek, opisów sekcji, etykiet pól, placeholderów, wartości statusu, przycisków i komunikatów pomocniczych, tak aby przełączanie języka działało również na ekranach tworzenia i edycji kategorii.
- Rozszerzono formularze tworzenia i edycji artykułu o możliwość przypisania kategorii, dodając relację `Article -> ArticleCategory`, migrację bazy danych oraz aktualizację testów jednostkowych encji i formularza.
- Rozszerzono publiczny widok `blog_index` o sekcję kategorii artykułów wraz z nową trasą filtrowania `/category/{slug}`, obsługą slugów budowanych z nazw kategorii, filtrowaniem listy opublikowanych wpisów oraz zachowaniem wybranej kategorii w paginacji.
- Uzupełniono publiczny widok bloga o dynamiczne podmienianie głównego tytułu i opisu listy artykułów na treści aktualnie wybranej kategorii, dzięki czemu wejście w filtr kategorii prezentuje jej własny kontekst redakcyjny zamiast ogólnego intro bloga.
- Dopracowano interfejs filtrów kategorii na `blog_index`, upraszczając listę do kompaktowych przycisków bez opisów, dodając tłumaczenie etykiety `Kategorie artykułów`, zmniejszając promień zaokrągleń oraz porządkując wyrównanie i wizualne odróżnienie samej etykiety sekcji.
- Dodano nowe testy jednostkowe dla `BlogController`, `SecurityController`, `BlogSettingsController` i `QueueStatusController`, uzupełniając brakujące pokrycie dla publicznego filtrowania kategorii, logowania, zapisu ustawień bloga oraz obsługi widoku i czyszczenia kolejek administracyjnych.
- Dopracowano internacjonalizację sekcji kategorii na `blog_index`, zastępując twardo wpisane polskie etykiety kluczami i18n oraz rozdzielając język interfejsu użytkownika od parametru `lang` używanego do filtrowania listy artykułów.
- Uporządkowano fallbacki tytułu i opisu na stronie `blog_category`, tak aby korzystały kolejno z tłumaczenia dla aktualnego języka użytkownika, pól podstawowych kategorii, a dopiero na końcu z neutralnych wartości i18n.
- Rozszerzono widok `blog_show` o informację o kategorii artykułu z linkiem do filtrowanej listy wpisów tej kategorii oraz dopracowano układ znacznika względem headline, także dla wariantu bez grafiki nagłówkowej.
- Ujednolicono formatowanie nowego bloku stylów kategorii w `public/assets/css/styles.css`, dopasowując spacing i zapis deklaracji do konwencji używanej w reszcie arkusza.
- Uproszczono sortowanie aktywnych kategorii w repozytorium, usuwając redundantne drugie kryterium `createdAt`, oraz doprecyzowano test `QueueStatusController`, aby potwierdzał usunięcie obu konkretnych encji kolejki zamiast dowolnych dwóch wywołań `remove()`.
- Rozszerzono widok `blog_show` o sekcję polecanych artykułów pod panelem autora, prezentującą do `5` innych opublikowanych wpisów z tej samej kategorii, a dla artykułów bez kategorii dobierającą rekomendacje z całego bloga.
- Przeniesiono dolne akcje `Wróć`, `Kopiuj link` i `Edytuj` na `blog_show` bezpośrednio pod treść artykułu, tak aby pojawiały się przed sekcją `Autor artykułu` i blokiem rekomendacji.

## 2026-03-28

- Uporządkowano `VERSION.md`, przenosząc sekcję z datą `2026-03-27` na dół pliku tak, aby zachować zasadę dopisywania nowszych zmian na końcu strony.
- Poprawiono link kategorii na `blog_show`, tak aby przejście do filtrowanej listy wpisów zachowywało aktualny język interfejsu użytkownika zamiast zawsze kierować do wariantu `pl`, oraz dodano test regresyjny dla `BlogController`.
- Przebudowano pływające menu administracyjne, dodając rozwijaną sekcję `Zarządzanie treścią`, do której przeniesiono pozycje `Lista artykułów` i `Kategorie artykułów`, wraz z nowym kluczem tłumaczenia `admin_shortcut_content_management_title`.
- Dopracowano dashboard administracyjny, kompaktując przyciski akcji w panelach do pojedynczej linii, skracając etykiety akcji w blokach zarządzania do spójnych form `Przeglądaj`, `Dodaj`, `Kolejki` i `Edytuj` oraz zmieniając etykietę statystyki użytkowników z `Administratorzy` na krótsze `Admini`.
- Uzupełniono style dashboardu o bezpieczne zawijanie dłuższych etykiet statystyk wewnątrz kafelków, dzięki czemu podpisy takie jak `Admini` nie wychodzą już poza obrys paneli.
- Rozszerzono `admin_article_index` o filtrowanie listy po kategorii, dodając obsługę filtra w `ArticleController`, nowe metody repozytorium do liczenia i paginacji z uwzględnieniem kategorii, zachowanie parametru filtra w paginacji oraz testy jednostkowe dla wyboru konkretnej kategorii i pustego wyboru `Wszystkie kategorie`.
- Dodano nad tabelą `admin_article_index` nowy, kompaktowy blok `Filtry` wyrównany do prawej strony, z uproszczonym układem bez etykiety pola `Kategoria`, przyciskiem czyszczenia aktywnego filtra oraz stylami dopasowanymi do estetyki panelu administracyjnego.
- Zastąpiono natywną listę rozwijaną kategorii na `admin_article_index` customowym dropdownem z własnym triggerem, panelem opcji, stanem zaznaczenia i obsługą JavaScript, dzięki czemu cała lista rozwijana i jej elementy wizualnie pasują do panelu administracyjnego.
- Rozszerzono obraz Dockera o usługę `cron` oraz zintegrowano jej uruchamianie w `docker/scripts/entrypoint.sh`, dzięki czemu przy starcie kontenera automatycznie rejestrowany jest crontab użytkownika `www-data`.
- Dodano plik `docker/conf/cron/article-queue` z zadaniami uruchamianymi co minutę dla komend `app:article-export:process-queue` i `app:article-import:process-queue`, wraz z logowaniem wyjścia do plików w katalogu `/tmp`.
- Wydzielono obsługę środowiska i uruchamiania komend Symfony dla crona do skryptów `docker/scripts/prepare-cron-environment.sh` oraz `docker/scripts/run-console.sh`, upraszczając `entrypoint`, zapewniając dostęp do zmiennych `APP_ENV`, `APP_DEBUG`, `APP_SECRET` i `DATABASE_URL` oraz użycie pełnej ścieżki do interpretera PHP w kontenerze.
- Rozszerzono proces eksportu o identyfikację użytkownika inicjującego akcję, dodając relacje `requestedBy` do `article_export_queue` i `article_export`, nowe migracje bazy danych, przekazywanie użytkownika przy enqueue, prezentację tych danych na listach eksportów i kolejek oraz zapis metadanych `exported_by` w generowanym pliku JSON.
- Rozszerzono proces importu o identyfikację użytkownika inicjującego akcję, dodając relację `requestedBy` do `article_import_queue`, migrację bazy danych, zapisywanie użytkownika przy dodawaniu pliku do kolejki, prezentację tej informacji na listach importów i kolejek oraz dopięcie `requested_by_user_id` do kontekstu logów błędów importu.
- Przebudowano układ tabel `admin_article_export_index` i `admin_article_import_index`, łącząc kolumny użytkownika i daty utworzenia w pojedyncze pola z układem `użytkownik -> data`, usuwając zbędne kolumny pomocnicze i porządkując prezentację rekordów.
- Uzupełniono internacjonalizację nagłówków tabel administracyjnych oraz brakujących etykiet w panelu, dopinając nowe klucze `i18n` dla widoków eksportów, importów, kolejek, użytkowników i kategorii oraz zachowując poprawne przełączanie treści `PL/EN` w tabeli kategorii.
- Uproszczono listę importów, usuwając osobną kolumnę `Komunikat`, zastępując ją ikoną ostrzeżenia wyświetlaną tylko dla rekordów z błędem oraz dodając modal z pełną treścią komunikatu po kliknięciu.
- Dodano trwałe powiadomienia per-user o zakończeniu importu i eksportu przetwarzanego w tle, widoczne wyłącznie dla zalogowanego użytkownika inicjującego akcję, z internacjonalizowanym komunikatem o sukcesie lub błędzie, automatycznym ukrywaniem po `10` sekundach oraz możliwością ręcznego zamknięcia.
- Rozszerzono nowe powiadomienia o aktywne dostarczanie bez odświeżania strony przez polling do dedykowanego endpointu, przycisk przejścia do listy `Importy` lub `Eksporty` zależnie od typu zdarzenia oraz scalanie wielu zakończonych importów albo eksportów do pojedynczego komunikatu na typ i rezultat.

## 2026-03-29

- Dopracowano mobilny układ `blog_index`, wymuszając pojedynczą kolumnę kart artykułów, zabezpieczając ich zawartość przed wychodzeniem poza blok oraz zmniejszając główny tytuł strony na małych ekranach.
- Przebudowano mobilną sekcję kategorii na `blog_index`, przenosząc etykietę `Kategorie artykułów` nad listę, wprowadzając poziome przewijanie kategorii z automatycznym przewinięciem aktywnego elementu do widoku oraz stabilizując paginację dla ekranów poniżej `425px`.
- Dopracowano mobilny hero na `blog_show`, zmniejszając tytuł nakładany na obrazek oraz kompaktując badge kategorii tak, aby lepiej mieścił się i wizualnie pasował do nagłówka.
- Poprawiono internacjonalizację kategorii na `blog_show`, usuwając podwójny napis `Kategoria Category`, podpinając etykietę `KATEGORIA` do przełącznika języka oraz dodając brakującą ochronę skryptu przewijania kategorii przed błędem `querySelector` na `null`.
- Ustabilizowano dolne akcje na `blog_show`, przywracając ich wyrównanie do prawej strony na urządzeniach mobilnych.
- Ujednolicono komunikaty flash w panelu administracyjnym względem wybranego języka użytkownika na ekranach `admin_article_index`, `admin_article_category_index`, `admin_user_index`, `admin_article_import_index`, `admin_article_export_index`, `admin_queue_status` oraz `admin_blog_settings`.
- Rozszerzono internacjonalizację widoków administracyjnych, uzupełniając brakujące tłumaczenia na stronach `admin_dashboard`, `admin_article_import_index`, `admin_article_export_index`, `admin_queue_status`, `admin_blog_settings` oraz formularzach dodawania i edycji użytkownika.
- Dopracowano internacjonalizację pływającego menu administracyjnego, podpinając brakujące elementy sekcji `Użytkownicy`, etykiety dostępności i angielskie nazwy pozycji `Imports & Exports` oraz `Imports`.
- Naprawiono konflikt warstw na urządzeniach mobilnych, podnosząc `z-index` mobilnego draweru ponad pływające menu administracyjne, dzięki czemu przyciski zmiany języka, motywu i koloru są ponownie klikalne.

## 2026-03-30

- Dodano nowy moduł zarządzania górnym menu bloga w panelu administracyjnym pod trasą `/admin/top-menu`, obejmujący listę elementów, formularze tworzenia i edycji oraz usuwanie wpisów z potwierdzeniem.
- Wprowadzono encję `TopMenuItem`, repozytorium, formularz, migrację bazy danych oraz mechanizm budowania drzewa menu z relacjami `parent/children`, statusem aktywności i pozycją pozwalającą ustalać kolejność elementów.
- Rozszerzono konfigurację elementów menu o internacjonalizowane nazwy `PL/EN` oraz typy celu wybierane w zakładkach: zewnętrzny adres URL, strona główna bloga, konkretna kategoria artykułów albo konkretny artykuł.
- Podłączono top menu do globalnych danych Twiga i layoutu aplikacji, zastępując twardo wpisaną nawigację dynamicznym renderowaniem w desktopowym topbarze i mobilnym drawerze wraz z obsługą zagnieżdżonych submenu.
- Rozszerzono dashboard oraz pływające skróty administracyjne o szybki dostęp do modułu `Top menu`.
- Uzupełniono tłumaczenia i18n, komunikaty walidacyjne, style oraz JavaScript potrzebne do obsługi zakładek typu linku, submenu i akcji usuwania elementów menu.
- Dodano zestaw testów jednostkowych dla nowych klas i integracji z dashboardem oraz globalami Twiga.
- Uproszczono listę elementów na `admin_top_menu_index`, usuwając z tabeli dodatkowe informacje o tłumaczeniach `PL/EN` oraz szczegóły adresu docelowego, dzięki czemu widok skupia się na nazwie elementu i typie celu.
- Rozszerzono konfigurację celu top menu o opcję `Brak`, która pozwala tworzyć elementy pełniące wyłącznie rolę rodzica submenu bez przekierowania, wraz z pełną obsługą w formularzach, i18n, builderze menu i testach regresyjnych.
- Dodano dla celu `Inna strona WWW` przełącznik `Otwórz w nowym oknie`, zapis nowego ustawienia w bazie danych, logikę renderowania `target="_blank"` tylko dla zaznaczonych elementów oraz zgodność widoków ze starszymi rekordami menu.
- Dopracowano formularze tworzenia i edycji top menu, poprawiając wyśrodkowanie tabów wyboru celu, kształt paneli `Tłumaczenia` i `Przekierowanie` oraz automatyczne przełączanie na zakładkę zawierającą ukryte pole z błędem walidacji.
- Przebudowano zachowanie i styl desktopowego submenu top menu, dopasowując pozycjonowanie względem rodzica, pełną szerokość elementów podrzędnych, ikonę rozwijania, tło oraz cień do aktualnego motywu interfejsu.
- Zmieniono mobilne submenu top menu na akordeon, w którym elementy z dziećmi rozwijają własną listę, a otwarcie jednego submenu automatycznie zwija wcześniej aktywny panel.
- Rozszerzono moduł eksportów o kategorie artykułów, dodając akcje eksportu pojedynczego i zbiorczego na `admin_article_category_index`, nowy typ eksportu `categories`, osobną kolejkę `category_export_queue`, dedykowany command `app:category-export:process-queue`, writer plików JSON oraz skrót `composer category-export:process-queue`.
- Zachowano wspólną listę gotowych eksportów na `admin_article_export_index`, jednocześnie rozdzielając procesy przetwarzania eksportu artykułów i kategorii oraz aktualizując `admin_queue_status`, dashboard i badge w skrótach administracyjnych tak, aby agregowały oba typy kolejek eksportu.
- Rozbudowano ekran `admin_article_export_index` o filtrowanie po typie eksportu oraz stronicowanie, a następnie dopracowano wygląd filtra typu tak, aby korzystał z tego samego customowego dropdownu i stylu co filtr kategorii na `admin_article_index`.

## 2026-03-31

- Rozszerzono moduł eksportów o pełny eksport hierarchii `Top menu`, dodając akcję `Eksportuj hierarchię` na `admin_top_menu_index`, nowy typ eksportu `top_menu`, osobną kolejkę `top_menu_export_queue`, dedykowany command `app:top-menu-export:process-queue`, writer pliku JSON oraz migrację bazy danych.
- Zachowano wspólną listę gotowych eksportów na `admin_article_export_index`, jednocześnie uzupełniając filtr typu eksportu o `Top menu` oraz rozszerzając widok `admin_queue_status`, dashboard, badge w skrótach administracyjnych i zadania crona tak, aby uwzględniały także kolejkę eksportu menu.
- Uzupełniono internacjonalizację `PL/EN`, etykiety i komunikaty flash związane z eksportem `Top menu` oraz dodano testy jednostkowe dla nowej kolejki, writera eksportu, integracji kontrolerów i agregacji globali Twiga.
- Dodano do kategorii artykułów trwałe, unikalne pole `slug` wyliczane z polskiego tytułu kategorii, wraz z migracją danych, serwisem generującym unikalne wartości, aktualizacją zapisu w panelu administracyjnym oraz przepięciem bloga, top menu i eksportu kategorii na zapisany slug zamiast wyliczania go „w locie”.
- Rozszerzono eksport kategorii o pole `slug`, a eksport artykułów o `category_slug`, dzięki czemu import może rozpoznawać przypisaną kategorię po stabilnym identyfikatorze niezależnym od lokalnych identyfikatorów bazodanowych.
- Dodano do elementów `Top menu` trwałe, unikalne pole `unique_name` wyliczane z polskiej nazwy, wraz z migracją danych, serwisem generującym kolizyjnie bezpieczne wartości i przygotowaniem repozytorium pod dopasowywanie rekordów po tym identyfikatorze podczas importu.
- Rozszerzono eksport `Top menu` o stabilne identyfikatory środowiskowo niezależne: `unique_name`, `parent_unique_name`, `category_slug` oraz `article_slug`, aby przyszły import mógł rozpoznawać zarówno sam element, jego rodzica, jak i cel odnośnika bez polegania na lokalnych `id`.
- Uzupełniono kontenerowy plik crontaba `docker/conf/cron/article-queue` o brakujący wpis `app:category-export:process-queue`, dzięki czemu kolejka eksportu kategorii jest przetwarzana automatycznie tak samo jak eksport artykułów, eksport top menu i import artykułów.
- Zaktualizowano `README.md`, rozszerzając dokumentację o komplet kolejek eksportu, stabilne identyfikatory zapisywane w payloadach (`category_slug`, `unique_name`, `parent_unique_name`, `article_slug`) oraz pełną listę wpisów crontaba dla zadań backgroundowych.
- Uogólniono poprawkę customowych dropdownów filtrów w panelu administracyjnym, tak aby każda karta zawierająca `article-index-toolbar` pozwalała liście opcji wyjść poza obrys kontenera, eliminując problem ucinania selekta na ekranie eksportów i w kolejnych widokach wykorzystujących ten sam komponent.
- Dodano pełny moduł `Import Menu` dla hierarchii `Top menu`, obejmujący nową stronę `admin_top_menu_import_index`, osobną kolejkę `top_menu_import_queue`, command `app:top-menu-import:process-queue`, migrację bazy danych oraz integrację z pływającym menu administracyjnym.
- Wprowadzono importer hierarchii menu oparty o stabilne identyfikatory `unique_name` i `parent_unique_name`, z dopasowaniem istniejących rekordów po `unique_name`, rozpoznawaniem celów po `category_slug` i `article_slug` oraz przetwarzaniem od najwyższego poziomu drzewa, aby rodzice byli dostępni przed dziećmi.
- Rozszerzono `admin_queue_status`, dashboard, badge w skrótach administracyjnych, powiadomienia per-user oraz wpisy crona i skrypty Composer tak, aby uwzględniały również import `Top menu`, wraz z brakującą internacjonalizacją `PL/EN` i komunikatami dla nowego przepływu.
- Wydzielono dedykowany wyjątek `TopMenuImportException` dla importu menu, porządkując semantykę warstwy domenowej i oddzielając błędy importu `Top menu` od istniejącego importu artykułów.
- Uszczelniono importer `Top menu` pod kątem wydajności, pobierając brakujących rodziców wskazywanych przez `parent_unique_name` jednym zapytaniem przed sortowaniem hierarchii zamiast wykonywać serię pojedynczych lookupów podczas rekursji.
- Ujednolicono polskie copy i nazewnictwo wokół nowego modułu importu, zastępując w interfejsie określenia `top menu` bardziej naturalnym `menu`, a w pływającym menu zmieniając etykiety pozycji na `Import Menu` oraz `Import Artykułów`.
- Dodano wspólny serwis `TopMenuCacheManager`, który po ręcznej edycji i po udanym imporcie `Top menu` czyści oraz natychmiast dogrzewa cache menu dla `pl` i `en`, dzięki czemu nowa hierarchia jest widoczna od razu bez oczekiwania na wygaśnięcie cache.
- Rozszerzono zestaw testów jednostkowych o nowy importer `Top menu`, command przetwarzający jego kolejkę, odświeżanie cache menu oraz dodatkowe scenariusze regresyjne dla hierarchii, rodziców spoza payloadu i agregacji danych w dashboardzie oraz globalach Twiga.
- Przebudowano customowe dropdowny filtrów w listingach administracyjnych tak, aby `admin_article_index` i `admin_article_export_index` korzystały z jednego wspólnego komponentu opartego o ujednolicone hooki `data-listing-filter-*`, z panelem opcji wynoszonym nad kartę tylko na czas otwarcia, dzięki czemu zachowane są poprawne zaokrąglenia bloku i pełna widoczność listy.
- Naprawiono tworzenie kategorii artykułów bez ręcznie podanego sluga, usuwając błędne wymaganie `slug` na poziomie walidacji encji `ArticleCategory` oraz dodając bezpiecznik w kontrolerze, który zgłasza błąd formularza tylko wtedy, gdy automatyczne wygenerowanie sluga rzeczywiście się nie powiedzie.

## 2026-04-01

- Dodano pełny moduł `Import kategorii`, obejmujący nową stronę `admin_category_import_index`, osobną kolejkę `category_import_queue`, dedykowany command `app:category-import:process-queue`, migrację bazy danych oraz integrację z pływającym menu administracyjnym.
- Wprowadzono importer kategorii działający w tle, który rozpoznaje rekordy po `slug`, aktualizuje istniejące kategorie zamiast tworzyć duplikaty oraz zapisuje błędy przetwarzania przy statusie `FAILED`.
- Rozszerzono `admin_queue_status`, dashboard, badge w skrótach administracyjnych, powiadomienia per-user, skrypty Composer oraz kontenerowy plik crontaba `docker/conf/cron/article-queue`, tak aby uwzględniały również kolejkę importu kategorii.
- Zaktualizowano format eksportu kategorii, aby używał pluralnego klucza `categories`, oraz dopracowano importer i komunikaty walidacyjne pod kątem czytelności, spójnych anglojęzycznych błędów backendowych i obsługiwanych wartości statusu.
- Uspójniono fallbacki tłumaczeń w Twig, dodając helpery `ui_translate()` i `ui_language_label()` oraz porządkując użycie ich w widokach bloga, formularzy i ekranu importu kategorii zamiast rozproszonych warunków inline.
- Uzupełniono `README.md` o opis importu kategorii, ręczne uruchamianie konsumenta i wpis crona, a zestaw testów jednostkowych rozszerzono o importer kategorii, command kolejki, kontroler, encję, writer eksportu oraz agregację danych w dashboardzie i globalach Twiga.
- Przebudowano organizację stylów frontendowych, rozbijając monolityczny `public/assets/css/styles.css` na mniejsze pliki tematyczne dla warstwy bazowej, komponentów współdzielonych i widoków stron oraz pozostawiając `styles.css` jako manifest `@import` używany przez build produkcyjny.
- Rozszerzono podział CSS o bardziej granularne komponenty, wydzielając osobne arkusze dla `navigation`, `buttons`, `forms`, `flash`, `admin-shortcuts` i `editor`, a istniejące pliki `topbar`, `common` i `admin` odchudzono do ich właściwej odpowiedzialności.
- Zmieniono ładowanie stylów w Twig tak, aby środowisko developerskie korzystało z manifestu `public/assets/css/styles.css` importującego źródłowe pliki CSS, natomiast środowisko produkcyjne ładowało jeden zminifikowany plik `public/assets/build/styles.min.css`.
- Rozszerzono skrypt `scripts/build-assets.mjs` i proces builda assetów o bundlowanie oraz minifikację CSS obok JavaScriptu, generując w katalogu `public/assets/build` zarówno developmentowe bund­le pomocnicze, jak i produkcyjny plik `styles.min.css`.
- Dodano nowe skróty budowania assetów produkcyjnych w `package.json` i `composer.json`, a `README.md` uzupełniono o opis nowej struktury CSS, różnic między trybem `dev` i `prod` oraz dostępnych komend builda.
- Zaktualizowano `Dockerfile`, aby etap `build_assets` kopiował źródła CSS, budował także produkcyjny bundle stylów i przenosił `styles.min.css` do finalnego obrazu aplikacji.
- Naprawiono regresję stylowania tabel w panelu administracyjnym po rozbiciu CSS na pliki modułowe, przywracając wspólne bazowe reguły `table`, `th` i `td`, dzięki czemu poprawny wygląd odzyskały ekrany `Zarządzanie artykułami`, `Zarządzanie kategoriami artykułów`, `Zarządzanie górnym menu bloga`, `Stan kolejek`, `Importy`, `Import kategorii`, `Import menu` i `Eksporty`.
- Dopracowano pływające menu administracyjne, usuwając ucinanie ostatnich pozycji w rozwijanym panelu `Importy & Eksporty`, dodając subtelne przewijanie przy ograniczonej wysokości oraz zmieniając układ tak, aby scroll dotyczył wyłącznie środkowej części listy, a górny przycisk szybkiej akcji i dolny blok `Szybkie ustawienia` pozostawały przypięte.
- Poszerzono pływające menu administracyjne, aby etykieta `Importy & Eksporty` wraz z badge mieściła się w jednym wierszu bez niepożądanego zawijania.

## 2026-04-02

- Dodano pełny moduł zarządzania słowami kluczowymi artykułów w panelu administracyjnym, obejmujący listę, formularze tworzenia i edycji, relację z artykułami, skrót w pływającym menu oraz komplet tłumaczeń i18n, walidacji i testów jednostkowych.
- Rozszerzono model słów kluczowych o stabilną unikalną nazwę, status aktywności, opcjonalny kolor oraz zakres językowy `PL`, `EN` i `Dla wszystkich`, dzięki czemu można współdzielić te same słowa kluczowe między wersjami językowymi bez duplikowania danych.
- Wydzielono wspólny serwis generowania unikalnych nazw opartych o slug, porządkując logikę identyfikatorów wykorzystywaną zarówno przez słowa kluczowe, jak i elementy `Top menu`.
- Przebudowano formularz tworzenia i edycji słowa kluczowego, ustawiając pola `Język`, `Kolor` i `Status` w jednym rzędzie, dodając pusty stan `brak koloru` z możliwością wyczyszczenia wyboru oraz przenosząc podpowiedzi pól do tooltipów otwieranych z ikony `?`.
- Dodano reużywalny komponent customowego selecta z osobnym modułem JavaScript i komponentem CSS, a następnie zastosowano go na ekranach dodawania i edycji słów kluczowych zamiast natywnego wyglądu systemowego.
- Zmieniono wybór słów kluczowych na formularzu artykułu z klasycznego `select` na reużywalny komponent listy z wyszukiwaniem, podpowiedziami i usuwalnymi elementami, który filtruje wyniki do słów zgodnych z językiem artykułu lub zakresem `Dla wszystkich`.
- Dopracowano zachowanie listy podpowiedzi słów kluczowych, wynosząc ją nad układ formularza jak overlay, pokazując dopiero po uzyskaniu fokusu i wpisaniu minimum jednego znaku oraz spinając wszystkie etykiety, komunikaty i akcje komponentu z i18n zamiast wartości hardcoded.

## 2026-04-03

- Ujednolicono komunikaty potwierdzające zapis artykułu w panelu administracyjnym, tak aby na ekranach tworzenia i edycji korzystały z `UserLanguageResolver` i wyświetlały się zgodnie z wybranym językiem interfejsu.
- Rozszerzono publiczny widok `blog_show` o sekcję słów kluczowych pod treścią artykułu, prezentowaną jako klikane badge z opcjonalnym akcentem kolorystycznym pobieranym z konfiguracji słowa kluczowego.
- Dodano nową stronę filtrowania artykułów po słowie kluczowym pod trasą `/keyword/{language}/{name}`, wykorzystującą ten sam widok listy co `blog_index` oraz zachowując spójny układ kart i paginacji.
- Zmieniono publiczne listingi artykułów tak, aby nie filtrowały wpisów po języku `PL/EN`, traktując parametr `lang` wyłącznie jako warstwę interfejsu i SEO, a nie kryterium ograniczające wyniki.
- Dopracowano wizualnie sekcję słów kluczowych na `blog_show`, usuwając dodatkowy nagłówek, zmniejszając badge, zdejmując obramowanie sekcji oraz zwiększając odstęp między końcem treści a listą słów kluczowych.
- Zmieniono etykietę pola wyboru kategorii w formularzach dodawania i edycji artykułu z `Nazwa` na `Kategoria`, wprowadzając dla tego pola osobny klucz `i18n` bez wpływu na formularz zarządzania samymi kategoriami.
- Uogólniono w modelu `BlogSettings` nazwę limitu elementów na stronę w panelu administracyjnym do zastosowań szerszych niż sama lista artykułów, aktualizując formularz ustawień bloga, tłumaczenia i walidację, a listę eksportów spięto z domyślną wartością `25` dla listingów administracyjnych.
- Uzupełniono proces eksportu kategorii, widoki eksportów i kolejek oraz formularz ustawień bloga o brakujące tłumaczenia `PL/EN`, a także rozszerzono zestaw testów jednostkowych o nowe encje, commandy, writer'y i ścieżki kontrolerów związane z eksportem kategorii i paginacją eksportów.
- Dopracowano mobilny układ strony głównej bloga i dashboardu administracyjnego, wymuszając pojedynczą kolumnę paneli i kart na mniejszych ekranach.
- Dodano na stronie głównej bloga sekcję `TOP 5 słów kluczowych`, opartą o najczęściej używane słowa z opublikowanych artykułów, wraz z linkami do filtrów, licznikami oraz testami kontrolera.
- Uzupełniono internacjonalizację sekcji `TOP 5 słów kluczowych` oraz dopracowano jej wygląd, spacing, separator z nagłówkiem osadzonym na linii i czytelność badge z licznikami.
- Przebudowano mobilne zachowanie pływającego menu administracyjnego, zamieniając je na pełnoekranowy drawer z przyciskiem zamknięcia, obsługą `Escape`, kliknięcia poza panelem i zamykania po przejściu w link.
- Rozszerzono desktopowe skróty administracyjne o możliwość dokowania panelu do lewej krawędzi ekranu, zapamiętywanie stanu dokowania i pełną wysokość zadokowanego widoku.
- Dodano w zadokowanym panelu możliwość zwijania do wąskiego raila po lewej stronie oraz zapamiętywanie stanu zwinięcia między odświeżeniami.
- Ustabilizowano skróty administracyjne przy przełączaniu między viewportem desktopowym i mobilnym, usuwając błędy pozycji zębatki, domykania panelu i synchronizacji stanu dokowania.
- Dopracowano zwinięty wariant zadokowanego raila, pokazując skróty jako same ikonki z tooltipami i badge oraz zachowując działanie akcji ustawień w dolnej sekcji.
- Rozszerzono globalny mechanizm tooltipów o obsługę elementów dodawanych dynamicznie oraz automatyczne przenoszenie tooltipa nad element, gdy przy dolnej krawędzi ekranu nie mieści się pod nim.
- Poprawiono kontrast ikon w zwiniętym, zadokowanym panelu skrótów administracyjnych, dzięki czemu pozostają czytelne również przy jasnych kolorach akcentu.
- Dodano preloader dla tras administracyjnych w formie `blur overlay`, wygaszany po pełnym załadowaniu strony, z fallbackiem `noscript`, internacjonalizacją statusu oraz finalną animacją EKG z pulsującym sercem.
- Przebudowano ekran `Ustawienia bloga`, usuwając blok `admin://seo-preview`, łącząc ustawienia `Paginacja strony głównej` i `Paginacja list w panelu` w jedną sekcję oraz upraszczając nagłówki sekcji `Tożsamość i SEO` i `Globalne ustawienia paginacji`.
- Ujednolicono układ wszystkich sekcji formularza ustawień bloga: poszerzono wspólną kolumnę etykiet, przeniesiono podpowiedzi do tooltipów pod ikoną `?`, dodano subtelne rozróżnienie wierszy paginacji oraz dopracowano pozycje liczników i etykiet dla pól tekstowych i `textarea`.

## 2026-04-04

- Dodano obsługę `prefers-reduced-motion` dla preloadera tras administracyjnych, wyłączając animacje oraz przejścia w środowiskach z ograniczonym ruchem.
- Przeniesiono animacje preloadera do stanu `pending`, dzięki czemu puls serca i przebieg EKG uruchamiają się tylko wtedy, gdy overlay faktycznie przechodzi w aktywny stan ładowania.
- Uzupełniono formularz `Ustawienia bloga` o placeholdery sterowane przez `data-i18n-placeholder`, poprawiając lokalizację pól tekstowych i paginacyjnych oraz porządkując wspólną zmienną szerokości kolumny etykiet w warstwie stylów.
- Dodano automatyzację wersjonowania wydań przez `composer`, obejmującą wyliczanie kolejnej wersji semantycznej na podstawie tagów Git oraz tworzenie nowego anotowanego taga dla wariantów `patch`, `minor` i `major`.
- Rozszerzono workflow release o warianty publikujące, które po utworzeniu nowego taga automatycznie wypychają bieżący branch oraz tag do zdalnego repozytorium `origin`, z zabezpieczeniem przed uruchomieniem na brudnym worktree lub w stanie `detached HEAD`.
- Uzupełniono `README.md` o dokumentację procesu release, dostępnych komend `composer release:*` i `composer release:publish:*` oraz zalecanego przebiegu publikacji zmian z tagiem.
- Zarezerwowano docelową wysokość sekcji headline na publicznym widoku artykułu jeszcze przed załadowaniem grafiki nagłówkowej, eliminując skoki layoutu bez zmiany szerokości bloku.
- Przebudowano toolbar edytora treści w formularzach dodawania i edycji artykułu, grupując poziomy `H1-H7` w kompaktowej liście rozwijanej `Nagłówki`, porządkując kolejność grup narzędzi i rozdzielając je pionowymi separatorami.
- Dopracowano wizualnie toolbar edytora, zmniejszając i odchudzając dropdown `Nagłówki`, wymieniając ikonę `kodu inline` na odróżnialną od `bloku kodu` oraz dodając własne tooltipy z tłumaczeniami `PL/EN` zamiast systemowych podpowiedzi przeglądarki.
- Rozszerzono edytor treści o skróty klawiaturowe `Ctrl+B`, `Ctrl+I` i `Ctrl+U`, które przy zaznaczonym tekście otaczają go znacznikami pogrubienia, kursywy i podkreślenia, a opisy skrótów dopisano również do tooltipów odpowiednich przycisków.
- Odblokowano ręczne wydłużanie pola `Treść` w edytorze, umożliwiając wygodne zwiększanie wysokości `textarea` bez naruszania stylu otaczającego komponentu.
- Dodano do formularzy tworzenia i edycji artykułu nowy edytor tabeli otwierany z toolbaru, który pozwala budować tabelę w formacie Markdown, zarządzać wierszami i kolumnami oraz włączać lub wyłączać wiersz nagłówków przed wstawieniem treści do edytora.
- Dopracowano interfejs edytora tabeli tak, aby wizualnie odpowiadał tabelom renderowanym w artykułach, stabilnie obsługiwał przewijanie przy większej liczbie kolumn i wierszy, resetował stan po wstawieniu tabeli oraz używał wspólnej blokady przewijania strony podczas otwartego modala.
- Wydzielono generator tabeli do osobnych komponentów `editor-table-builder.js` i `editor-table-builder.css`, a współdzielone dropdowny toolbaru do reużywalnego komponentu `dropdown-menu`, upraszczając główny moduł `editor.js`.
- Przebudowano toolbar edytora artykułu, przenosząc `Kod inline` i `Blok preformatowany` do nowego menu `Dodatkowe bloki tekstu` otwieranego ikoną z grotem, poprawiając kolejność narzędzi oraz czytelność ikon list punktowanych i numerowanych.
- Uspójniono zachowanie tooltipów i internacjonalizacji w edytorze artykułu, zastępując natywne tooltipy w generatorze tabeli projektowym komponentem podpowiedzi, ukrywając tooltip przy otwieraniu menu dodatkowych bloków oraz dopinając brakujące klucze `i18n` dla nowych elementów i akcji zamykania modali.

## 2026-04-05

- Dodano pełny moduł `Media` w panelu administracyjnym, obejmujący osobne strony `Dodaj obrazek` i `Galeria mediów`, zapis rekordów obrazków w encji `MediaImage`, przechowywanie plików w datowanych podkatalogach `public/uploads/media/YYYY/MM/DD`, integrację z dashboardem i pływającymi skrótami administracyjnymi oraz komplet formularzy, tłumaczeń, walidacji i testów jednostkowych.
- Rozszerzono galerię mediów o zmianę unikalnej nazwy obrazka z zachowaniem nazwy oryginalnej, podgląd obrazu w popupie, kopiowanie publicznego adresu URL, potwierdzenia dla usuwania pojedynczego pliku i czyszczenia całej galerii oraz dodatkowe filtrowanie listy wyłącznie do obsługiwanych formatów obrazków.
- Przebudowano układ i interakcje galerii mediów, rozdzielając badge metadanych, dopracowując ikonowe akcje i tooltipy, dodając pusty kafelek kończący siatkę oraz zamieniając go w aktywny slot drag-and-drop, który po upuszczeniu pliku uruchamia upload do serwera, pokazuje stan `drag-over` i preloader z trzema migającymi kropkami.
- Dodano reużywalny komponent uploadu plików z własnym modułem JavaScript i wspólnym CSS, dopasowany wizualnie do panelu administracyjnego, z obsługą wyboru pliku, opcjonalnym drag-and-drop sterowanym przez `data-file-upload-drop-enabled`, stanami overlay z dużym `+` oraz implementacją używaną na stronie `Dodaj obrazek`.
- Wydzielono wspólne serwisy `ManagedUploadedFileStorage`, `ManagedFilePathResolver`, `MediaImageStorage`, `MediaGalleryManager` i `MediaImageSupport`, porządkując zapis uploadów, bezpieczne rozwiązywanie ścieżek, filtrowanie obsługiwanych typów plików oraz współdzieloną logikę zarządzania plikami mediów.
- Dodano komendę `app:media:archive-orphans`, która wyszukuje porzucone pliki w `public/uploads/media/` bez odpowiadającego wpisu w bazie, przenosi je do katalogu tymczasowego, pakuje do archiwum ZIP w `var/media-orphans/` i raportuje listę plików oraz ścieżkę archiwum, a konfigurację rozszerzono o listę ignorowanych nazw `app.media_orphan_ignored_filenames` z domyślnym pomijaniem `.gitkeep`.
- Uzupełniono `README.md` o dokumentację modułu mediów, opis struktury katalogów uploadu, wyjaśnienie pojęcia porzuconych plików oraz instrukcję uruchamiania komendy `app:media:archive-orphans` wraz z katalogami roboczymi i zachowaniem procesu archiwizacji.

## 2026-04-06

- Naprawiono rozwijane sekcje w zadokowanym menu skrótów administratora po przejściu do stanu zwiniętego, tak aby kliknięcie ponownie poprawnie otwierało submenu z dziećmi zamiast zamykać odziedziczony stan `is-open`.
- Uszczelniono przełączanie między zwiniętym i rozwiniętym wariantem zadokowanego menu skrótów administratora, zamykając otwarte popovery i submenu przy zmianie stanu panelu, aby nie pozostawały widoczne po kliknięciu przycisku rozwinięcia.
- Rozszerzono skróty administratora o oznaczanie aktywnej trasy i automatyczne otwieranie odpowiedniej sekcji po wejściu na stronę, gdy panel jest zadokowany i rozwinięty, wraz z wyróżnieniem aktywnych pozycji i bieżącej strony.
- Przebudowano fullscreen podglądu obrazów używanego m.in. w galerii mediów, tak aby sam modal wykorzystywał całą dostępną przestrzeń, a obrazy były wyśrodkowane w obu osiach bez sztucznego powiększania ponad naturalny rozmiar.
- Dopasowano preloader panelu administracyjnego do jasnego i ciemnego motywu, wprowadzając osobne warianty kolorystyczne overlayu, panelu statusu i animacji oraz ustawianie `data-theme` już w `<head>`, aby właściwy motyw był widoczny od pierwszego renderu.

## 2026-04-07

- Rozszerzono formularze dodawania i edycji artykułu o wybór `Grafiki nagłówkowej` bezpośrednio z biblioteki mediów, przekazując do widoków listę obrazków galerii i dodając w polu grafiki szybkie akcje otwarcia pickera, czyszczenia wartości oraz podgląd aktualnie używanego obrazu.
- Dodano w popupie wyboru grafiki nagłówkowej kompaktowy picker oparty o kafelki obrazów z nakładką na podglądzie, przenosząc tytuł, metadane i przycisk `Wybierz` na sam obrazek, upraszczając układ listy oraz dopracowując minimalistyczny scrollbar i blokadę przewijania strony na czas otwartego modala.
- Rozszerzono picker grafiki nagłówkowej o wyszukiwanie po nazwie oryginalnej i niestandardowej, domyślne ograniczenie widoku do 10 najnowszych obrazków, sortowanie po dacie `od najnowszych` lub `od najstarszych`, poprawne filtrowanie bez przypadkowego submitu formularza artykułu po wciśnięciu `Enter` oraz komunikat pustych wyników.
- Uspójniono obsługę domyślnej grafiki nagłówkowej i internacjonalizacji nowego pickera, tak aby podgląd poprawnie korzystał z systemowego obrazka fallbackowego, modal używał kluczy `i18n` zamiast tekstów hardcoded, a zamknięcie korzystało z etykiety `Zamknij`.
- Uszczelniono backend formularza artykułu pod kątem brakującego pola `Treść`, ustawiając dla `content` bezpieczne `empty_data`, dzięki czemu nie dochodzi już do wyjątku `InvalidTypeException`, a brak wartości przechodzi przez standardową walidację formularza.
