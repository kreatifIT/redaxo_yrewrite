<?php

/**
 * YREWRITE Addon.
 *
 * @author jan.kristinus@yakamara.de
 *
 * @package redaxo\yrewrite
 *
 * @var rex_addon $this
 */

// TODO: content/yrewrite_url: { title: 'translate:mode_url', perm: 'yrewrite[url]' }

ob_start();

$addon = rex_addon::get('yrewrite');

$article_id = $params['article_id'];
$clang = $params['clang'];
$ctype = $params['ctype'];

$domain = rex_yrewrite::getDomainByArticleId($article_id, $clang);
$isStartarticle = rex_yrewrite::isDomainStartArticle($article_id, $clang);

$autoUrl = rex_getUrl();
if (0 === strpos($autoUrl, $domain->getUrl())) {
    $autoUrl = substr($autoUrl, strlen($domain->getUrl()));
} else {
    $autoUrl = substr($autoUrl, strlen($domain->getPath()));
}

if ($isStartarticle) {

    echo rex_view::warning($addon->i18n('startarticleisalways', $domain->getName()));

} else {
    $article = rex_article::getCurrent();
    $data = $article->getValue('yrewrite_url_data');
    $values = strlen($data) ? json_decode($data, true) : [];

    $type = rex_request('yrewrite_func', 'string', $article->getValue('yrewrite_func'));
    $values[$type] = stripslashes(rex_request('yrewrite_val-'. $type, 'string', $values[$type]));

    $yform = new rex_yform();
    $yform->setObjectparams('form_action', rex_url::backendController(['page' => 'content/edit', 'article_id' => $article_id, 'clang' => $clang, 'ctype' => $ctype], false));
    $yform->setObjectparams('form_id', 'yrewrite-url');
    $yform->setObjectparams('form_name', 'yrewrite-url');
    $yform->setObjectparams('real_field_names', true);

    $yform->setObjectparams('form_showformafterupdate', 1);

    $yform->setObjectparams('main_table', rex::getTable('article'));
    $yform->setObjectparams('main_id', $article_id);
    $yform->setObjectparams('main_where', 'id='.$article_id.' and clang_id='.$clang);
    $yform->setObjectparams('getdata', true);

    $yform->setValueField('select', ['yrewrite_func', $addon->i18n('url_type'), 'attributes' => ['class' => 'form-control url-type-selector'], 'options' => [
        'auto' => ucfirst($addon->i18n('yrewrite_priority_auto')),
        'custom' => $addon->i18n('customurl'),
        'intern' => $addon->i18n('intern_url'),
        'extern' => $addon->i18n('extern_url'),
        'mediafile' => $addon->i18n('mediafile'),
    ]]);

    // custom url
    $yform->setValueField('html', ['', '<div class="yrewrite_url-wrapper custom '. ($type == 'custom' ? '' : 'hidden') .'">']);
    $yform->setValueField('text', ['yrewrite_val-custom', $addon->i18n('mode_url'), 'notice' => $autoUrl, 'default' => $values['custom'], 'no_db' => true]);
    $yform->setValueField('html', ['', '</div>']);

    // intern url
    $yform->setValueField('html', ['', '<div class="yrewrite_url-wrapper intern '. ($type == 'intern' ? '' : 'hidden') .'">']);
    $yform->setValueField('html', ['', '<div class="form-group yform-element"><label class="control-label">'. $this->i18n('forward_article_id') .'</label>'. rex_var_link::getWidget(1, 'yrewrite_val-intern', $values['intern']) .'</div>']);
    $yform->setValueField('html', ['', '</div>']);

    // extern url
    $yform->setValueField('html', ['', '<div class="yrewrite_url-wrapper extern '. ($type == 'extern' ? '' : 'hidden') .'">']);
    $yform->setValueField('text', ['yrewrite_val-extern', $addon->i18n('mode_url'), 'default' => $values['extern'], 'no_db' => true]);
    $yform->setValueField('html', ['', '</div>']);

    // media file
    $yform->setValueField('html', ['', '<div class="yrewrite_url-wrapper mediafile '. ($type == 'mediafile' ? '' : 'hidden') .'">']);
    $yform->setValueField('html', ['', '<div class="form-group yform-element"><label class="control-label">'. $this->i18n('forward_media') .'</label>'. rex_var_media::getWidget(1, 'yrewrite_val-mediafile', $values['mediafile']) .'</div>']);
    $yform->setValueField('html', ['', '</div>']);

    $yform->setValueField('hidden', ['yrewrite_url_data', json_encode($values)]);

    if ($type == 'custom') {
        $yform->setValidateField('customfunction', ['name'=>'yrewrite_val-custom', 'function' => function($func, $yrewrite_url ) {
            return (strlen($yrewrite_url) > 250);
        }, 'params'=>[], 'message' => rex_i18n::msg('yrewrite_warning_nottolong')]);
        $yform->setValidateField('customfunction', ['name'=>'yrewrite_val-custom', 'function' => function($func, $yrewrite_url ) {
            if ($yrewrite_url == "") return false;
            return (!preg_match('/^[%_\.+\-\/a-zA-Z0-9]+$/', $yrewrite_url));
        }, 'params'=>[], 'message' => rex_i18n::msg('yrewrite_warning_chars')]);

        $yform->setValidateField('customfunction', ['name'=>'yrewrite_val-custom', 'function' => function($func, $yrewrite_url, $params, $field ) {
            $return = (($a = rex_yrewrite::getArticleIdByUrl($params["domain"], $yrewrite_url)) && (key($a) != $params["article_id"] || current($a) != $params["clang"]));
            if ($return && $yrewrite_url != "") {
                $field->setElement("message", rex_i18n::msg('yrewrite_warning_urlexists', key($a) ));
            } else {
                $return = false;
            }
            return $return;
        }, 'params'=>['article_id' => $article_id, "domain" => $domain, "clang" => $clang], 'message' => rex_i18n::msg('yrewrite_warning_urlexists')]);
    }

    $yform->setActionField('db', [rex::getTable('article'), 'id=' . $article_id.' and clang_id='.$clang]);
    $yform->setObjectparams('submit_btn_label', $addon->i18n('update_url'));
    $form = $yform->getForm();

    if ($yform->objparams['actions_executed']) {
        $form = rex_view::success($addon->i18n('urlupdated')) . $form;
        rex_yrewrite::generatePathFile([
            'id' => $article_id,
            'clang' => $clang,
            'extension_point' => 'ART_UPDATED',
        ]);
        rex_article_cache::delete($article_id, $clang);

    } else {

    }

    echo $form;

    $selector_preview = '#yform-yrewrite-url-yrewrite_url p.help-block';
    $selector_url = '#yform-yrewrite-url-yrewrite_url input';

    echo '

<script type="text/javascript">

jQuery(document).ready(function() {

    jQuery("'.$selector_url.'").keyup(function() {
        updateCustomUrlPreview();
    });

    jQuery("form#yrewrite-url .url-type-selector").change(function() {
        $("form#yrewrite-url .yrewrite_url-wrapper").addClass("hidden");
        $("form#yrewrite-url .yrewrite_url-wrapper."+ $(this).val()).removeClass("hidden");
    });

    updateCustomUrlPreview();

});

function updateCustomUrlPreview() {
    var base = "'.('default' == $domain->getName() ? '&lt;default&gt;/' : $domain->getUrl()).'";
    var autoUrl = "'.$autoUrl.'";
    var customUrl = jQuery("'.$selector_url.'").val();
    var curUrl = "";

    if (customUrl !== "") {
        curUrl = base + customUrl;

    } else {
        curUrl = base + autoUrl;

    }

    jQuery("'.$selector_preview.'").html(curUrl);
}

</script>';

}

$form = ob_get_contents();
$content = '<section id="rex-page-sidebar-yrewrite-url" data-pjax-container="#rex-page-sidebar-yrewrite-url" data-pjax-no-history="1">'.$form.'</section>';
ob_end_clean();

return $content;
