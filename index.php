<?php
// +------------------------------------------------------------------------+
// | @author Deen Doughouz (DoughouzForest)
// | @author_url 1: http://www.playtubescript.com
// | @author_url 2: http://codecanyon.net/user/doughouzforest
// | @author_email: wowondersocial@gmail.com
// +------------------------------------------------------------------------+
// | PlayTube - The Ultimate Video Sharing Platform
// | Copyright (c) 2017 PlayTube. All rights reserved.
// +------------------------------------------------------------------------+


require_once('./assets/init.php');


$page = 'home';
if (isset($_GET['link1'])) {
    $page = $_GET['link1'];
}
if ($page == 'finish') {
    $page = 'confirm';
}
if ($pt->config->pop_up_18 == 'on' && (!empty($_COOKIE['pop_up_18']) && $_COOKIE['pop_up_18'] == 'no' && $page != 'age_block' && !IS_LOGGED)) {
    header('Location: ' .PT_Link('age_block'));
    exit();
}
$pt->is_ajax_load = false;
$maintenance_mode = false;
if ( $pt->config->maintenance_mode == 'on' ) {
    if ( IS_LOGGED === false ) {
        $maintenance_mode = true;
        if (!empty($_COOKIE['maintenance_mode']) && $_COOKIE['maintenance_mode'] == 'no') {
            $maintenance_mode = false;
        }
        if(isset($_GET['access']) && $_GET['access'] == 'admin'){
            if (empty($_COOKIE['maintenance_mode'])) {
                setcookie("maintenance_mode", 'no', time() + (10 * 365 * 24 * 60 * 60), '/');
            }
            $maintenance_mode = false;
        }
    } else {
        if ($pt->user->admin == "0") {
            $maintenance_mode = true;
        }

    }

    if( $maintenance_mode === true ){
        $file_location = "./sources/maintenance/content.php";
        if (file_exists($file_location)) {
            require_once $file_location;
        }
    }
}

if (IS_LOGGED == true) {
    if ($user->last_active < (time() - 60)) {
        $update = $db->where('id', $user->id)->update('users', array(
            'last_active' => time()
        ));
    }
}

if (IS_LOGGED) {
    if ($pt->config->require_subcription == 'on' && !$pt->user->is_pro && !PT_IsAdmin() && $page == 'watch') {
        $page = 'go_pro';
    }
}
else{
    if ($pt->config->require_subcription == 'on' && $page == 'watch') {
        $page = 'home';
    }
}


if (!empty($_GET['v'])) {
    $video_short_id = PT_Secure($_GET['v']);
    $get_video = PT_GetVideoByID($video_short_id, 0, 0, 1, 1);
    if (!empty($get_video)) {
        header("Location: $get_video->url");
        exit();
    }
}
if (file_exists("./sources/$page/content.php")) {
    if (!empty($_GET)) {
        foreach ($_GET as $key => $value) {
            if (!is_array($value)) {
                $value = preg_replace('/on[^<>=]+=[^<>]*/m', '', $value);
                $_GET[$key] = strip_tags($value);
            }
            else{
                foreach ($value as $keyv => $valuev) {
                    $valuev = preg_replace('/on[^<>=]+=[^<>]*/m', '', $valuev);
                    $value[$keyv] = strip_tags($valuev);
                }
                $_GET[$key] = $value;
            }
        }
    }
    if (!empty($_POST)) {
        foreach ($_POST as $key => $value) {
            if (!is_array($value)) {
                $value = preg_replace('/on[^<>=]+=[^<>]*/m', '', $value);
                $_POST[$key] = strip_tags($value);
            }
            else{
                foreach ($value as $keyv => $valuev) {
                    $valuev = preg_replace('/on[^<>=]+=[^<>]*/m', '', $valuev);
                    $value[$keyv] = strip_tags($valuev);
                }
                $_POST[$key] = $value;
            }
        }
    }
    include("./sources/$page/content.php");
}

if (empty($pt->content)) {
    include("./sources/404/content.php");
}

$side_header = 'not-logged';

if (IS_LOGGED == true) {
    $side_header = 'loggedin';
}

$announcement_html = '';
$footer            = '';

