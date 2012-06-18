<?php
class Banners {

    private $banners_table; 
    private $valid_mimes;
    private $valid_exts;
    private $banner_folder;
    public $errors;
    public $audience;
    public $banner_path;

    public function __construct($audience=1) {
        $this->errors = array();
        $this->banners_table = 'conel_banners';
        if ($audience == 1 || $audience == 2) {
            $this->audience = $audience;
        }
        $this->banner_path = $this->getBannerFolder();
        $this->valid_mimes = array('image/jpeg', 'image/png', 'image/gif', 'image/pjpeg');
        $this->valid_exts = array('jpg', 'jpeg', 'png', 'gif');
    }

    public function getAudiencePath($audience='') {
        $path = ($this->audience == 1) ? 'staff' : 'student';
        return $path;
    }

    private function getBannerFolder() {
        $audience_name = $this->getAudiencePath($this->audience);
        $path = pathinfo(getcwd());
        return $path['dirname'] . '\\' . $path['basename'] . '\\' . $audience_name . '\\' ;
    }

    private function createBannersTable() {
        /*
        CREATE TABLE mdl_conel_banners (
            id INT NOT NULL AUTO_INCREMENT,
            PRIMARY KEY(id),
            position SMALLINT(3) unsigned NOT NULL,
            link VARCHAR(170) NOT NULL,
            img_url VARCHAR(150) NOT NULL,
            active TINYINT(1) unsigned NOT NULL,
            audience SMALLINT(3) unsigned NOT NULL,
            date_created BIGINT(15) unsigned,
            date_modified BIGINT(15) unsigned
        );
        */
    } 

    public function bannersExist() {
        return true;
    }

    public function getBanners() {

        global $DB;

        $banners = array();
        $active_banners = 0;
        $fpbanners = $DB->get_records($this->banners_table, array('audience'=>$this->audience), 'position ASC', '*');
        $c = 0;
        foreach($fpbanners as $ban) {
            $banners[$c]['id'] = $ban->id;
            $banners[$c]['position'] = $ban->position;
            $banners[$c]['link'] = $ban->link;
            $banners[$c]['img_url']	= $ban->img_url;
            $banners[$c]['active'] = $ban->active;
            $position = $ban->position;
            $c++;
            if ($ban->active) $active_banners++;
        }
        return $banners;
    }

