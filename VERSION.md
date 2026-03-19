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
