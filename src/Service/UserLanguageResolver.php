<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserLanguageResolver
{
    private const COOKIE_NAME = 'user_language';
    private const DEFAULT_LANGUAGE = 'pl';
    private const SUPPORTED_LANGUAGES = ['pl', 'en'];
    private const LEGACY_TRANSLATION_KEYS = [
        "Hasło jest wymagane dla nowego użytkownika.\0Password is required for a new user." => 'flash_user_password_required',
        "Użytkownik został utworzony.\0User created." => 'flash_user_created',
        "Nie możesz odebrać sobie roli administratora.\0You cannot remove your own administrator role." => 'flash_user_cannot_remove_own_admin_role',
        "Nie możesz dezaktywować aktualnie zalogowanego konta.\0You cannot deactivate the currently signed-in account." => 'flash_user_cannot_deactivate_current_account',
        "Użytkownik został zaktualizowany.\0User updated." => 'flash_user_updated',
        "Nie możesz usunąć pierwszego administratora.\0You cannot delete the first administrator." => 'flash_user_cannot_delete_first_admin',
        "Użytkownik został usunięty.\0User deleted." => 'flash_user_deleted',
        "Plik importu został dodany do kolejki.\0The import file has been added to the queue." => 'flash_article_import_queued',
        "Plik importu nie jest dostępny do pobrania.\0The import file is not available for download." => 'flash_article_import_download_unavailable',
        "Import został usunięty.\0The import has been deleted." => 'flash_article_import_deleted',
        "Wszystkie importy zostały usunięte.\0All imports have been deleted." => 'flash_article_imports_deleted_all',
        "Kategoria została dodana.\0Category created." => 'flash_category_created',
        "Kategoria została zaktualizowana.\0Category updated." => 'flash_category_updated',
        "Kategoria została usunięta.\0Category deleted." => 'flash_category_deleted',
        "Eksport kategorii jest już w kolejce.\0Category export is already queued." => 'flash_category_export_already_queued',
        "Eksport kategorii został dodany do kolejki.\0Category export added to the queue." => 'flash_category_export_queued',
        "Wybierz co najmniej jedną kategorię do eksportu.\0Select at least one category to export." => 'flash_category_export_select_at_least_one',
        "Eksport zaznaczonych kategorii jest już w kolejce.\0Selected category exports are already queued." => 'flash_category_export_selected_already_queued',
        "Nie udało się wygenerować sluga kategorii.\0Failed to generate a category slug." => 'flash_category_slug_generation_failed',
        "Nie udało się usunąć pliku obrazka z dysku. Obrazek pozostał w galerii.\0The image file could not be deleted from disk. The image remains in the gallery." => 'flash_media_delete_disk_failed',
        "Obrazek został usunięty z galerii.\0The image has been removed from the gallery." => 'flash_media_deleted',
        "Galeria została wyczyszczona.\0The gallery has been cleared." => 'flash_media_gallery_cleared',
        "Usunięto tylko część obrazków z galerii. Nie udało się usunąć wszystkich plików z dysku.\0Only part of the gallery was cleared. Not all files could be deleted from disk." => 'flash_media_gallery_partially_cleared',
        "Podaj niestandardową nazwę obrazka.\0Provide a custom image name." => 'flash_media_custom_name_required',
        "Niestandardowa nazwa obrazka może mieć maksymalnie 255 znaków.\0The custom image name can be at most 255 characters long." => 'flash_media_custom_name_too_long',
        "Taka niestandardowa nazwa obrazka już istnieje.\0This custom image name already exists." => 'flash_media_custom_name_exists',
        "Niestandardowa nazwa obrazka została zapisana.\0The custom image name has been saved." => 'flash_media_custom_name_saved',
        "Nie udało się dodać obrazka. Sprawdź błędy formularza.\0The image could not be added. Check the form errors." => 'flash_media_create_failed',
        "Obrazek został dodany do galerii.\0The image has been added to the gallery." => 'flash_media_created',
        "Słowo kluczowe zostało dodane.\0Keyword created." => 'flash_keyword_created',
        "Słowo kluczowe zostało zaktualizowane.\0Keyword updated." => 'flash_keyword_updated',
        "Słowo kluczowe zostało usunięte.\0Keyword deleted." => 'flash_keyword_deleted',
        "Eksport słów kluczowych jest już w kolejce.\0Keyword export is already queued." => 'flash_keyword_export_already_queued',
        "Eksport słów kluczowych został dodany do kolejki.\0Keyword export added to the queue." => 'flash_keyword_export_queued',
        "Plik importu kategorii został dodany do kolejki.\0The category import file has been added to the queue." => 'flash_category_import_queued',
        "Plik importu kategorii nie jest dostępny do pobrania.\0The category import file is not available for download." => 'flash_category_import_download_unavailable',
        "Import kategorii został usunięty.\0The category import has been deleted." => 'flash_category_import_deleted',
        "Wszystkie importy kategorii zostały usunięte.\0All category imports have been deleted." => 'flash_category_imports_deleted_all',
        "Plik eksportu nie jest dostępny do pobrania.\0The export file is not available for download." => 'flash_export_download_unavailable',
        "Eksport został usunięty.\0The export has been deleted." => 'flash_export_deleted',
        "Wszystkie eksporty zostały usunięte.\0All exports have been deleted." => 'flash_exports_deleted_all',
        "Artykuł został dodany.\0Article created." => 'flash_article_created',
        "Artykuł został zaktualizowany.\0Article updated." => 'flash_article_updated',
        "Artykuł został zarchiwizowany.\0Article archived." => 'flash_article_archived',
        "Artykuł jest już opublikowany.\0Article is already published." => 'flash_article_already_published',
        "Artykuł został opublikowany.\0Article published." => 'flash_article_published',
        "Eksport artykułu jest już w kolejce.\0Article export is already queued." => 'flash_article_export_already_queued',
        "Eksport artykułu został dodany do kolejki.\0Article export added to the queue." => 'flash_article_export_queued',
        "Artykuł ma już przypisanego autora.\0Article already has an author assigned." => 'flash_article_author_already_assigned',
        "Autor artykułu został przypisany.\0Article author assigned." => 'flash_article_author_assigned',
        "Wybierz co najmniej jeden artykuł do eksportu.\0Select at least one article to export." => 'flash_article_export_select_at_least_one',
        "Eksport zaznaczonych artykułów jest już w kolejce.\0Selected article exports are already queued." => 'flash_article_export_selected_already_queued',
        "Usunąć można tylko zarchiwizowany artykuł.\0Only archived articles can be deleted." => 'flash_article_delete_only_archived',
        "Artykuł został usunięty.\0Article deleted." => 'flash_article_deleted',
        "Plik importu menu został dodany do kolejki.\0The top menu import file has been added to the queue." => 'flash_top_menu_import_queued',
        "Plik importu menu nie jest dostępny do pobrania.\0The top menu import file is not available for download." => 'flash_top_menu_import_download_unavailable',
        "Import menu został usunięty.\0The top menu import has been deleted." => 'flash_top_menu_import_deleted',
        "Wszystkie importy menu zostały usunięte.\0All top menu imports have been deleted." => 'flash_top_menu_imports_deleted_all',
        "Element menu został dodany.\0Menu item created." => 'flash_top_menu_created',
        "Element menu został zaktualizowany.\0Menu item updated." => 'flash_top_menu_updated',
        "Nie można usunąć elementu menu, który ma dzieci.\0You cannot delete a menu item that still has children." => 'flash_top_menu_delete_has_children',
        "Element menu został usunięty.\0Menu item deleted." => 'flash_top_menu_deleted',
        "Eksport top menu jest już w kolejce.\0Top menu export is already queued." => 'flash_top_menu_export_already_queued',
        "Eksport top menu został dodany do kolejki.\0Top menu export added to the queue." => 'flash_top_menu_export_queued',
        "Nieprawidłowe dane do zmiany kolejności menu.\0Invalid payload for menu reordering." => 'flash_top_menu_reorder_invalid_payload',
        "Nie udało się zapisać nowej kolejności elementów menu.\0The new menu item order could not be saved." => 'admin_top_menu_tree_save_error',
        "Nowa kolejność elementów menu została zapisana.\0The new menu item order has been saved." => 'admin_top_menu_tree_save_success',
        "Nie udało się wygenerować unikalnej nazwy elementu menu.\0Failed to generate a unique menu item name." => 'flash_top_menu_unique_name_generation_failed',
        "Kolejka oczekujących elementów została wyczyszczona.\0The pending queue has been cleared." => 'flash_queue_cleared',
        "Element został usunięty z kolejki.\0The item has been removed from the queue." => 'flash_queue_item_deleted',
        "Plik importu słów kluczowych został dodany do kolejki.\0The keyword import file has been added to the queue." => 'flash_keyword_import_queued',
        "Plik importu słów kluczowych nie jest dostępny do pobrania.\0The keyword import file is not available for download." => 'flash_keyword_import_download_unavailable',
        "Import słów kluczowych został usunięty.\0The keyword import has been deleted." => 'flash_keyword_import_deleted',
        "Wszystkie importy słów kluczowych zostały usunięte.\0All keyword imports have been deleted." => 'flash_keyword_imports_deleted_all',
        "Ustawienia bloga zostały zapisane.\0Blog settings have been saved." => 'flash_blog_settings_saved',
        "Wybrana kategoria\0Selected category" => 'blog_category_fallback_title',
        "Artykuły przypisane do wybranej kategorii.\0Articles assigned to the selected category." => 'blog_category_fallback_description',
        "Słowo kluczowe\0Keyword" => 'blog_keyword_fallback_title',
        "Artykuły oznaczone wybranym słowem kluczowym.\0Articles tagged with the selected keyword." => 'blog_keyword_fallback_description',
        "oczekujących importów słów kluczowych\0pending keyword imports" => 'admin_shortcut_keyword_imports_badge',
        "oczekujących importów kategorii\0pending category imports" => 'admin_shortcut_category_imports_badge',
        "Dozwolone formaty: JPG, PNG, WEBP, GIF, AVIF. Maksymalny rozmiar pliku: {{ limit }}.\0Allowed formats: JPG, PNG, WEBP, GIF, AVIF. Maximum file size: {{ limit }}." => 'admin_media_form_file_hint',
        "Import kategorii\0Category import" => 'admin_category_imports_title',
        "Import słów kluczowych\0Keyword import" => 'admin_article_keyword_imports_title',
        "Nie znaleziono strony\0Page not found" => 'error_not_found_page_title_short',
        "Nie znaleziono zasobu\0Resource not found" => 'error_not_found_prompt',
        "Wygląda na to, że adres jest nieprawidłowy albo artykuł został usunięty, ukryty lub jeszcze nie został opublikowany.\0The address appears to be invalid, or the article was removed, hidden, or has not been published yet." => 'error_not_found_message',
        "Wróć na stronę główną\0Back to homepage" => 'error_back_home',
        "Przejdź do listy artykułów\0Browse articles" => 'error_browse_articles',
        "Brak dostępu\0Access denied" => 'error_access_denied_title',
        "Sekcja administracyjna\0Administration section" => 'error_access_denied_prompt',
        "Nie masz uprawnień, aby wejść do tej sekcji. Jeśli potrzebujesz dostępu administratora, skontaktuj się z właścicielem systemu.\0You do not have permission to enter this section. If you need administrator access, contact the system owner." => 'error_access_denied_message',
        "Wyloguj się i zmień konto\0Log out and switch account" => 'error_access_denied_logout',
        "Przejdź do logowania\0Go to login" => 'error_access_denied_login',
    ];

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly ?TranslatorInterface $translator = null,
    )
    {
    }

    public function getLanguage(): string
    {
        return $this->resolveLanguage($this->requestStack->getCurrentRequest());
    }

    public function resolveLanguage(?Request $request): string
    {
        $language = $request?->cookies->get(self::COOKIE_NAME);

        if (!is_string($language) || '' === trim($language)) {
            return self::DEFAULT_LANGUAGE;
        }

        $language = strtolower(trim($language));

        return in_array($language, self::SUPPORTED_LANGUAGES, true)
            ? $language
            : self::DEFAULT_LANGUAGE;
    }

    /**
     * @param array<string, string|int|float|\Stringable> $parameters
     */
    public function translate(string $id, string|array|null $englishOrParameters = null, string $domain = 'app'): string
    {
        if (is_array($englishOrParameters) || null === $englishOrParameters) {
            return $this->translator?->trans($id, $englishOrParameters ?? [], $domain, $this->getLanguage())
                ?? $id;
        }

        $translationKey = self::LEGACY_TRANSLATION_KEYS[$id."\0".$englishOrParameters] ?? null;
        if (null !== $translationKey && null !== $this->translator) {
            return $this->translator->trans($translationKey, [], 'app', $this->getLanguage());
        }

        return 'pl' === $this->getLanguage() ? $id : $englishOrParameters;
    }
}