    // This should be run after every 'delete'.
    public function updateOrder() {
        global $DB;

        $results = $DB->get_records($this->banners_table, array('audience'=>$this->audience), 'position ASC', '*'); 
        if (count($results) == 0) {
            // No banners exist: return true
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

    public function move($current_pos='', $new_pos='') {
        global $DB;
        global $CFG;

        if ($current_pos == '' && $new_pos == '') {
            $this->errors[] = 'empty move positions';
            return false;
        }

        // get id numbers of banners which need to be swapped
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

    public function upload(Array $files) {

        global $DB;

        // Check we have a file
        if($files['banner_img']['error'] != 0) {
            $this->errors[] = "No file uploaded";
            return false;
        }

        // Check file is JPEG or GIF and its size is less than 500Kb
        $filename = basename($files['banner_img']['name']);
        $ext = substr($filename, strrpos($filename, '.') + 1);

        if ((!in_array($ext, $this->valid_exts)) 
            || (!in_array($files["banner_img"]["type"], $this->valid_mimes)) 
            || ($files["banner_img"]["size"] > 500000)) 
        {
            $this->errors[] =  "Only .jpg, .jpeg, .png, .gif images under 500Kb are accepted for upload";
            return false;
        }

        // Determine the path to which we want to save this file
        $newname = $this->banner_path . $filename;

        // Check if the file with the same name is already exists on the server
        if (file_exists($newname)) {
            $this->errors[] = "File ".$files["banner_img"]["name"]." already exists";
            return false;
        }
        // Attempt to move the uploaded file to it's new place
        if (!move_uploaded_file($files['banner_img']['tmp_name'], $newname)) {
           $this->errors[] = "A problem occurred during file upload!";
           return false;
        }

        // Banner successfully updated!
            
        // Only validate URL if banner link given
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
            $this->errors[] = "Position must be numeric";
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

    public function delete($id='') {
        if ($id == '' || !is_numeric($id)) {
            $this->errors[] = 'No banner ID provided';
            return false;
        }

        global $DB;

        // get image filename and idnumber of banner to delete
        $banner_id = '';
        $img_url = '';
        if ($found = $DB->get_record($this->banners_table, array('id'=>$id), '*')) {
            $banner_id = $found->id;	
            $img_url = $found->img_url;	
        } else {
            $this->errors[] = 'Banner not found';
            return false;
        }

        // Delete the banner from the table
        $result = $DB->delete_records($this->banners_table, array('id' => $banner_id));
        if ($result === false) {
            $this->errors[] = "Could not delete banner: $banner_id";
            return false;
        } else {
            // Delete the file from banners directory - to save space and prevent 'duplicate' image errors
            $filepath = $this->banner_path . $img_url;
            //Check if the file with the same name is already exists on the server
            if (file_exists($filepath) && unlink($filepath)) {
                // Update order of banners
                if ($this->updateOrder() === false) {
                    $this->errors[] = 'Could not update banner order!';
                    return false;
                }
            }
        }
        return true;
    }


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
        // Successfully disabled and re-ordered
        return true;
    }

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
            // Successfully disabled and re-ordered
        } else {
            $this->errors[] = "Could not enable banner $id";
            return false;
        }
        return true;
    }

    public function update($id='', $link='', Array $files) {

        // Validate ids
        if ($id == '' || !is_numeric($id)) {
            $this->errors[] = 'Invalid ID: ' . $id;
            return false;
        }
        // Validate non-empty links
        if ($link != '' && !filter_var($link, FILTER_VALIDATE_URL)) {
            $this->errors[] = "Invalid URL: $link";
            return false;
        }

        global $DB;

        $new_banner = ((!empty($files["new_banner_img"])) && ($files['new_banner_img']['error'] == 0)) ? $files['new_banner_img'] : '';

        // No new banner was added. Update link only
        if ($new_banner == '') {
            $banner_found = $DB->get_record($this->banners_table, array('id' => $id));

            // Just update link and date modified
            $banner_found->date_modified = time();
            $banner_found->link = $link;
            $result = $DB->update_record($this->banners_table, $banner_found, false);

            if ($result === true) {
                // Woo hoo! everything works
                return true;
            } else {
                $this->errors[] = 'Could not update banner';
                return false;
            }
        } else {
            // If updating banner, delete old banner and then upload new banner
            // delete old banner	
            $old_banner = $DB->get_record($this->banners_table, array('id' => $id));
            if ($old_banner === false) {
                $this->errors[] = 'Banner not found';
                return false;
            }
            $newname = $old_banner->img_url;
            // Now we have image url: delete it!
            $filepath = $this->banner_path . $newname;
            // Check that a file with the same name doesn't already exist on the server
            if (file_exists($filepath) && !unlink($filepath)) {
                $this->errors[] = "Problem deleting ".$files["new_banner_img"]["name"];
                return false;
            }
            // Deleted successfully, upload new banner
            $filename = basename($files['new_banner_img']['name']);
            $ext = substr($filename, strrpos($filename, '.') + 1);

            // Check that the file is a JPEG or GIF and its size is less than 500Kb
            if ((!in_array($ext, $this->valid_exts)) || (!in_array($files["new_banner_img"]["type"], $this->valid_mimes)) || ($files["new_banner_img"]["size"] > 500000)) {
                $this->errors[] = "Only .jpg, .jpeg, .png, .gif images under 500Kb are accepted for upload";
                return false;
            }
            //Determine the path to which we want to save this file
            $newname = $this->banner_path . $filename;
            //Check if the file with the same name is already exists on the server
            if (file_exists($newname)) {
              $this->errors[] = 'A banner with this name already exists.';
              return false;
            }
            // Attempt to move the uploaded file to it's new place
            if ((!move_uploaded_file($files['new_banner_img']['tmp_name'], $newname))) {
                $this->errors[] = 'Error uploading new banner image';
                return false;
            }
            // Now finally, update the database record with new details
            $old_banner->link = $link;
            $old_banner->img_url = $filename;
            $old_banner->date_modified = time();
            $result = $DB->update_record($this->banners_table, $old_banner, false);
            if ($result === true) {
                // Woo hoo! everything works : redirect to home
                return true;
            } else {
                $this->errors[] = 'Banner update failed!';
                return false;
            }
        }
    }

    /***********************************
    *  __destruct
    *
    ************************************/
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
