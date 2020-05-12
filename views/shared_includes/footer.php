<?php defined('ALTUMCODE') || die() ?>

<footer class="container">
    <div class="d-flex justify-content-between sticky-footer">
        <div class="footer__img">
            <?php if($settings->logo != ''): ?>
                <img src="<?= $settings->url . UPLOADS_ROUTE . 'logo/logobmotion.jpg' ?>" style="height: 7rem;" class="img-fluid" alt="<?= $language->global->accessibility->logo_alt ?>" />
            <?php else: ?>
                <?= $settings->title ?>
            <?php endif ?>
        </div>
        <div class="col-md-9 px-0 footer__pdf">

            <div>
                <span><?= 'Copyright &copy; ' . date('Y') . ' ' . $settings->title . '. All rights reserved. Product by <a href="#">Bmotion</a>' ?></span>
            </div>

            <?php if(count($languages) > 1): ?>
            <span class="dropdown d-print-none">
                <a class="dropdown-toggle clickable" id="languageDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <?= $language->global->language ?>
                </a>
                <div class="dropdown-menu" aria-labelledby="languageDropdown">
                    <h6 class="dropdown-header"><?= $language->global->choose_language ?></h6>
                    <?php
                    foreach($languages as $language_name) {
                        echo '<a class="dropdown-item" href="index.php?language=' . $language_name . '">' . $language_name . '</a>';
                    }
                    ?>
                </div>
            </span>
            <?php endif ?>

            <?php
            $bottom_menu_result = $database->query("SELECT `url`, `title` FROM `pages` WHERE `position` = '0'");

            while($bottom_menu = $bottom_menu_result->fetch_object()):

                $link_internal = true;
                if(strpos($bottom_menu->url, 'http://') !== false || strpos($bottom_menu->url, 'https://') !== false) {
                    $link_url = $bottom_menu->url;
                    $link_internal = false;
                } else {
                    $link_url = $settings->url . 'page/' . $bottom_menu->url;
                }

                ?>
                <a href="<?= $link_url ?>" <?= $link_internal ? null : 'target="_blank"' ?> class="mr-3"><?= $bottom_menu->title ?></a>
            <?php endwhile ?>

        </div>

        <div class="col-auto px-0">
            <p class="mt-3 mt-md-0">
                <?php
                if(!empty($settings->facebook))
                    echo '<span class="fa-stack mx-1"><a href="https://facebook.com/' . $settings->facebook . '" class="icon-facebook" rel="nofollow" data-toggle="tooltip" title="' . $language->global->footer->facebook . '"><i class="fab fa-fw fa-facebook"></i></a></span>';

                if(!empty($settings->twitter))
                    echo '<span class="fa-stack mx-1"><a href="https://twitter.com/' . $settings->twitter . '" class="icon-twitter" rel="nofollow" data-toggle="tooltip" title="' . $language->global->footer->twitter . '"><i class="fab fa-fw fa-twitter"></i></a></span>';

                if(!empty($settings->instagram))
                    echo '<span class="fa-stack mx-1"><a href="https://instagram.com/' . $settings->instagram . '" class="icon-instagram" rel="nofollow" data-toggle="tooltip" title="' . $language->global->footer->instagram . '"><i class="fab fa-fw fa-instagram"></i></a></span>';

                if(!empty($settings->youtube))
                    echo '<span class="fa-stack mx-1"><a href="https://youtube.com/' . $settings->youtube . '" class="icon-youtube" rel="nofollow" data-toggle="tooltip" title="' . $language->global->footer->youtube . '"><i class="fab fa-fw fa-youtube"></i></a></span>';
                ?>
            </p>
        </div>

    </div>
</footer>
