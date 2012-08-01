<?php

include('../../../config.php');
include('Banners.class.php');

require_login(); 
if (!has_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM))) {
    error("Only the administrator can access this page!", $CFG->wwwroot);
}
$audience = optional_param('audience', 1, PARAM_INT);
$audience_name = ($audience == 1) ? 'Staff' : 'Student';

$PAGE->set_context(get_system_context());
$PAGE->set_pagelayout('admin');
$PAGE->set_title($audience_name . " Banners");
$PAGE->set_heading($audience_name . " Banners");
$PAGE->set_url($CFG->wwwroot.'/theme/conel/banners/index.php');
$PAGE->requires->css('/theme/conel/banners/styles/banners.css', true);
$PAGE->requires->css('/lib/jquery/colorbox/colorbox.css', true);
$PAGE->requires->js('/lib/jquery/jquery-1.7.2.min.js', true);

// body
echo $OUTPUT->header();
$banners = new Banners($audience);
$other_link = $banners->getOtherLink();
?>

<div id="holder">
	<a href="#" class="add_banner"><span class="add">Add Banner</span></a>
    <p><strong>Banner size:</strong> 495 x 185 pixels<br />
<?php
    $all_banners = $banners->getBanners();
    $audience_path = $banners->getAudiencePath($audience);
    $num_banners = count($all_banners);

    if ($num_banners == 0) {
        echo '<p>No '.$audience_path.' banners have been added yet.</p>';
    }
?>
<div id="banners_holder">
<?php
	if ($num_banners > 0) {
		$c = 1;

		foreach ($all_banners as $banner) {
			
			$active = $banner['active'];
			$active_class = ($banner['active'] == 0) ? ' inactive' : '';
            $id = $banner['id'];
			echo '	
			<!-- banner '.$c.' -->
			<div class="banner'.$active_class.'">
			<div class="position">
				<div class="moveup">';
			if ($c > 1 && $active == 1) {
				echo '<a href="banner_actions.php?action=moveup&amp;pos='.$c.'&amp;audience='.$audience.'" title="Move Up"><img src="img/icon-moveup.png" /></a>';
			}
			echo '</div>
				<div class="count">'.$c.'</div>
				<div class="movedown">';
			if ($c != $num_banners && $active == 1) {
				echo '<a href="banner_actions.php?action=movedown&amp;pos='.$c.'&amp;audience='.$audience.'" title="Move Down"><img src="img/icon-movedown.png" /></a>';
			}
			echo '</div>
			</div>
				<div class="banner_details">
					<img src="'.$banner['img_url'].'" height="155" width="425" alt="" /><br />
					<div class="actions">
						<a href="banner_edit.php?id='.$id.'&amp;audience='.$audience.'" class="edit"><span class="'.$c.'">Edit</span></a>
						<a href="banner_actions.php?action=delete&amp;id='.$id.'&amp;audience='.$audience.'" class="delete"><span class="'.$c.'">Delete</span></a>';	
					if ($active) {
						echo '<a href="banner_actions.php?action=disable&amp;id='.$id.'&amp;audience='.$audience.'" class="disable"><span class="'.$c.'">Disable</span></a>';
					} else {
						echo '<a href="banner_actions.php?action=enable&amp;id='.$id.'&amp;audience='.$audience.'" class="enable"><span class="'.$c.'">Enable</span></a>';
					}
					// Reduce size of banner link if over a certain amount of characters
					$banner_link_title = $banner['link'];
					$max_chars = 25;
					if (strlen($banner_link_title) > $max_chars) {
						$banner_link_title = substr($banner_link_title, 0, $max_chars) . '...';
					}
					echo '</div>
					<div class="link">
						<strong>Link:</strong> <a href="'.$banner['link'].'" target="_blank" title="'.$banner['link'].'">'.$banner_link_title.'</a>
					</div>
					<br class="clear_both" />
				</div>
			</div>
			<!-- // banner '.$c.' -->';

			$c++;
		}
	}
?>
</div>

<!-- add banner -->
<div id="banner_add_form">
<h3>Add Banner</h3>
	<form enctype="multipart/form-data" action="banner_actions.php" method="POST">
		<table>
			<tr>
				<td><label for="upload_link">Link:</label></td>
				<td><input type="text" name="banner_link" class="field" id="upload_link" /></td>
			</tr>
			<tr>
				<td valign="top"><label for="upload_banner">Banner:</label></td>
				<td>
					<input type="file" name="banner_img" id="upload_banner" />
					<input type="hidden" name="MAX_FILE_SIZE" value="500000" />
					<input type="hidden" name="action" value="upload" />
					<input type="hidden" name="audience" value="<?php echo $audience; ?>" />
                    <p class="note">Banner size: 495 pixels wide, 185 pixels height</p>
				</td>
			</tr>
			<tr>
				<td colspan="2">
				<input type="hidden" name="position" value="0" />
					<br /><input type="submit" value="Add Banner" />
				</td>
			</tr>
		</table>
	</form>
</div>
<!-- //add banner -->

<p><strong><?php echo $other_link; ?></strong></p>
</div>
<script type="text/javascript" src="/lib/jquery/colorbox/jquery.colorbox-min.js"></script>
<script type="text/javascript" src="/theme/conel/banners/js/banners-admin.js"></script>
<?php
echo $OUTPUT->footer();
?>
