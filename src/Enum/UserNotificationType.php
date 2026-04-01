<?php

declare(strict_types=1);

namespace App\Enum;

enum UserNotificationType: string
{
    case IMPORT_COMPLETED_SUCCESS = 'import_completed_success';
    case IMPORT_COMPLETED_ERROR = 'import_completed_error';
    case TOP_MENU_IMPORT_COMPLETED_SUCCESS = 'top_menu_import_completed_success';
    case TOP_MENU_IMPORT_COMPLETED_ERROR = 'top_menu_import_completed_error';
    case EXPORT_COMPLETED_SUCCESS = 'export_completed_success';
    case EXPORT_COMPLETED_ERROR = 'export_completed_error';

    public function translationKey(): string
    {
        return 'user_notification_'.$this->value;
    }

    public function flashType(): string
    {
        return match ($this) {
            self::IMPORT_COMPLETED_ERROR,
            self::TOP_MENU_IMPORT_COMPLETED_ERROR,
            self::EXPORT_COMPLETED_ERROR => 'error',
            default => 'success',
        };
    }

    public function actionLabelTranslationKey(): string
    {
        return match ($this) {
            self::IMPORT_COMPLETED_SUCCESS,
            self::IMPORT_COMPLETED_ERROR => 'user_notification_action_imports',
            self::TOP_MENU_IMPORT_COMPLETED_SUCCESS,
            self::TOP_MENU_IMPORT_COMPLETED_ERROR => 'user_notification_action_top_menu_imports',
            self::EXPORT_COMPLETED_SUCCESS,
            self::EXPORT_COMPLETED_ERROR => 'user_notification_action_exports',
        };
    }

    public function targetRouteName(): string
    {
        return match ($this) {
            self::IMPORT_COMPLETED_SUCCESS,
            self::IMPORT_COMPLETED_ERROR => 'admin_article_import_index',
            self::TOP_MENU_IMPORT_COMPLETED_SUCCESS,
            self::TOP_MENU_IMPORT_COMPLETED_ERROR => 'admin_top_menu_import_index',
            self::EXPORT_COMPLETED_SUCCESS,
            self::EXPORT_COMPLETED_ERROR => 'admin_article_export_index',
        };
    }
}
