<?php 

include($_SERVER['DOCUMENT_ROOT'] . '\theme\conel\banners\Banners.class.php');
$audience = 1; // staff
$banners = new Banners($audience); 
$banners_exist = $banners->bannersExist();
$banners_found = $banners->getBanners();
$audience_name = ucfirst($banners->getAudiencePath($audience));

$hassidepre = $PAGE->blocks->region_has_content('side-pre', $OUTPUT);
$hassidepost = $PAGE->blocks->region_has_content('side-post', $OUTPUT);
$showsidepre = $hassidepre && !$PAGE->blocks->region_completely_docked('side-pre', $OUTPUT);
$showsidepost = $hassidepost && !$PAGE->blocks->region_completely_docked('side-post', $OUTPUT);

$custommenu = $OUTPUT->custom_menu();
$hascustommenu = (empty($PAGE->layout_options['nocustommenu']) && !empty($custommenu));

$bodyclasses = array();
if ($showsidepre && !$showsidepost) {
    $bodyclasses[] = 'side-pre-only';
} else if ($showsidepost && !$showsidepre) {
    $bodyclasses[] = 'side-post-only';
} else if (!$showsidepost && !$showsidepre) {
    $bodyclasses[] = 'content-only';
}
if ($hascustommenu) {
    $bodyclasses[] = 'has_custom_menu';
}

echo $OUTPUT->doctype() ?>
<html <?php echo $OUTPUT->htmlattributes() ?>>
<head>
    <title><?php echo $PAGE->title ?></title>
    <link rel="shortcut icon" href="<?php echo $OUTPUT->pix_url('favicon', 'theme')?>" />
    <meta name="description" content="<?php p(strip_tags(format_text($SITE->summary, FORMAT_HTML))) ?>" />
    <!-- Banners -->
    <link rel="stylesheet" type="text/css" href="/lib/jquery/rotator/wt-rotator.css"/>
    <script type="text/javascript" src="/lib/jquery/jquery-1.7.2.min.js"></script>
    <script type="text/javascript" src="/lib/jquery/jquery.easing.1.3.min.js"></script>
    <script type="text/javascript" src="/lib/jquery/rotator/js/jquery.wt-rotator.min.js"></script>
    <script type="text/javascript" src="/theme/conel/banners/js/config.js"></script>
    <!-- //Banners -->
    <?php echo $OUTPUT->standard_head_html() ?>
</head>
<body id="<?php p($PAGE->bodyid) ?>" class="<?php p($PAGE->bodyclasses.' '.join(' ', $bodyclasses)) ?>">
<?php echo $OUTPUT->standard_top_of_body_html() ?>

<div id="page">

    <div id="page-header" class="clearfix">
        <h1 class="headermain"><?php echo $PAGE->heading ?></h1>
		<div id="header-home" class="clearfix">
            <?php $banner_img = $OUTPUT->pix_url('banners/banner'. rand(1,4), 'theme'); ?>
            <div id="header_holder">
                <a href="/"><img src="<?php echo $banner_img; ?>" alt="E-Zone" width="350" height="80" /></a>
            </div>
        </div>
        <div class="headermenu"><?php
            echo $OUTPUT->login_info();
            echo $OUTPUT->lang_menu();
            echo $PAGE->headingmenu;
        ?></div>
        <?php if ($hascustommenu) { ?>
        <div id="custommenu"><?php echo $custommenu; ?></div>
         <?php } ?>
    </div>
<!-- END OF HEADER -->

    <div id="page-content">
        <div id="region-main-box">
            <div id="region-post-box">

                <div id="region-main-wrap">
                    <div id="region-main">
                        <div class="region-content">

                        <h2>News</h2>
                        <?php if ($banners_exist === true) { ?>
                        <div class="container">
                            <div class="wt-rotator">
                            <div class="screen"><noscript><img src="<?php echo $banners_found[0]['img_url']; ?>" alt="" /></noscript></div>
                            <div class="c-panel">
                                <div class="buttons"><div class="prev-btn"></div><div class="play-btn"></div><div class="next-btn"></div></div>
                                <div class="thumbnails">
                                    <ul>
                                        <?php foreach ($banners_found as $ban) {
                                            echo '<li><a href="'.$ban['img_url'].'"><img src="'.$ban['img_url'].'" alt="Banner" width="495" height="185" /></a><a href="'.$ban['link'].'"></a></li>' . PHP_EOL;
                                        } ?>
                                    </ul>
                                </div>     
                              </div><!-- // c-panel -->
                            </div><!-- // wt-rotator -->
                        </div><!-- // container -->
                        
                        <?php 
                        } else {
                            echo '<p>No banners have been added yet.</p>';
                        }
                        if (has_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM))) {
                            echo '<p style="text-align:right;"><a href="/theme/conel/banners/index.php?role=1">Edit '.$audience_name.' Banners</a></p>';
                        }
                        ?>

                        <h2>Get Help</h2>
                        <ul id="get_help">
                            <li class="gh1"><a href="">Anti-bullying</a></li>
                            <li class="gh2"><a href="">Careers</a></li>
                            <li class="gh3"><a href="">E-Learning<br />&amp; ICT Support</a></li>
                            <li class="gh4"><a href="">E-safety</a></li>
                            <li class="gh5"><a href="">Learner Guidance<br />&amp; Policies</a></li>
                            <li class="gh6"><a href="">Learner Support</a></li>
                            <li class="gh7"><a href="">Mentoring</a></li>
                            <li class="gh8"><a href="">Recruit Direct</a></li>
                            <li class="gh9"><a href="">Safeguarding</a></li>
                            <li class="gh10"><a href="" target="_blank">Student Help Reporting System</a></li>
                            <li class="gh11"><a href="">Student Success Stories</a></li>
                            <li class="gh12"><a href="">Welfare</a></li>
                        </ul>
                        <br class="clear_both" />

                            <?php echo $OUTPUT->main_content() ?>
                        </div>
                    </div>
                </div>

                <?php if ($hassidepre) { ?>
                <div id="region-pre" class="block-region">
                    <div class="region-content">
                        <?php echo $OUTPUT->blocks_for_region('side-pre') ?>
                    </div>
                </div>
                <?php } ?>

                <?php if ($hassidepost) { ?>
                <div id="region-post" class="block-region">
                    <div class="region-content">
                        <?php echo $OUTPUT->blocks_for_region('side-post') ?>
                    </div>
                </div>
                <?php } ?>

            </div>
        </div>
    </div>

<!-- START OF FOOTER -->
    <div id="page-footer">
        <p class="helplink">
        <?php echo page_doc_link(get_string('moodledocslink')) ?>
        </p>

        <?php
        echo $OUTPUT->login_info();
        echo $OUTPUT->home_link();
        echo $OUTPUT->standard_footer_html();
        ?>
    </div>
</div>
<?php echo $OUTPUT->standard_end_of_body_html() ?>
</body>
</html>
