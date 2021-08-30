<?php
defined("BASEPATH") or exit("No direct script access allowed");

if (isset($_SERVER["HTTP_ORIGIN"])) {
  header("Access-Control-Allow-Origin: {$_SERVER["HTTP_ORIGIN"]}");
  header("Access-Control-Allow-Credentials: true");
  header("Access-Control-Max-Age: 86400"); // cache for 1 day
}

// Access-Control headers are received during OPTIONS requests
if ($_SERVER["REQUEST_METHOD"] == "OPTIONS") {
  
  if (isset($_SERVER["HTTP_ACCESS_CONTROL_REQUEST_METHOD"])) {
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
  }

  if (isset($_SERVER["HTTP_ACCESS_CONTROL_REQUEST_HEADERS"])) {
    header("Access-Control-Allow-Headers:{$_SERVER["HTTP_ACCESS_CONTROL_REQUEST_HEADERS"]}");
  }

  exit(0);
}


class Profile extends CI_Controller
{
  private $mydata = "";
  public function __construct()
  {
    parent::__construct();
    $this->load->model("service/Model_profile", "mp");
    $this->mydata = json_decode(file_get_contents("php://input"), true);
    if (empty($this->mydata)) {
      $this->mydata   = $_POST;
    }
    $no_auth_array = array();

    if (!in_array($this->router->fetch_method(), $no_auth_array)) {
        // $this->check_auth();
    }
  }

  function get_users()
  {
    $res = $this->mp->get_users()->result_array();
    pr($res); die;
  }
  /*
  *   for insert new profile
  */
  public function add_profile()
  {
    $keys = array("user_id", "profile_title", "profile_limit", "is_active");
    $data = check_api_keys_v1($keys, $this->mydata);

    $validation = array(
      "profile_title"    => array(
        "rule"  => "trim|required",
        "value" => $data["profile_title"]
      ),
      "user_id" => array(
        "rule"  => "trim|required",
        "value" => $data["user_id"]
      ),
      "profile_limit" => array(
        "rule"  => "trim|required",
        "value" => $data["profile_limit"]
      ),
      "is_active" => array(
        "rule"  => "trim|required",
        "value" => $data["is_active"]
      )
    );
    $this->check_validation_v1($validation);
    
    $data = getDecodeids($data);
    $res = $this->mp->add_profile($data);
    if($res)
    {
      $resp = $this->mp->get_profile($res)->row_array();
      respond_success_to_api_v1("Successfully create new profile.", getEncodesids($resp));
    }
    respond_error_to_api_v1("Failed to create new profile.");
  }

  /*
  *   update profile
  */
  public function edit_profile()
  {
    $keys = array("user_id", "profile_id", "profile_title", "profile_limit");
    $data = check_api_keys_v1($keys, $this->mydata);

    $validation = array(
      "profile_title"    => array(
        "rule"  => "trim|required",
        "value" => $data["profile_title"]
      ),
      "user_id" => array(
        "rule"  => "trim|required",
        "value" => $data["user_id"]
      ),
      "profile_id" => array(
        "rule"  => "trim|required",
        "value" => $data["profile_id"]
      ),
      "profile_limit" => array(
        "rule"  => "trim|required|in_list[1,0]",
        "value" => $data["profile_limit"]
      )
    );
    $this->check_validation_v1($validation);
    
    $data = getDecodeids($data);
    $id = $data['profile_id'];
    unset($data['profile_id']);
    $res = $this->mp->update_profile($data, $id);
    
    if($res) {
        $resp = $this->mp->get_profile($id)->row_array();
        respond_success_to_api_v1("Successfully updated profile.", getEncodesids($resp));
    }
    else {
        respond_error_to_api_v1("Failed to update profile.");
    }
  }

  /*
  *   for active profile and other will inactiveate
  */
  public function active_profile()
  {
    $keys = array("user_id", "profile_id");
    $data = check_api_keys_v1($keys, $this->mydata);

    $validation = array(
      "profile_id"    => array(
        "rule"  => "trim|required",
        "value" => $data["profile_id"]
      ),
      "user_id" => array(
        "rule"  => "trim|required",
        "value" => $data["user_id"]
      )
    );
    $this->check_validation_v1($validation);
    
    $data = getDecodeids($data);
    $data['is_deactive'] = @$this->mydata['is_deactive'];
    $id = $data['profile_id'];
    unset($data['profile_id']);
    $res = $this->mp->active_profile($data, $id);
    
    if($res) {
        $resp = $this->mp->get_profile($id)->row_array();
        respond_success_to_api_v1("Successfully change status of profile.", getEncodesids($resp));
    }
    else {
        respond_error_to_api_v1("Failed to change status of profile.");
    }
  }

  /*
  * for get all profile of a user
  */
  public function get_all_profile()
  {
    $resp = $this->mp->get_all_profile($this->UID)->result_array();
    respond_success_to_api_v1("success", getEncodesids($resp));
  }

    public function get_all_profile_with_detail()
    {
        $resp = $this->mp->get_all_profile($this->UID)->result_array();
        foreach($resp as $key => $value)
        {
            $resp[$key]['links'] = $this->mp->get_profile_detail($value['id'])->result_array();
        }
        respond_success_to_api_v1("success", getEncodesids($resp));
    }
    
  /*
  * get socal media type details
  */
  public function get_social_masters()
  {
    $res = $this->common->get_all_social_type()->result_array();
    foreach($res as $key => $val)
    {
      $res[$key]['social_media_logo'] = BASE_URL.$val['social_media_logo'];
    }
    respond_success_to_api_v1("success", getEncodesids($res));
  }


  /*
  *   for delete profile and its all links
  */
  public function delete_profile()
  {
    $keys = array("profile_id");
    $data = check_api_keys_v1($keys, $this->mydata);

    $validation = array(
      "profile_id" => array(
        "rule"  => "trim|required",
        "value" => $data["profile_id"]
      )
    );
    $this->check_validation_v1($validation);
    
    $data = getDecodeids($data);

    if($this->mp->delete_profile($data['profile_id']))
    {
      respond_success_to_api_v1("Successfully deleted your profile.");
    } else {
      respond_error_to_api_v1("Failed to delete your profile.");
    }
  }

}