<?php

namespace Modules\Core\Helpers;

class LanguageFlags
{
    /**
     * Get SVG flag for a specific language code
     *
     * @param  string  $langCode  ISO language code
     * @return string SVG markup
     */
    public static function getFlag(string $langCode, $base64 = false): string
    {
        if ($base64) {
            $flag = self::FLAGS[strtolower($langCode)] ?? self::FLAGS['en'];

            return 'data:image/svg+xml;base64,'.base64_encode($flag);
        }

        return self::FLAGS[strtolower($langCode)] ?? self::FLAGS['en'];
    }

    /**
     * SVG flags for popular languages (16px)
     */
    public const FLAGS = [
        'en' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 16" width="24" height="16"><rect width="24" height="16" fill="#012169"/><path d="M0,0 L24,16 M24,0 L0,16" stroke="#fff" stroke-width="3"/><path d="M0,0 L24,16 M24,0 L0,16" stroke="#C8102E" stroke-width="2"/><path d="M12,0 L12,16 M0,8 L24,8" stroke="#fff" stroke-width="5"/><path d="M12,0 L12,16 M0,8 L24,8" stroke="#C8102E" stroke-width="3"/></svg>',

        'es' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 16" width="24" height="16"><rect width="24" height="16" fill="#c60b1e"/><rect width="24" height="8" y="4" fill="#ffc400"/><path d="M6,8.5 C6,9.5 7,10.5 8,10.5 L9,10.5 C10,10.5 11,9.5 11,8.5 C11,7.5 10,6.5 9,6.5 L8,6.5 C7,6.5 6,7.5 6,8.5 Z" fill="#c60b1e"/></svg>',

        'fr' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 16" width="24" height="16"><rect width="8" height="16" fill="#002654"/><rect width="8" height="16" x="8" fill="#fff"/><rect width="8" height="16" x="16" fill="#ce1126"/></svg>',

        'de' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 16" width="24" height="16"><rect width="24" height="5.33" y="0" fill="#000"/><rect width="24" height="5.33" y="5.33" fill="#DD0000"/><rect width="24" height="5.33" y="10.66" fill="#FFCE00"/></svg>',

        'ar' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 16" width="24" height="16"><rect width="24" height="5.33" y="0" fill="#CE1126"/><rect width="24" height="5.33" y="5.33" fill="#fff"/><rect width="24" height="5.33" y="10.66" fill="#000"/><path d="M12,8 C12,8 12.5,6.5 14,6.5 C15.5,6.5 16,8 16,8" stroke="#C09300" stroke-width="0.75" fill="none"/><path d="M12,9.5 l4,0" stroke="#C09300" stroke-width="0.75"/></svg>',

        'zh' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 16" width="24" height="16"><rect width="24" height="16" fill="#DE2910"/><path d="M4,3 L5,1 L6,3 L8,3 L6.5,4.5 L7,6.5 L5,5.5 L3,6.5 L3.5,4.5 L2,3 Z" fill="#FFDE00"/><path d="M11,2 L11.5,3 L12.5,3 L11.75,3.75 L12,5 L11,4.25 L10,5 L10.25,3.75 L9.5,3 L10.5,3 Z" fill="#FFDE00"/><path d="M13,5 L13.5,6 L14.5,6 L13.75,6.75 L14,8 L13,7.25 L12,8 L12.25,6.75 L11.5,6 L12.5,6 Z" fill="#FFDE00"/><path d="M13,9 L13.5,10 L14.5,10 L13.75,10.75 L14,12 L13,11.25 L12,12 L12.25,10.75 L11.5,10 L12.5,10 Z" fill="#FFDE00"/><path d="M11,12 L11.5,13 L12.5,13 L11.75,13.75 L12,15 L11,14.25 L10,15 L10.25,13.75 L9.5,13 L10.5,13 Z" fill="#FFDE00"/></svg>',

        'ru' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 16" width="24" height="16"><rect width="24" height="5.33" y="0" fill="#fff"/><rect width="24" height="5.33" y="5.33" fill="#0039A6"/><rect width="24" height="5.33" y="10.66" fill="#D52B1E"/></svg>',

        'pt' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 16" width="24" height="16"><rect width="24" height="16" fill="#006600"/><path d="M8,8 L16,8" stroke="#FFCC00" stroke-width="8"/><circle cx="12" cy="8" r="3.5" fill="#002776" stroke="#fff" stroke-width="0.5"/></svg>',

        'ja' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 16" width="24" height="16"><rect width="24" height="16" fill="#fff"/><circle cx="12" cy="8" r="4" fill="#bc002d"/></svg>',

        'it' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 16" width="24" height="16"><rect width="8" height="16" fill="#009246"/><rect width="8" height="16" x="8" fill="#fff"/><rect width="8" height="16" x="16" fill="#ce2b37"/></svg>',
    ];
}
