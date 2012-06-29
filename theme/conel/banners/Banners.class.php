<?php
/**
 * Banner Changer
 *
 * Banners used for advertising news to Conel Staff and Students. 
 * Added to the conel theme for use on the frontpage and 'my moodle'. 
 *
 * Uses two jQuery plugins for display and editing: 
 *     1. http://codecanyon.net/item/jquery-banner-rotator-slideshow/109046 
 *     2. http://www.jacklmoore.com/colorbox
 *
 * @package    theme
 * @copyright  2012 Nathan Kowald
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class Banners {

    /** @var string The table name banner details are stored in */
    private $banners_table; 
    /** @var array Valid mime types */
    private $valid_mimes;
    /** @var array Valid image extensions */
    private $valid_exts;

    /** @var array Holds any errors encountered. Displayed during __destruct() */
    public $errors;
    /** @var string 1 = Staff, 2 = Student. The two categories of banner. */
    public $audience;
    /** @var string The server path to the banner. Used when adding and deleting banners */
    public $server_path;


    /**
     * Sets the banners table, audience, server_path, valid mime types and valid image extensions
     *
     * @param int   $audience Sets audience. Either Staff or Student.
     */
    public function __construct($audience=1) {
        $this->errors = array();
        $this->banners_table = 'conel_banners';
        if ($audience == 1 || $audience == 2) {
            $this->audience = $audience;
        }
        $this->server_path = $this->getServerPath();
        $this->valid_mimes = array('image/jpeg', 'image/png', 'image/gif', 'image/pjpeg');
        $this->valid_exts = array('jpg', 'jpeg', 'png', 'gif');
    }

    /**
     * Gets the path name of the banners for the given audience type.
     *
     * @param int $audience Sets audience type (Staff=1 or Student=2).
     * @return string $path Returns the path name.
     */
    public function getAudiencePath($audience=1) {
        $path = ($audience == 1) ? 'staff' : 'student';
        return $path;
    }

    /**
     * Gets the webpath for the given banner type. Used in image src="".
     * e.g. http://moodle/theme/conel/banners/staff/
     *
     * @return string $http_path Returns the HTTP path (image URL).
     */
    private function getHTTPPath() {
        global $CFG;
        $audience_path = $this->getAudiencePath($this->audience);
        $http_path = $CFG->wwwroot . '/theme/conel/banners/' . $audience_path . '/';
        return $http_path;
    }

    /**
     * Gets the server path for the given banner type.
     * e.g. D:\moodle\theme\conel\banners\staff\
     * This path is used when working with the filesystem. Uploads and deletes.
     *
     * @return string Returns the server path
     */
    private function getServerPath() {
        $audience_name = $this->getAudiencePath($this->audience);
        $path = pathinfo(getcwd());
        return $path['dirname'] . '\\' . $path['basename'] . '\\' . $audience_name . '\\' ;
    }

    // TODO - If table not found: create it.
    private function createBannersTable() {
        /*
        global $CFG;
        $query = "sprintf("CREATE TABLE %s (
            id INT NOT NULL AUTO_INCREMENT,
            PRIMARY KEY(id),
            position SMALLINT(3) unsigned NOT NULL,
            link VARCHAR(170),
            img_url VARCHAR(150) NOT NULL,
            active TINYINT(1) unsigned NOT NULL,
            audience SMALLINT(2) unsigned NOT NULL,
            date_created BIGINT(15) unsigned,
            date_modified BIGINT(15) unsigned
        )", 
            $CFG->prefix . $this->banners_table 
        );
        */
    } 

    /**
     * Queries the banners table seeing if any banners have been added for the current audience type.
     *
     * @return boolean True or False.
     */
    public function bannersExist() {
        global $DB;
        return $DB->record_exists($this->banners_table, array('audience'=>$this->audience));
    }

    /**
     * Gets banner details for the current audience type.
     * Used on the frontpage and admin page.
     *
     * @return array $banners Array of banner details for the current audience type.
     */
    public function getBanners() {
        global $DB;
        $fpbanners = $DB->get_records($this->banners_table, array('audience'=>$this->audience), 'position ASC', '*');
        $http_path = $this->getHTTPPath();
        $banners = array();
        $c = 0;
        foreach($fpbanners as $ban) {
            $banners[$c]['id'] = $ban->id;
            $banners[$c]['position'] = $ban->position;
            $banners[$c]['link'] = $ban->link;
            $banners[$c]['img_url']	= $http_path . $ban->img_url;
            $banners[$c]['active'] = $ban->active;
            $c++;
        }
        return $banners;
    }

    /**
     * Updates the banner order for the current audience type.
     * This should be run after every 'upload', 'delete', 'enable', 'disable'.
     *
     * @return boolean True if banner order updated successfully. Otherwise false.
     */
    private function updateOrder() {
        global $DB;

        $results = $DB->get_records($this->banners_table, array('audience'=>$this->audience), 'position ASC', '*'); 
        if (count($results) == 0) {
            // No banners exist.
            return true;
        }
        $pos = 1;
        foreach ($results as $res) {
            if (!$DB->set_field($this->banners_table, 'position', $pos, array('id'=>$res->id))) {
                $this->errors[] = 'Banner order update failed!';
                return false;
            }
            $pos++;
        }
        return true;
    }

    /**
     * Moves a banner up or down based on the given positions.
     *
     * @param string $current_pos The current position of the banner.
     * @param string $new_pos The position the banner will be moved to.
     * @return boolean True if move successful. Otherwise false.
     */
    private function move($current_pos='', $new_pos='') {
        global $DB;
        global $CFG;

        if ($current_pos == '' && $new_pos == '') {
            $this->errors[] = 'Empty move positions';
            return false;
        }

        // Get id numbers of banners which need to be swapped.
        $order = ($new_pos > $current_pos) ? 'ASC' : 'DESC';
        $query = sprintf(
            "SELECT * FROM %s WHERE position IN (%d, %d) AND audience = %1d ORDER BY position %s",
            $CFG->prefix . $this->banners_table, 
            $new_pos,
            $current_pos,
            $this->audience,
            $order
        );
        if (!$results = $DB->get_records_sql($query)) {
            $this->errors[] = 'Banner positions don\'t exist';
            return false;    
        }

        $i = 0;
        foreach ($results as $res) {
            if ($i == 0) {
                $res->position = $new_pos;
                $res->date_modified = time();
                $DB->update_record($this->banners_table, $res, true);
            } else if ($i == 1) {
                $res->position = $current_pos;
                $res->date_modified = time();
                $DB->update_record($this->banners_table, $res, false);
            }
            $i++;
        }

        return true;
    }

    /**
     * Cleans up the name of an image file. 
     * Best practises suggest spaces be replaced with hyphens.
     *
     * @param string $filename The filename of the uploaded image.
     * @return string Returns a cleaned filename
     */
    private function cleanFilename($filename) {
        return str_replace(' ', '-', $filename);
    }

    /**
     * Uploads a banner image to the server.
     *
     * @param Array $files $_FILES array comes from the posted form. Required.
     * @return mixed I know you should only really return one type.
     *               Returns false if an error occurs OR a cleaned filename if successfully uploaded.
     */
    private function uploadFile(Array $files) {
        // Work out if new banner img or updating banner image.
        $name = (isset($files['banner_img'])) ? 'banner_img' : 'new_banner_img';

        // Check we have a file.
        if($files[$name]['error'] != 0) {
            $this->errors[] = "No file uploaded";
            return false;
        }

        // Check file is JPEG or GIF and its size is less than 500Kb.
        $filename = $this->cleanFilename(basename($files[$name]['name']));
        $ext = substr($filename, strrpos($filename, '.') + 1);

        if ((!in_array($ext, $this->valid_exts)) || (!in_array($files[$name]["type"], $this->valid_mimes)) || ($files[$name]["size"] > 500000)) {
            $this->errors[] =  "Only .jpg, .jpeg, .png, .gif images under 500Kb are accepted for upload";
            return false;
        }

        // Determine the path to save this file to.
        $fullpath = $this->server_path . $filename;

        // Check if the file with the same name is already exists on the server.
        $original_name = substr($filename, 0, (strpos($filename, $ext) - 1));
        $i = 1;
        while(file_exists($fullpath)) {
            $filename = $original_name . '_' . $i . '.' . $ext;
            $fullpath = $this->server_path . $filename;
            $i++;
        }
        // Attempt to move the uploaded file to it's new place.
        if (!move_uploaded_file($files[$name]['tmp_name'], $fullpath)) {
           $this->errors[] = "A problem occurred during file upload!";
           return false;
        }

        return $filename;
    }

    /**
     * Uploads a banner image to the server and saves the details to the banners table.
     *
     * @param Array $files $_FILES array comes from the posted form. Required.
     * @return boolean False if an error occurs. Otherwise True.
     */
    public function upload(Array $files) {
        global $DB;

        $filename = $this->uploadFile($files);
        // Banner successfully updated!
            
        // Only validate URL if banner link given.
        if ($_POST['banner_link'] != '') {
            if (filter_var($_POST['banner_link'], FILTER_VALIDATE_URL)) {
                $banner_link = filter_var($_POST['banner_link'], FILTER_VALIDATE_URL);
            } else {
                $this->errors[] = "Invalid URL";
                return false;
            }
        }
        if (is_numeric($_POST['position'])) {
            $position = $_POST['position'];
        } else {
            $this->errors[] = "Position must be a number";
            return false;
        }

        $record = new stdClass();
        $record->position = $position;
        $record->link = $banner_link;
        $record->img_url = $filename;
        $record->active = 1;
        $record->audience = $this->audience;
        $record->date_created = time();

        $DB->insert_record($this->banners_table, $record, false);
        $this->updateOrder();
        return true;
    }

    /**
     * A worker method. Determines banner positions then gets move() to do the moving.
     *
     * @param string $banner_pos The current position of the banner you want to move.
     * @return boolean False if an error occurs. Otherwise True.
     */
    public function moveUp($banner_pos = '') {
        if ($banner_pos == '' || !is_numeric($banner_pos)) {
            $this->errors[] = 'Invalid or blank banner position given';
            return false;
        }
        $result = $this->move($banner_pos, ($banner_pos - 1));
        if ($result === false) {
            $this->errors[] = 'Error moving banner up';
            return false;
        }
        return true;
    }

    /**
     * A worker method. Determines banner positions then gets move() to do the moving.
     *
     * @param string $banner_pos The current position of the banner you want to move.
     * @return boolean False if an error occurs. Otherwise True.
     */
    public function moveDown($banner_pos = '') {
        if ($banner_pos == '' || !is_numeric($banner_pos)) {
            $this->errors[] = 'Invalid or blank banner position given';
            return false;
        }
        $result = $this->move($banner_pos, ($banner_pos + 1));
        if ($result === false) {
            $this->errors[] = 'Error moving banner down';
            return false;
        }
        return true;
    }

    /**
     * Deletes a banner.
     *
     * @param string $id ID of the banner to delete.
     * @return boolean False if an error occurs. Otherwise True.
     */
    public function delete($id='') {
        if ($id == '' || !is_numeric($id)) {
            $this->errors[] = 'No banner ID provided';
            return false;
        }

        global $DB;

        // Get image filename and idnumber of banner to delete.
        $banner_id = '';
        $img_url = '';
        if ($found = $DB->get_record($this->banners_table, array('id'=>$id), '*')) {
            $banner_id = $found->id;	
            $img_url = $found->img_url;	
        } else {
            $this->errors[] = 'Banner not found';
            return false;
        }

        // Delete the banner from the table.
        $result = $DB->delete_records($this->banners_table, array('id' => $banner_id));
        if ($result === false) {
            $this->errors[] = "Could not delete banner: $banner_id";
            return false;
        } else {
            // Delete the file from banners directory - to save space and prevent 'duplicate' image errors.
            $filepath = $this->server_path . $img_url;
            // Check if the file exists on the server: if so DELETE it.
            if (file_exists($filepath) && unlink($filepath)) {
                // Update order of banners.
                if ($this->updateOrder() === false) {
                    $this->errors[] = 'Could not update banner order!';
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Disables a banner. Disabled banners aren't shown.
     * Banner position is moved to the end. In the admin page the disabled banner is greyed out.
     *
     * @param string $id ID of the banner to disable.
     * @return boolean False if an error occurs. Otherwise True.
     */
    public function disable($id='') {
        if ($id == '' || !is_numeric($id)) {
            $this->errors[] = 'No banner ID provided';
            return false;
        }
        global $DB;

        $record = $DB->get_record($this->banners_table, array('id'=>$id), '*');
        $record->date_modified = time();
        $record->active = 0;
        $record->position = 100;

        $result = $DB->update_record($this->banners_table, $record, false);
        if ($result !== true) {
            $this->errors[] = "Could not disable banner $id";
            return false;
        }
        if ($this->updateOrder() === false) {
            $this->errors[] = 'Could not update banner order!';
            return false;
        }
        // Successfully disabled and re-ordered.
        return true;
    }

    /**
     * Enables a banner.
     *
     * @param string $id ID of the banner to enable.
     * @return boolean False if an error occurs. Otherwise True.
     */
    public function enable($id='') {
        if ($id == '' || !is_numeric($id)) {
            $this->errors[] = 'No banner ID provided';
            return false;
        }

        global $DB;

        $record = $DB->get_record($this->banners_table, array('id'=>$id), '*');
        $record->active = 1;
        $record->position = 100;
        $record->date_modified = time();

        $result = $DB->update_record($this->banners_table, $record, false);
        if ($result === true) {
            if ($this->updateOrder() === false) {
                $this->errors[] = 'Could not update banner order!';
                return false;
            }
            // Successfully disabled and re-ordered.
        } else {
            $this->errors[] = "Could not enable banner $id";
            return false;
        }
        return true;
    }

    /**
     * Updates an existing banner.
     * Either updates just link and date_modified or the image too if given.
     *
     * @param string $id ID of the banner to update.
     * @param string $link The updated link.
     * @param Array $files The $_FILES array. Used if updating the banner image.
     * @return boolean False if an error occurs. Otherwise True.
     */
    public function update($id='', $link='', Array $files) {

        // Validate ids.
        if ($id == '' || !is_numeric($id)) {
            $this->errors[] = 'Invalid ID: ' . $id;
            return false;
        }
        // Validate non-empty links.
        if ($link != '' && !filter_var($link, FILTER_VALIDATE_URL)) {
            $this->errors[] = "Invalid URL: $link";
            return false;
        }

        global $DB;

        $new_banner = ((!empty($files["new_banner_img"])) && ($files['new_banner_img']['error'] == 0)) ? $files['new_banner_img'] : '';

        // No new banner was added. Update link only.
        if ($new_banner == '') {
            $banner_found = $DB->get_record($this->banners_table, array('id' => $id));

            // Update link and date modified.
            $banner_found->date_modified = time();
            $banner_found->link = $link;
            $result = $DB->update_record($this->banners_table, $banner_found, false);

            if ($result === true) {
                // Woo hoo! everything works.
                return true;
            } else {
                $this->errors[] = 'Could not update banner';
                return false;
            }
        } else {
            // If updating banner, delete old banner and then upload new banner.
            $old_banner = $DB->get_record($this->banners_table, array('id' => $id));
            if ($old_banner === false) {
                $this->errors[] = 'Banner not found';
                return false;
            }
            $oldname = $old_banner->img_url;
            // Now we have image url: delete it!.
            $filepath = $this->server_path . $oldname;
            // Check that a file with the same name doesn't already exist on the server.
            if (file_exists($filepath) && !unlink($filepath)) {
                $this->errors[] = "Problem deleting $filename";
                return false;
            }
            // Deleted successfully, upload new banner.
            $filename = $this->uploadFile($files);

            // Update the database record with new details.
            $old_banner->link = $link;
            $old_banner->img_url = $filename;
            $old_banner->date_modified = time();
            $result = $DB->update_record($this->banners_table, $old_banner, false);
            if ($result === true) {
                // Woo hoo! everything works : redirect to home.
                return true;
            } else {
                $this->errors[] = 'Banner update failed!';
                return false;
            }
        }
    }

    /**
     * Gets the link to the 'other' audience type. If staff, returns a link to student banners etc.
     * Used on the admin page.
     *
     * @return string Link to the 'other' audience type.
     */
    public function getOtherLink() {
        if ($this->audience == 1) {
            $link_name = $this->getAudiencePath(2);
            $link = '<a href="index.php?audience=2">Change '.ucfirst($link_name).' banners</a>';
        } else {
            $link_name = $this->getAudiencePath(1);
            $link = '<a href="index.php?audience=1">Change '.ucfirst($link_name).' banners</a>';
        }
        return $link;
    }

    /**
     * If errors have been set by any of the methods, it displays them.
     * Once displayed it resets the errors array.
     * __destruct() is called when no other references to Banners exist or during shutdown.
     *
     * TODO use Moodle 2's built in error methods for outputting errors.
     */
    public function __destruct() {
        if (count($this->errors) > 0) {
            echo '<div style="color:red;">';
            echo "<h2>Errors</h2>";
            echo '<ul>';
            foreach($this->errors as $error) {
                echo "<li>$error</li>";
            }
            echo '</ul>';
            echo '</div>';
            echo '<p><a href="index.php?audience='.$this->audience.'">back to banners</a>';
        }

        $this->errors[] = array();
    }
    
}
