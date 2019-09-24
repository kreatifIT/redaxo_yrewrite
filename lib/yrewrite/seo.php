<?php

/**
 * YREWRITE Addon.
 *
 * @author  jan.kristinus@yakamara.de
 *
 * @package redaxo\yrewrite
 */

class rex_yrewrite_seo
{
    public $article = null, $domain = null;

    public static $priority = ['1.0', '0.7', '0.5', '0.3', '0.1', '0.0'], $priority_default = '', $index_setting_default = 0, $changefreq = [
        'always',
        'hourly',
        'daily',
        'weekly',
        'monthly',
        'yearly',
        'never',
    ], $changefreq_default = 'weekly', $robots_default = "User-agent: *\nDisallow:", $title_scheme_default = '%T / %SN';

    /**
     * @var string
     */
    public static $meta_title_field = 'yrewrite_title';
    /**
     * @var string
     */
    public static $meta_description_field = 'yrewrite_description';
    /**
     * @var string
     */
    public static $meta_changefreq_field = 'yrewrite_changefreq';
    /**
     * @var string
     */
    public static $meta_priority_field = 'yrewrite_priority';
    /**
     * @var string
     */
    public static $meta_index_field = 'yrewrite_index';
    /**
     * @var string
     */
    public static $meta_canonical_url_field = 'yrewrite_canonical_url';

    public function __construct($article_id = 0, $clang = null)
    {
        if ($article_id == 0) {
            $article_id = rex_article::getCurrentId();
        }
        if (is_null($clang)) {
            $clang = rex_clang::getCurrentId();
        }

        if (($article = rex_article::get($article_id, $clang))) {
            $this->article = $article;
            $this->domain  = rex_yrewrite::getDomainByArticleId($article_id, $clang);
        }
    }

    public function getTitleTag()
    {
        return '<title>' . rex_escape(strip_tags($this->getTitle())) . '</title>'; //  lang="de"
    }

    public function getDescriptionTag()
    {
        return '<meta name="description" content="' . rex_escape(strip_tags($this->getDescription())) . '">'; //  lang="de"
    }

    public function getCanonicalUrlTag()
    {
        return '<link rel="canonical" href="' . rex_escape($this->getCanonicalUrl()) . '" />';
    }

    public function getRobotsTag()
    {
        if ($this->article->getValue(self::$meta_index_field) == 1 || ($this->article->getValue(self::$meta_index_field) == 0 && $this->article->isOnline())) {
            return '<meta name="robots" content="index, follow">';
        } else {
            return '<meta name="robots" content="noindex, nofollow">';
        }
    }

    public function getTitle()
    {
        $title_scheme = htmlspecialchars_decode(trim($this->domain->getTitle()));
        if ($title_scheme == '') {
            $title_scheme = self::$title_scheme_default;
        }

        $ytitle = '';
        if ($this->article && $this->article->getValue(self::$meta_title_field) != '') {
            $ytitle = $this->article->getValue(self::$meta_title_field);
        }
        if ($ytitle == '') {
            $ytitle = $this->article->getValue('name');
        }

        $ytitle       = rex_extension::registerPoint(new rex_extension_point('YREWRITE_TITLE', $ytitle, ['article' => $this->article]));
        $title_scheme = rex_extension::registerPoint(new rex_extension_point('YREWRITE_TITLE_SCHEME', $title_scheme, ['article' => $this->article]));

        $title = $title_scheme;
        $title = str_replace('%T', $ytitle, $title);
        $title = str_replace('%SN', rex::getServerName(), $title);

        return $this->cleanString($title);
    }

    public function getDescription($content_length = 180)
    {
        $description = trim(rex_extension::registerPoint(new rex_extension_point('YREWRITE_DESCRIPTION',
            $this->article->getValue(self::$meta_description_field), ['article' => $this->article])));

        if ($description != '') {
            $description = strip_tags($description);
            $description = wordwrap($description, $content_length, '' . "|||||||");
            $description = explode("|||||||", $description);
            $description = array_shift($description);
        }
        return $this->cleanString($description);
    }

    public function getCanonicalUrl()
    {
        $canonical_url = trim($this->article->getValue(self::$meta_canonical_url_field));
        if ($canonical_url == "") {
            $canonical_url = rex_yrewrite::getFullUrlByArticleId($this->article->getId(), $this->article->getClang());
        }
        $canonical_url = rex_extension::registerPoint(new rex_extension_point('YREWRITE_CANONICAL_URL', $canonical_url, ['article' => $this->article]));
        return $canonical_url;
    }

    public function getHrefLangs()
    {
        $current_mount_id = $this->domain->getMountId();

        $lang_domains = [];
        foreach (rex_yrewrite::getDomains() as $domain) {
            if ($current_mount_id == $domain->getMountId()) {
                foreach ($domain->getClangs() as $clang) {
                    if ($lang = rex_clang::get($clang)) {
                        $article = rex_article::getCurrent($clang);
                        if ($article->isOnline() && $lang->isOnline())
                        {
                            $lang_domains[$lang->getCode()] = rex_yrewrite::getFullUrlByArticleId($article->getId(), $lang->getId());
                        }
                    }
                }
                break;
            }
        }

        return rex_extension::registerPoint(new rex_extension_point('YREWRITE_HREFLANG_TAGS', $lang_domains, ['article' => $this->article]));
    }

