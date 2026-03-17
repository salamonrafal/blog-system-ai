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
