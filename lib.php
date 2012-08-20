<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * repository_dsim class
 * This is a subclass of repository class
 *
 * @package    repository_dsim
 * @category   repository
 * @copyright  2012 ULCC http://www.ulcc.ac.uk
 * @author     James Ballard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class repository_dsim extends repository {

    /**
     * Constructor
     *
     * @param int $repositoryid
     * @param stdClass $context
     * @param array $options
     */
    public function __construct($repositoryid, $context = SITEID, $options = array()) {
        parent::__construct($repositoryid, $context, $options);
        $this->accessurl = $this->get_option('accessurl');
        $this->instusername = $this->get_option('instusername');
        $this->instpassword = $this->get_option('instpassword');
        $this->publiccases = $this->get_option('publiccases');
        $this->instonly = $this->get_option('instonly');
    }

    /**
     * Get file listing
     *
     * @param string $path
     * @param string $page
     */
    public function get_listing($path = '', $page = '') {
        global $CFG,$OUTPUT;
        $client = new SoapClient($this->accessurl, array("trace" => 1, "exceptions" => 0));

        $xmlCaseList = simplexml_load_string($this->getCaseList($client));

        $ret = array();

        $ret['list'] = array();
        // the management interface url
        $ret['manage'] = false;
        // dynamically loading
        $ret['dynload'] = true;
        // the current path of this list.
        $ret['path'] = array();
        // set to true, the login link will be removed
        $ret['nologin'] = true;
        // set to true, the search button will be removed
        $ret['nosearch'] = true;
        // a file in listing
        foreach($xmlCaseList->children() as $child)
        {
            $title = (string)$child->caseTitle;
            $source = (string)$child->caseID;
            $url = (string)$this->generatePlayerLink($client,$source);
            $ret['list'][] = array(
                'title'=>$title,
                'source'=>$url,
                'thumbnail' => $OUTPUT->pix_url('f/folder-32')->out(false),
                'date'=>'',
                'size'=>'unknown',
                // the accessible url of the file
                'url'=>$url
            );
        }
        return $ret;
    }

    /**
     * Get file listing
     *
     * @param string $path
     * @param string $page
     */

    public function getCaseList($client){
        $myCaseList = array(
            'institutionalUserName'=>$this->instusername,
            'institutionalPassword'=>$this->instpassword,
            'showPublicCases'=>$this->publiccases,
            'showMyInstitutionOnly'=>$this->instonly
        );
        $response = $client->getSimpleCaseList($myCaseList);
        $xml = $response->getSimpleCaseListResult;
        return $xml;

    }

    /**
     * generatePlayerLink method takes the following arguments:
     *  1: Institutional login name (user name)
     *  2: Institutional password
     *  3: Student's/Learner's user id (institution's unique identifier for that user)
     *  4: Student's First Name
     *  5: Student's Last Name
     *  6: Student's Email Address
     *  7: Case ID (vpSim ID for the case that student/learner needs to access)
     *  generatePlayerLink returns generates a one-time passcode for the student/learner
     *  and returns a URL to the vpSim player
     *
     * @param object $client
     * @param string $caseID
     *
     */

    public function generatePlayerLink($client, $caseID){
        global $USER;
        //Define an associative array of arguments for a specific webservice method

        $myPlayer = array(
            'institutionalUserName'=>$this->instusername,
            'institutionalPassword'=>$this->instpassword,
            'userID'=>$USER->username,
            'userFirstName'=>$USER->firstname,
            'userLastName'=>$USER->lastname,
            'userEmail'=>$USER->email,
            'caseID'=>$caseID
        );

        // Call webservice method
        $response = $client->generatePlayerLink($myPlayer);
        $url = $response->generatePlayerLinkResult;
        return $url;
    }

    /**
     * Check if user logged in
     */
    public function check_login() {
        global $SESSION;
        //if (!empty($SESSION->logged)) {
            //return true;
        //} else {
            //return false;
        //}
        return true;
    }

    /**
     * if check_login returns false,
     * this function will be called to print a login form.
     */
    public function print_login() {
        $user_field->label = get_string('username').': ';
        $user_field->id    = 'demo_username';
        $user_field->type  = 'text';
        $user_field->name  = 'demousername';
        $user_field->value = '';
        
        $passwd_field->label = get_string('password').': ';
        $passwd_field->id    = 'demo_password';
        $passwd_field->type  = 'password';
        $passwd_field->name  = 'demopassword';

        $form = array();
        $form['login'] = array($user_field, $passwd_field);
        return $form;
    }

    /**
     * Search in external repository
     *
     * @param string $text
     */
    public function search($text) {
        $search_result = array();
        // search result listing's format is the same as 
        // file listing
        $search_result['list'] = array();
        return $search_result;
    }
    /**
     * move file to local moodle
     * the default implementation will download the file by $url using curl,
     * that file will be saved as $file_name.
     *
     * @param string $url
     * @param string $filename
     */
    /**
    public function get_file($url, $file_name = '') {
    }
    */

    /**
     * when logout button on file picker is clicked, this function will be 
     * called.
     */
    public function logout() {
        global $SESSION;
        unset($SESSION->logged);
        return true;
    }

    /**
     *
     * @param array $options
     * @return mixed
     */
    public function set_option($options = array()) {
        if (!empty($options['accessurl'])) {
            set_config('accessurl', trim($options['accessurl']), 'dsim');
        }
        if (!empty($options['instusername'])) {
            set_config('instusername', trim($options['instusername']), 'dsim');
        }
        if (!empty($options['instpassword'])) {
            set_config('instpassword', trim($options['instpassword']), 'dsim');
        }
        if (!empty($options['publiccases'])) {
            set_config('publiccases', trim($options['publiccases']), 'dsim');
        }
        if (!empty($options['instonly'])) {
            set_config('instonly', trim($options['instonly']), 'dsim');
        }
        unset($options['accessurl']);
        unset($options['instusername']);
        unset($options['instpassword']);
        unset($options['publiccases']);
        unset($options['instonly']);

        $ret = parent::set_option($options);
        return $ret;
    }

    /**
     *
     * @param string $config
     * @return mixed
     */
    public function get_option($config = '') {

        if ($config==='accessurl') {
            return trim(get_config('dsim', 'accessurl'));
        }elseif ($config==='instusername') {
            return trim(get_config('dsim', 'instusername'));
        }elseif ($config==='instpassword') {
            return trim(get_config('dsim', 'instpassword'));
        }elseif ($config==='publiccases') {
            return trim(get_config('dsim', 'publiccases'));
        }elseif ($config==='instonly') {
            return trim(get_config('dsim', 'instonly'));
        } else {
            $options['accessurl'] = trim(get_config('dsim', 'accessurl'));
            $options['instusername'] = trim(get_config('dsim', 'instusername'));
            $options['instpassword']  = trim(get_config('dsim', 'instpassword'));
            $options['publiccases']  = trim(get_config('dsim', 'publiccases'));
            $options['instonly']  = trim(get_config('dsim', 'instonly'));
        }
        $options = parent::get_option($config);
        return $options;
    }

    /**
     * Type option names
     *
     * @return array
     */
    public static function get_type_option_names() {
        return array('accessurl','instusername','instpassword','publiccases','instonly');
    }

    /**
     * Type config form
     */
    public function type_config_form(&$mform) {
        global $CFG;
        $accessurl = get_config('dsim', 'accessurl');
        $instusername = get_config('dsim', 'instusername');
        $instpassword = get_config('dsim', 'instpassword');
        $publiccases = get_config('dsim', 'publiccases');
        $instonly = get_config('dsim', 'instonly');

        if (empty($accessurl)) {
            $accessurl = '';
        }
        if (empty($instusername)) {
            $instusername = '';
        }
        if (empty($instpassword)) {
            $instpassword = '';
        }
        if (empty($publiccases)) {
            $publiccases = '';
        }
        if (empty($instonly)) {
            $instonly = '';
        }

        parent::type_config_form($mform);

        $strrequired = get_string('required');

        $mform->addElement('text', 'accessurl', get_string('accessurl', 'repository_dsim'), array('value'=>$accessurl,'size' => '80'));
        $mform->addRule('accessurl', $strrequired, 'required', null, 'client');

        $mform->addElement('text', 'instusername', get_string('instusername', 'repository_dsim'), array('value'=>'','size' => '80'));
        $mform->addRule('instusername', $strrequired, 'required', null, 'client');

        $mform->addElement('password', 'instpassword', get_string('instpassword', 'repository_dsim'), array('value'=>$instpassword, 'size' => '80'));
        $mform->addRule('instpassword', $strrequired, 'required', null, 'client');

        $mform->addElement('selectyesno', 'publiccases', get_string('publiccases', 'repository_dsim'), array('value'=> $publiccases));
        $mform->addRule('publiccases', $strrequired, 'required', null, 'client');

        $mform->addElement('selectyesno', 'instonly', get_string('instonly', 'repository_dsim'), array('value'=> $instonly));
        $mform->addRule('instonly', $strrequired, 'required', null, 'client');
    }

    /**
     * Supports file linking and copying
     *
     * @return int
     */
    public function supported_returntypes() {
        // From moodle 2.3, we support file reference
        // see moodle docs for more information
        //return FILE_INTERNAL | FILE_EXTERNAL | FILE_REFERENCE;
        return FILE_EXTERNAL;
    }
}