    public function getHreflangTags()
    {
        $return       = '';
        $lang_domains = $this->getHrefLangs();

        foreach ($lang_domains as $code => $url) {
            $return .= '<link rel="alternate" hreflang="' . $code . '" href="' . $url . '" />';
        }
        return $return;
    }


    public function getImages()
    {
        $images = rex_extension::registerPoint(new rex_extension_point('YREWRITE_IMAGES', '', ['article' => $this->article]));

        if ($images === '') {
            $sql = rex_sql::factory();

            $mediaFields = $sql->getArray('
                SELECT jt1.name
                FROM rex_metainfo_type AS m
                LEFT JOIN rex_metainfo_field jt1 ON jt1.type_id = m.id
                WHERE m.label = "REX_MEDIA_WIDGET" OR m.label = "REX_MEDIALIST_WIDGET"
                ORDER BY priority
            ');

            foreach ($mediaFields as $mfield) {
                $images = $this->article->getValue($mfield['name']);

                if ($images != '') {
                    break;
                }
            }

            if ($images == '') {
                // image from slices
                $sql->setQuery('
                    SELECT
                        TRIM(BOTH "," FROM CONCAT_WS(",", 
                            media1, media2, media3, media4, media5, media6, media7, media8, media9, media10,
                            medialist1, medialist2, medialist3, medialist4, medialist5, medialist6, medialist7, medialist8, medialist9, medialist10
                        )) AS mediagroup
                    FROM rex_article_slice 
                    WHERE 
                        article_id = :article_id 
                        AND clang_id = :clang_id 
                        AND (
                            media1 > ""
                            OR media1 > ""
                            OR media2 > ""
                            OR media3 > ""
                            OR media4 > ""
                            OR media5 > ""
                            OR media6 > ""
                            OR media7 > ""
                            OR media8 > ""
                            OR media9 > ""
                            OR media10 > ""
                            OR medialist1 > ""
                            OR medialist2 > ""
                            OR medialist3 > ""
                            OR medialist4 > ""
                            OR medialist5 > ""
                            OR medialist6 > ""
                            OR medialist7 > ""
                            OR medialist8 > ""
                            OR medialist9 > ""
                            OR medialist10 > ""
                        ) 
                    ORDER BY priority LIMIT 1', [
                    'article_id' => $this->article->getId(),
                    'clang_id'   => $this->article->getClangId(),
                ]);
                $images = $sql->hasNext() ? $sql->getValue('mediagroup') : '';
            }
        }
        return $images;
    }

    public function getImageTag()
    {
        $return = '';
        $images = $this->getImages();

        if ($images != '') {
            $image = array_shift(explode(',', $images));
            $media = rex_media::get($image);
            if ($media) {
                $attrs = rex_extension::registerPoint(new rex_extension_point('YREWRITE_IMAGE_ATTRIBUTES', [
                    'src'    => rex_yrewrite::getFullPath(ltrim($media->getUrl(), '/')),
                    'width'  => $media->getValue('width'),
                    'height' => $media->getValue('height'),
                ], ['media' => $media]));

                $return = '
                <meta property="og:image" content="' . $attrs['src'] . '" />
                <meta property="og:image:width" content="' . $attrs['width'] . '" />
                <meta property="og:image:height" content="' . $attrs['height'] . '" />
                <meta property="twitter:image" content="' . $attrs['src'] . '" />
                <meta name="image" content="' . $attrs['src'] . '" />
            ';
            }
        }
        return $return;
    }

    public function getSocialTags()
    {
        return '
            <meta property="og:url" content="' . $this->getCanonicalUrl() . '"/>
            <meta property="og:title" content="' . $this->getTitle() . '"/>
            <meta property="og:description" content="' . $this->getDescription(200) . '"/>
            <meta property="og:type" content="Article"/>
            <meta name="twitter:card" content="summary_large_image"/>
        ';
    }


    public function cleanString($str)
    {
        return str_replace(["\n", "\r"], [' ', ''], $str);
    }

    // ----- global static functions

    public function sendRobotsTxt($domain = '')
    {
        if ($domain == '') {
            $domain = rex_yrewrite::getHost();
        }

        header('Content-Type: text/plain');
        // header content length ?
        $content = 'Sitemap: ' . rex_yrewrite::getFullPath('sitemap.xml') . "\n\n";

        if (rex_yrewrite::getDomainByName($domain)) {
            $robots = rex_yrewrite::getDomainByName($domain)->getRobots();
            if ($robots != '') {
                $content .= $robots;
            } else {
                $content .= self::$robots_default;
            }
        }

        echo $content;
        exit;
    }

