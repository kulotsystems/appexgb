
                <div class="brand inline no-selection" id="<?php echo $brand_id; ?>">
                    <table class="cursor-pointer toggle-sidebar" data-toggle="">
                        <tr>
                            <td align="center"><?php $src = HOST_ROOT . "ui/pages/assets/img/" . $app_logo; ret(1); ?>
                                <div class="brand-logo">
                                    <img src="<?php echo $src; ?>" data-src="<?php echo $src; ?>" data-src-retina="<?php echo $src; ?>" alt="logo">
                                </div>
                            </td>
                            <td align="left">
                                <ul class="ul-condensed <?php echo $banner_text_class; ?>">
                                    <li class="brand-name" style="font-family: serif; text-shadow: 1px 1px #bbb"><span style="color: red"><?php echo APP_NAME; ?> <span style="color: blue"><?php echo APP_NAME_2; ?></span></span></li>
                                    <li class="brand-app-title-1"><?php echo APP_TITLE_1; ?></li>
                                    <li class="brand-app-title-2"><?php echo APP_TITLE_2; ?></li>
                                </ul>
                            </td>
                        </tr>
                    </table>
                </div>

