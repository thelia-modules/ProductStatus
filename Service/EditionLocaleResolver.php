<?php

declare(strict_types=1);

/*
 * This file is part of the Thelia package.
 * http://www.thelia.net
 *
 * (c) OpenStudio <info@thelia.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ProductStatus\Service;

use Symfony\Component\HttpFoundation\Request;
use Thelia\Model\Lang;
use Thelia\Model\LangQuery;

/**
 * The language in which statuses are edited in the back-office, resolved the same way
 * when a screen is rendered and when its form is saved, and the same way as the
 * default-twig back-office resolves its edit language:
 *
 *  1. `edit_language_id` posted with the form, or given in the query string;
 *  2. the edit language stored in the admin session;
 *  3. the language of the admin interface, when the shop has it;
 *  4. the default language of the shop.
 *
 * The admin interface language alone never decides: an administrator browsing the
 * back-office in French while editing the English content writes English.
 */
final readonly class EditionLocaleResolver
{
    public const PARAMETER = 'edit_language_id';

    private const SESSION_KEY = 'thelia.admin.edition.lang';

    private const LAST_RESORT_LOCALE = 'en_US';

    public function resolveLang(?Request $request): Lang
    {
        if (null === $request) {
            return Lang::getDefaultLanguage();
        }

        $editLanguageId = (int) ($request->request->get(self::PARAMETER) ?? $request->query->get(self::PARAMETER) ?? 0);
        if ($editLanguageId > 0 && null !== $lang = LangQuery::create()->findPk($editLanguageId)) {
            return $lang;
        }

        $storedLang = $request->hasSession() ? $request->getSession()->get(self::SESSION_KEY) : null;
        if ($storedLang instanceof Lang) {
            return $storedLang;
        }

        return LangQuery::create()->findOneByLocale($request->getLocale()) ?? Lang::getDefaultLanguage();
    }

    public function resolveLocale(?Request $request): string
    {
        return $this->resolveLang($request)->getLocale()
            ?? Lang::getDefaultLanguage()->getLocale()
            ?? self::LAST_RESORT_LOCALE;
    }
}