    public function sendSitemap($domain = '')
    {

        $domains = rex_yrewrite::getDomains();

        if ($domain == '') {
            $domain = rex_yrewrite::getHost();
        }

        $sitemap = [];

        if (rex_yrewrite::getDomainByName($domain) || count($domains) == 1) {
            $urls = [];

            if (count($domains) == 1) {
                $domain = rex_yrewrite::getDefaultDomain();
            } else {
                $domain = rex_yrewrite::getDomainByName($domain);
            }

            $domain_article_id = $domain->getStartId();
            $paths             = 0;
            if (($dai = rex_article::get($domain_article_id))) {
                $paths = count($dai->getParentTree());
            }

            foreach (rex_yrewrite::getPathsByDomain($domain->getName()) as $article_id => $path) {
                foreach ($domain->getClangs() as $clang_id) {

                    if (!rex_clang::get($clang_id)->isOnline()) {
                        continue;
                    }

                    $article = rex_article::get($article_id, $clang_id);
                    $category = $article->getParent() ?: $article->getCategory();

                    if ($category && !$category->isOnline()) {
                        continue;
                    }

                    if (
                        ($article) &&
                        $article->isPermitted() &&
                        ($article->getValue(self::$meta_index_field) == 1 || ($article->isOnline() && $article->getValue(self::$meta_index_field) == 0)) &&
                        ($article_id != $domain->getNotfoundId() || $article_id == $domain->getStartId())

                    ) {

                        $changefreq = $article->getValue(self::$meta_changefreq_field);
                        if (!in_array($changefreq, self::$changefreq)) {
                            $changefreq = self::$changefreq_default;
                        }

                        $priority = $article->getValue(self::$meta_priority_field);

                        if (!in_array($priority, self::$priority)) {
                            $article_paths = count($article->getParentTree());
                            $prio          = $article_paths - $paths - 1;
                            if ($prio < 0) {
                                $prio = 0;
                            }

                            if (isset(self::$priority[$prio])) {
                                $priority = self::$priority[$prio];
                            } else {
                                $priority = self::$priority_default;
                            }
                        }

                        $url = [
                            'loc'        => rex_yrewrite::getFullPath($path[$clang_id]),
                            'lastmod'    => date(DATE_W3C, $article->getValue('updatedate')),
                            'changefreq' => $changefreq,
                            'priority'   => $priority,
                            'image'      => [],
                        ];

                        $images = $this->getImages();

                        if ($images) {
                            $images = array_unique(array_filter(explode(',', $images)));

                            foreach ($images as $media_name) {
                                $media = rex_media::get($media_name);

                                if ($media && $media->isImage()) {
                                    $imgUrl         = [
                                        'loc'   => rex_yrewrite::getFullPath(ltrim($media->getUrl(), '/')),
                                        'title' => rex_escape($media->getTitle()),
                                    ];
                                    $url['image'][] = rex_extension::registerPoint(new rex_extension_point('YREWRITE_SITEMAP_IMAGE', $imgUrl,
                                        ['media' => $media, 'lang_id' => $clang_id]));
                                }
                            }
                        }

                        $urls[] = $url;
                    }
                }
            }

            $urls = rex_extension::registerPoint(new rex_extension_point('YREWRITE_DOMAIN_SITEMAP_URLS', $urls));

            foreach ($urls as $url) {
                $_item = "\n<url>";

                foreach ($url as $label1 => $value1) {

                    if (is_array($value1)) {
                        if (empty($value1)) {
                            continue;
                        }

                        foreach ($value1 as $item) {
                            $_item .= "\n\t<{$label1}:{$label1}>";

                            foreach ($item as $label2 => $value2) {
                                $_item .= "\n\t\t<{$label1}:{$label2}>{$value2}</{$label1}:{$label2}>";
                            }

                            $_item .= "\n\t</{$label1}:{$label1}>";
                        }
                    } else {
                        $_item .= "\n\t<{$label1}>{$value1}</{$label1}>";
                    }
                }
                $_item .= "\n" . '</url>';

                $sitemap[] = $_item;
            }
            $sitemap = rex_extension::registerPoint(new rex_extension_point('YREWRITE_DOMAIN_SITEMAP', $sitemap, ['domain' => $domain]));
        }
        $sitemap = rex_extension::registerPoint(new rex_extension_point('YREWRITE_SITEMAP', $sitemap));

        rex_response::cleanOutputBuffers();
        header('Content-Type: application/xml');
        $content = '<?xml version="1.0" encoding="UTF-8"?>';
        $content .= "\n".'<?xml-stylesheet type="text/xsl" href="assets/addons/yrewrite/xsl-stylesheets/xml-sitemap.xsl"?>';
        $content .= "\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">';
        $content .= implode("\n", $sitemap);
        $content .= "\n" . '</urlset>';
        echo $content;
        exit;
    }

    /* @deprecated */
    public static function checkArticlePerm($article)
    {
        return $article->isPermitted();
    }
}
