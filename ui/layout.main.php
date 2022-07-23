
        <div class="page-container">
            <div class="header">
                <a class="btn-link toggle-sidebar hidden-lg-up pg pg-menu cursor-pointer m-r-15" data-toggle="sidebar" style="font-size: 1.2em"></a>
                <?php $banner_text_class = 'text-black'; $app_logo = 'app_logo_1.png'; $brand_id = 'banner1'; require INDEX . "ui/layout.banner.php"; ?>
                <div class="d-flex align-items-center margin-right-neg-15">
                    <div class="pull-left text-black cursor-pointer collapse-768" align="center">
                        <span id="spAdminUserTitle" data-toggle='tooltip' data-placement='left' data-original-title='<?php echo $arr_admin_details['user_type_title']; ?>'>
                            <span id="spUserTitle" class="text-bold"><span class="badge badge-success"><?php echo "<span class='fa fa-user-circle'></span> " . $arr_admin_details['user_type_acronym']; ?></span></span>
                        </span>
                        <span class="semi-bold text-black text-uppercase" id="spAdminFirstname"><?php echo $arr_admin_details['first_name']; ?></span>
                        <span class="semi-bold text-black text-uppercase" id="spAdminMiddleName" data-middlename="<?php echo $arr_admin_details['middle_name']; ?>"><?php if($arr_admin_details['middle_name'] != "") {echo strtoupper(substr($arr_admin_details['middle_name'], 0, 1)) . ". ";} ?></span>
                        <span class="semi-bold text-black text-uppercase" id="spAdminLastName"><?php echo $arr_admin_details['last_name']; ?></span>
                    </div>
                    <div class="dropdown pull-right">
                        <button class="profile-dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <span class="thumbnail-wrapper d32 circular inline" style="cursor: pointer"><?php $src = CITIZEN_AVATAR_URL . $arr_admin_details['avatar']; ret(1); ?>
                                <img id="imgProfile" src="<?php echo $src; ?>" data-src="<?php echo $src; ?>" data-src-retina="<?php echo $src; ?>" alt="[img]">
                            </span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right profile-dropdown" role="menu">
                            <a class="dropdown-item cursor-pointer" href="backup"><i class="fa fa-fw fa-download"></i> DATA BACKUP</a>
                            <a class="dropdown-item cursor-pointer no-margin" id="btnShowLogoutPrompt"><i class="fa fa-power-off"></i> LOGOUT</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="page-content-wrapper">
                <div class="content"></div>
            </div>
        </div>