if ($pt->page != 'login') {
    $langs__footer = $langs;
    $langs_html    = '';
    $langs_modal_html    = '';
    foreach ($langs__footer as $key => $language) {
        $lang_explode = explode('.', $language);
        $language     = $lang_explode[0];
        $language_    = ucfirst($language);
        $langs_html .= '<li><a href="?lang=' . $language . '" rel="nofollow">' . $language_ . '</a></li>';
        $langs_modal_html .=  '<li class="language_select"><a href="?lang=' . $language . '" rel="nofollow" class="' . $language . '">' . $language_ . '</a></li>';
    }
    $pt->langs = $langs_modal_html;
    $footer = PT_LoadPage('footer/content', array(
        'DATE' => date('Y'),
        'LANGS' => $langs_html
    ));
}

$og_meta = '';
if ($pt->page == 'watch') {
    $og_meta = PT_LoadPage('watch/og-meta', array(
        'TITLE' => $pt->title,
        'DESC' => mb_substr($pt->description, 0, 400, "UTF-8"),
        'THUMB' => str_replace('mqdefault', 'maxresdefault', $get_video->thumbnail),
        'URL' => PT_Link('watch/' . PT_Slug($get_video->title, $get_video->video_id))
    ));
}
if ($pt->page == 'read') {
    $og_meta = PT_LoadPage('watch/og-meta', array(
        'TITLE' => $pt->title,
        'DESC' => mb_substr($pt->description, 0, 400, "UTF-8"),
        'THUMB' => PT_GetMedia($article->image),
        'URL' => PT_Link('articles/read/' . PT_URLSlug($article->title,$article->id))
    ));
}


/* Get active Announcements */

if ($pt->page != 'timeline') {

    $announcement          = pt_get_announcments();
    if(!empty($announcement)) {
        $announcement_html =  PT_LoadPage("announcements/content",array(
            'ANN_ID'       => $announcement->id,
            'ANN_TEXT'     => PT_Decode($announcement->text),
        ));
    }
}
/* Get active Announcements */
if (!empty($user->id)) {

$pt->subscribers_ = $db->rawQuery("SELECT * FROM ".T_SUBSCRIPTIONS." WHERE subscriber_id = '".$user->id."' AND user_id NOT IN (".implode(',', $pt->blocked_array).") ORDER BY id DESC LIMIT 6");

}

if (!empty($pt->config->seo)) {
    $seo = json_decode($pt->config->seo,true);
    if (in_array($pt->page, array_keys($seo))) {
        $pt->title       = str_replace('{SITE_TITLE}', $pt->config->title, $seo[$page]['title']);
        $pt->title = preg_replace_callback("/{LANG_KEY (.*?)}/", function($m) use ($lang_array) {
            return (isset($lang_array[$m[1]])) ? $lang_array[$m[1]] : '';
        }, $pt->title);
        $pt->description = str_replace('{SITE_DESC}', $pt->config->description, $seo[$page]['meta_description']);
        $pt->keyword     = str_replace('{SITE_KEYWORDS}', $pt->config->keyword, $seo[$page]['meta_keywords']);
    }
}
$final_content = PT_LoadPage('container', array(
    'CONTAINER_TITLE' => $pt->title,
    'CONTAINER_DESC' => $pt->description,
    'CONTAINER_KEYWORDS' => $pt->keyword,
    'CONTAINER_CONTENT' => $pt->content,
    'ANNOUNCEMENT'     => $announcement_html,
    'IS_LOGGED' => (IS_LOGGED == true) ? 'data-logged="true"' : '',
    'MAIN_URL' => $pt->actual_link,

    'HEADER_LAYOUT' => PT_LoadPage('header/content', array(
        'SIDE_HEADER' => PT_LoadPage("header/$side_header"),
        'SEARCH_KEYWORD' => (!empty($_GET['keyword'])) ? PT_Secure($_GET['keyword']) : ''
    )),
    'FOOTER_LAYOUT' => $footer,
    'OG_METATAGS' => $og_meta,
    'EXTRA_JS' => PT_LoadPage('extra-js/content'),
    'MODE' => (($pt->mode == 'night') ? 'checked' : ''),

    'RIGHT_AD' => PT_GetAd('right_side'),
    'LEFT_AD' => PT_GetAd('left_side'),
    'FOOTER_AD' => ($pt->page != 'register' && $pt->page != 'login') ? PT_GetAd('footer') : '',
    'HEADER_AD' => PT_GetAd('header'),
));


echo $final_content;
$db->disconnect();
unset($pt);
?>
